<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\DeliveryDetail;
use App\Models\Entry;
use App\Models\Item;
use App\Models\ItemReturn;
use App\Models\ItemReturnDetail;
use App\Models\Movement;
use App\Models\Responsible;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Toda operación que mueve el inventario pasa por aquí (igual que en el proyecto base).
 *
 * Reglas:
 *  - Una entrada suma stock; anularla lo descuenta (si todavía está en el almacén).
 *  - Una salida resta stock: los consumibles se van, los devolutivos quedan prestados.
 *  - Anular una salida devuelve todo al almacén (solo si no tiene devoluciones).
 *  - Una devolución en estado "bueno" vuelve a estar disponible; "dañado" o "incompleto"
 *    vuelve al almacén como NO disponible hasta que se marque como reparado o se dé de baja.
 *  - Nunca se deja un elemento con stock negativo.
 *  - Cada cambio queda en el historial (movements) con el saldo resultante.
 *
 * Cada operación corre en una transacción y bloquea las filas de los elementos
 * (lockForUpdate) para que dos usuarios al mismo tiempo no dejen el stock mal.
 */
class InventoryService
{
    /**
     * @param  array  $data   ['date', 'provider_id', 'document', 'notes']
     * @param  array  $items  [['item_id' => 1, 'quantity' => 10], ...]
     */
    public function registerEntry(array $data, int $userId, array $items): Entry
    {
        $items = $this->groupItems($items);
        $date = $this->date($data['date'] ?? null);

        return DB::transaction(function () use ($data, $userId, $items, $date) {
            $locked = $this->lockItems(array_keys($items));

            $entry = Entry::create([
                'date' => $date,
                'provider_id' => $data['provider_id'] ?? null,
                'document' => $data['document'] ?? null,
                'notes' => $data['notes'] ?? null,
                'user_id' => $userId,
                'status' => Entry::VALID,
            ]);

            foreach ($items as $itemId => $quantity) {
                $item = $locked[$itemId];
                $entry->details()->create(['item_id' => $itemId, 'quantity' => $quantity]);
                $item->increment('stock', $quantity);
                $this->record($item, Movement::ENTRY, $quantity, $date, $userId, ['entry_id' => $entry->id]);
            }

            return $entry;
        });
    }

    /** Anula una entrada: descuenta lo que había sumado. */
    public function cancelEntry(Entry $entry, int $userId): Entry
    {
        return DB::transaction(function () use ($entry, $userId) {
            $entry = Entry::whereKey($entry->id)->lockForUpdate()->firstOrFail();
            if (! $entry->isValid()) {
                $this->fail('La entrada ya está anulada.');
            }

            $quantities = $entry->details->groupBy('item_id')->map->sum('quantity')->all();
            $locked = $this->lockItems(array_keys($quantities));

            foreach ($quantities as $itemId => $quantity) {
                if ($locked[$itemId]->stock < $quantity) {
                    $this->fail("No se puede anular: de «{$locked[$itemId]->name}» solo quedan {$locked[$itemId]->stock} disponibles y la entrada sumó {$quantity}.");
                }
            }
            foreach ($quantities as $itemId => $quantity) {
                $locked[$itemId]->decrement('stock', $quantity);
                $this->record($locked[$itemId], Movement::ENTRY_CANCEL, $quantity, Carbon::today(), $userId, ['entry_id' => $entry->id]);
            }

            $entry->update(['status' => Entry::CANCELED, 'canceled_at' => now()]);

            return $entry;
        });
    }

    /**
     * Registra una salida (consumibles) y/o préstamo (devolutivos).
     *
     * @param  array  $data   ['date', 'due_date', 'responsible_id', 'dependency_id', 'notes']
     * @param  array  $items  [['item_id' => 1, 'quantity' => 2], ...]
     */
    public function registerDelivery(array $data, int $userId, array $items): Delivery
    {
        $items = $this->groupItems($items);
        $date = $this->date($data['date'] ?? null);
        $dueDate = ! empty($data['due_date']) ? Carbon::parse($data['due_date'])->startOfDay() : null;

        $responsible = Responsible::find($data['responsible_id'] ?? null);
        if (! $responsible) {
            $this->fail('Seleccione el responsable que recibe.');
        }
        if (! $responsible->active) {
            $this->fail("«{$responsible->name}» está inactivo; actívelo para entregarle elementos.");
        }

        return DB::transaction(function () use ($data, $userId, $items, $date, $dueDate, $responsible) {
            $locked = $this->lockItems(array_keys($items));
            $hasReturnables = false;

            foreach ($items as $itemId => $quantity) {
                $item = $locked[$itemId];
                if (! $item->active) {
                    $this->fail("El elemento «{$item->name}» está inactivo.");
                }
                if ($item->stock < $quantity) {
                    $this->fail("No hay suficientes «{$item->name}»: hay {$item->stock} disponibles y se pidieron {$quantity}.");
                }
                $hasReturnables = $hasReturnables || $item->isReturnable();
            }

            if ($hasReturnables && ! $dueDate) {
                $this->fail('Escriba la fecha en que se deben devolver los elementos devolutivos.');
            }
            if ($dueDate && $dueDate->lt($date)) {
                $this->fail('La fecha de devolución no puede ser anterior a la fecha de salida.');
            }

            $dependencyId = (int) ($data['dependency_id'] ?? 0) ?: $responsible->dependency_id;

            $delivery = Delivery::create([
                'date' => $date,
                'due_date' => $hasReturnables ? $dueDate : null,
                'responsible_id' => $responsible->id,
                'dependency_id' => $dependencyId,
                'notes' => $data['notes'] ?? null,
                'user_id' => $userId,
                'status' => Delivery::VALID,
            ]);

            foreach ($items as $itemId => $quantity) {
                $item = $locked[$itemId];
                $delivery->details()->create([
                    'item_id' => $itemId,
                    'type' => $item->type,
                    'quantity' => $quantity,
                    'returned' => 0,
                ]);
                $item->decrement('stock', $quantity);
                $this->record($item, $item->isReturnable() ? Movement::LOAN : Movement::OUTPUT, $quantity, $date, $userId, [
                    'delivery_id' => $delivery->id,
                    'responsible_id' => $responsible->id,
                    'dependency_id' => $dependencyId,
                ]);
            }

            return $delivery;
        });
    }

    /** Anula una salida sin devoluciones: todo vuelve a estar disponible. */
    public function cancelDelivery(Delivery $delivery, int $userId): Delivery
    {
        return DB::transaction(function () use ($delivery, $userId) {
            $delivery = Delivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            if (! $delivery->isValid()) {
                $this->fail('La salida ya está anulada.');
            }
            if ($delivery->returns()->exists()) {
                $this->fail('No se puede anular: la salida ya tiene devoluciones registradas.');
            }

            $quantities = $delivery->details->groupBy('item_id')->map->sum('quantity')->all();
            $locked = $this->lockItems(array_keys($quantities));

            foreach ($quantities as $itemId => $quantity) {
                $locked[$itemId]->increment('stock', $quantity);
                $this->record($locked[$itemId], Movement::OUTPUT_CANCEL, $quantity, Carbon::today(), $userId, [
                    'delivery_id' => $delivery->id,
                    'responsible_id' => $delivery->responsible_id,
                    'dependency_id' => $delivery->dependency_id,
                ]);
            }

            $delivery->update(['status' => Delivery::CANCELED, 'canceled_at' => now()]);

            return $delivery;
        });
    }

    /**
     * Registra la devolución de devolutivos de una salida.
     *
     * @param  array  $data   ['date', 'notes']
     * @param  array  $lines  [['delivery_detail_id' => 1, 'quantity' => 1, 'condition' => 'GOOD', 'notes' => ''], ...]
     */
    public function registerReturn(Delivery $delivery, array $data, int $userId, array $lines): ItemReturn
    {
        $date = $this->date($data['date'] ?? null);

        return DB::transaction(function () use ($delivery, $data, $userId, $lines, $date) {
            $delivery = Delivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
            if (! $delivery->isValid()) {
                $this->fail('La salida está anulada.');
            }
            if ($date->lt($delivery->date)) {
                $this->fail('La fecha de devolución no puede ser anterior a la fecha de salida (' . $delivery->date->format('d/m/Y') . ').');
            }

            $details = DeliveryDetail::where('delivery_id', $delivery->id)->lockForUpdate()->get()->keyBy('id');
            $valid = [];

            foreach ($lines as $line) {
                $quantity = (int) ($line['quantity'] ?? 0);
                if ($quantity <= 0) {
                    continue;
                }

                $detail = $details[(int) ($line['delivery_detail_id'] ?? 0)] ?? null;
                if (! $detail || ! $detail->isReturnable()) {
                    $this->fail('Uno de los elementos no pertenece a esta salida o no es devolutivo.');
                }

                $already = collect($valid)->where('detail.id', $detail->id)->sum('quantity');
                if ($quantity + $already > $detail->pending) {
                    $name = $detail->item->name ?? 'elemento';
                    $this->fail("De «{$name}» solo faltan por devolver {$detail->pending}.");
                }

                $condition = $line['condition'] ?? ItemReturnDetail::GOOD;
                if (! array_key_exists($condition, ItemReturnDetail::CONDITIONS)) {
                    $this->fail('Estado de devolución no válido.');
                }

                $valid[] = ['detail' => $detail, 'quantity' => $quantity, 'condition' => $condition, 'notes' => $line['notes'] ?? null];
            }

            if (! $valid) {
                $this->fail('Escriba la cantidad que se devuelve de al menos un elemento.');
            }

            $locked = $this->lockItems(collect($valid)->pluck('detail.item_id')->unique()->all());

            $return = ItemReturn::create([
                'date' => $date,
                'delivery_id' => $delivery->id,
                'notes' => $data['notes'] ?? null,
                'user_id' => $userId,
            ]);

            foreach ($valid as $line) {
                $detail = $line['detail'];
                $item = $locked[$detail->item_id];

                $return->details()->create([
                    'delivery_detail_id' => $detail->id,
                    'item_id' => $item->id,
                    'quantity' => $line['quantity'],
                    'condition' => $line['condition'],
                    'notes' => $line['notes'] ? mb_substr($line['notes'], 0, 255) : null,
                ]);
                $detail->increment('returned', $line['quantity']);

                $item->increment($line['condition'] === ItemReturnDetail::GOOD ? 'stock' : 'unavailable_stock', $line['quantity']);

                $this->record($item, Movement::RETURN, $line['quantity'], $date, $userId, [
                    'delivery_id' => $delivery->id,
                    'item_return_id' => $return->id,
                    'responsible_id' => $delivery->responsible_id,
                    'dependency_id' => $delivery->dependency_id,
                    'condition' => $line['condition'],
                    'notes' => $line['notes'] ? mb_substr($line['notes'], 0, 255) : null,
                ]);
            }

            return $return;
        });
    }

    /** Da de baja unidades (de las disponibles o de las no disponibles). */
    public function writeOff(Item $item, int $quantity, bool $fromUnavailable, string $reason, int $userId): Item
    {
        if ($quantity <= 0) {
            $this->fail('La cantidad debe ser mayor que cero.');
        }

        return DB::transaction(function () use ($item, $quantity, $fromUnavailable, $reason, $userId) {
            $item = $this->lockItems([$item->id])[$item->id];
            $column = $fromUnavailable ? 'unavailable_stock' : 'stock';

            if ($item->{$column} < $quantity) {
                $this->fail('Solo hay ' . $item->{$column} . ($fromUnavailable ? ' unidades no disponibles.' : ' unidades disponibles.'));
            }

            $item->decrement($column, $quantity);
            $this->record($item, Movement::WRITE_OFF, $quantity, Carbon::today(), $userId, [
                'notes' => mb_substr(($fromUnavailable ? '[No disponibles] ' : '') . $reason, 0, 255),
            ]);

            return $item;
        });
    }

    /** Pasa unidades dañadas/incompletas (ya arregladas) a disponibles. */
    public function markRepaired(Item $item, int $quantity, ?string $notes, int $userId): Item
    {
        if ($quantity <= 0) {
            $this->fail('La cantidad debe ser mayor que cero.');
        }

        return DB::transaction(function () use ($item, $quantity, $notes, $userId) {
            $item = $this->lockItems([$item->id])[$item->id];

            if ($item->unavailable_stock < $quantity) {
                $this->fail("Solo hay {$item->unavailable_stock} unidades no disponibles.");
            }

            $item->decrement('unavailable_stock', $quantity);
            $item->increment('stock', $quantity);
            $this->record($item, Movement::REPAIRED, $quantity, Carbon::today(), $userId, [
                'notes' => $notes ? mb_substr($notes, 0, 255) : null,
            ]);

            return $item;
        });
    }

    /** Une líneas repetidas del mismo elemento (p. ej. escaneado dos veces). */
    private function groupItems(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $id = (int) ($item['item_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($id <= 0 || $quantity <= 0) {
                continue;
            }
            $grouped[$id] = ($grouped[$id] ?? 0) + $quantity;
        }

        if (! $grouped) {
            $this->fail('Agregue al menos un elemento.');
        }

        return $grouped;
    }

    /** @return Collection<int, Item> */
    private function lockItems(array $ids): Collection
    {
        $items = Item::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        if ($items->count() !== count(array_unique($ids))) {
            $this->fail('Uno de los elementos ya no existe.');
        }

        return $items;
    }

    private function record(Item $item, string $type, int $quantity, Carbon $date, int $userId, array $extra = []): Movement
    {
        return Movement::create($extra + [
            'date' => $date,
            'item_id' => $item->id,
            'type' => $type,
            'quantity' => $quantity,
            'stock_after' => $item->stock,
            'unavailable_after' => $item->unavailable_stock,
            'user_id' => $userId,
        ]);
    }

    private function date(?string $value): Carbon
    {
        $date = $value ? Carbon::parse($value)->startOfDay() : Carbon::today();

        if ($date->gt(Carbon::today())) {
            $this->fail('La fecha no puede ser futura.');
        }

        return $date;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['items' => $message]);
    }
}
