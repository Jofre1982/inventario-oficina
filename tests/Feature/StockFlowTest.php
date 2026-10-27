<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\Item;
use App\Models\ItemReturnDetail;
use App\Models\Movement;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class StockFlowTest extends InventoryTestCase
{
    public function test_entries_add_stock_and_leave_history(): void
    {
        $paper = $this->item(['name' => 'Resma carta'], 0);

        $this->actingAs($this->admin)->post(route('entries.store'), [
            'date' => now()->toDateString(),
            'document' => 'Factura 123',
            'items' => [['item_id' => $paper->id, 'quantity' => 10], ['item_id' => $paper->id, 'quantity' => 5]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(15, $paper->fresh()->stock);
        $movement = Movement::where('item_id', $paper->id)->firstOrFail();
        $this->assertSame(Movement::ENTRY, $movement->type);
        $this->assertSame(15, $movement->quantity);
        $this->assertSame(15, $movement->stock_after);
    }

    public function test_consumables_leave_and_do_not_need_a_return_date(): void
    {
        $paper = $this->item([], 20);

        $this->actingAs($this->admin)->post(route('deliveries.store'), [
            'responsible_id' => $this->person->id,
            'dependency_id' => $this->dependency->id,
            'date' => now()->toDateString(),
            'items' => [['item_id' => $paper->id, 'quantity' => 8]],
        ])->assertSessionHasNoErrors();

        $delivery = Delivery::firstOrFail();
        $this->assertSame(12, $paper->fresh()->stock);
        $this->assertNull($delivery->due_date);
        $this->assertSame(['Entregada', 'info'], $delivery->loanStatus());
        $this->assertSame(Movement::OUTPUT, Movement::where('delivery_id', $delivery->id)->value('type'));
    }

    public function test_returnables_require_a_return_date(): void
    {
        $laptop = $this->item(['type' => Item::RETURNABLE], 2);

        $this->actingAs($this->admin)->from(route('deliveries.create'))->post(route('deliveries.store'), [
            'responsible_id' => $this->person->id,
            'dependency_id' => $this->dependency->id,
            'date' => now()->toDateString(),
            'items' => [['item_id' => $laptop->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('items');

        $this->assertSame(2, $laptop->fresh()->stock);
        $this->assertSame(0, Delivery::count());
    }

    public function test_cannot_deliver_more_than_available(): void
    {
        $toner = $this->item([], 3);

        $this->expectException(ValidationException::class);
        try {
            $this->deliver([$toner->id => 4]);
        } finally {
            $this->assertSame(3, $toner->fresh()->stock);
        }
    }

    public function test_cannot_deliver_inactive_items_or_to_inactive_people(): void
    {
        $item = $this->item([], 5);
        $item->update(['active' => false]);
        try {
            $this->deliver([$item->id => 1]);
            $this->fail('Debía rechazar el elemento inactivo');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('inactivo', $e->getMessage());
        }

        $item->update(['active' => true]);
        $this->person->update(['active' => false]);
        $this->expectException(ValidationException::class);
        $this->deliver([$item->id => 1]);
    }

    public function test_loan_return_cycle_with_conditions(): void
    {
        $radio = $this->item(['type' => Item::RETURNABLE, 'name' => 'Radio'], 4);
        $delivery = $this->deliver([$radio->id => 3], 10);
        $detail = $delivery->details->first();

        $this->assertSame(1, $radio->fresh()->stock);
        $this->assertSame(3, $radio->fresh()->loaned_quantity);
        $this->assertSame(['Prestado', 'warning'], $delivery->fresh()->loanStatus());

        // Regresa 1 bueno y 1 dañado.
        $this->actingAs($this->admin)->post(route('returns.store', $delivery), [
            'date' => now()->toDateString(),
            'lines' => [['delivery_detail_id' => $detail->id, 'quantity' => 1, 'condition' => ItemReturnDetail::GOOD]],
        ])->assertSessionHasNoErrors();
        $this->post(route('returns.store', $delivery), [
            'date' => now()->toDateString(),
            'lines' => [['delivery_detail_id' => $detail->id, 'quantity' => 1, 'condition' => ItemReturnDetail::DAMAGED, 'notes' => 'Antena rota']],
        ])->assertSessionHasNoErrors();

        $radio->refresh();
        $this->assertSame(2, $radio->stock);
        $this->assertSame(1, $radio->unavailable_stock);
        $this->assertSame(1, $radio->loaned_quantity);
        $this->assertSame(['Devuelto en parte', 'warning'], $delivery->fresh()->loanStatus());

        // No puede devolver más de lo pendiente.
        $this->post(route('returns.store', $delivery), [
            'date' => now()->toDateString(),
            'lines' => [['delivery_detail_id' => $detail->id, 'quantity' => 2, 'condition' => ItemReturnDetail::GOOD]],
        ])->assertSessionHasErrors('items');

        $this->post(route('returns.store', $delivery), [
            'date' => now()->toDateString(),
            'lines' => [['delivery_detail_id' => $detail->id, 'quantity' => 1, 'condition' => ItemReturnDetail::INCOMPLETE]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(['Devuelto', 'success'], $delivery->fresh()->loanStatus());
        $this->assertSame(2, $radio->fresh()->unavailable_stock);
        $this->assertSame(0, $radio->fresh()->loaned_quantity);

        // Reparado: vuelve a disponible. Baja: sale del inventario.
        $this->post(route('items.repair', $radio), ['quantity' => 1])->assertSessionHasNoErrors();
        $this->post(route('items.writeoff', $radio), ['quantity' => 1, 'from' => 'unavailable', 'reason' => 'Sin arreglo'])->assertSessionHasNoErrors();
        $radio->refresh();
        $this->assertSame(3, $radio->stock);
        $this->assertSame(0, $radio->unavailable_stock);
        $this->post(route('items.writeoff', $radio), ['quantity' => 9, 'from' => 'available', 'reason' => 'x'])->assertSessionHasErrors('items');
    }

    public function test_overdue_loans_show_on_the_dashboard_and_list(): void
    {
        $drill = $this->item(['type' => Item::RETURNABLE, 'name' => 'Taladro Bosch'], 2);
        $onTime = $this->item(['type' => Item::RETURNABLE, 'name' => 'Cámara Canon'], 1);
        $this->deliver([$drill->id => 1], 5, 20);   // venció hace 15 días
        $this->deliver([$onTime->id => 1], 30, 1);  // al día

        $this->assertSame(1, Delivery::overdue()->count());

        $this->actingAs($this->admin);
        $this->get(route('home'))->assertOk()->assertSee('Taladro Bosch')->assertSee('1 préstamo vencido')->assertSee('Vencido hace 15 días');
        $this->get(route('loans.overdue'))->assertOk()->assertSee('Taladro Bosch')->assertDontSee('Cámara Canon');
        $this->get(route('loans.index'))->assertOk()->assertSee('Taladro Bosch')->assertSee('Cámara Canon');
        $this->get(route('reports.loans', ['overdue' => 1]))->assertOk()->assertSee('Luz Marina Gómez')->assertSee('Taladro Bosch')->assertDontSee('Cámara Canon');
    }

    public function test_cancel_delivery_restores_stock_only_without_returns(): void
    {
        $paper = $this->item([], 10);
        $laptop = $this->item(['type' => Item::RETURNABLE], 1);
        $delivery = $this->deliver([$paper->id => 4, $laptop->id => 1], 15);

        $this->actingAs($this->admin)->patch(route('deliveries.cancel', $delivery))->assertSessionHasNoErrors();
        $this->assertSame(10, $paper->fresh()->stock);
        $this->assertSame(1, $laptop->fresh()->stock);
        $this->assertSame(0, $laptop->fresh()->loaned_quantity);
        $this->assertSame(Delivery::CANCELED, $delivery->fresh()->status);

        $second = $this->deliver([$laptop->id => 1], 15);
        $this->inventory()->registerReturn($second, ['date' => now()->toDateString()], $this->admin->id, [
            ['delivery_detail_id' => $second->details->first()->id, 'quantity' => 1, 'condition' => ItemReturnDetail::GOOD],
        ]);
        $this->patch(route('deliveries.cancel', $second))->assertSessionHasErrors('items');
    }

    public function test_cancel_entry_only_if_the_units_are_still_in_the_warehouse(): void
    {
        $item = Item::factory()->create();
        $entry = $this->inventory()->registerEntry(['date' => now()->toDateString()], $this->admin->id, [['item_id' => $item->id, 'quantity' => 5]]);
        $this->deliver([$item->id => 3]);

        $this->actingAs($this->admin)->patch(route('entries.cancel', $entry))->assertSessionHasErrors('items');
        $this->assertSame(2, $item->fresh()->stock);

        $other = $this->inventory()->registerEntry(['date' => now()->toDateString()], $this->admin->id, [['item_id' => $item->id, 'quantity' => 4]]);
        $this->patch(route('entries.cancel', $other))->assertSessionHasNoErrors();
        $this->assertSame(2, $item->fresh()->stock);
    }

    public function test_dates_cannot_be_in_the_future_and_return_date_after_delivery(): void
    {
        $item = $this->item(['type' => Item::RETURNABLE], 2);

        $this->actingAs($this->admin)->post(route('deliveries.store'), [
            'responsible_id' => $this->person->id, 'dependency_id' => $this->dependency->id,
            'date' => now()->addDay()->toDateString(), 'due_date' => now()->addDays(5)->toDateString(),
            'items' => [['item_id' => $item->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('date');

        $this->post(route('deliveries.store'), [
            'responsible_id' => $this->person->id, 'dependency_id' => $this->dependency->id,
            'date' => now()->toDateString(), 'due_date' => now()->subDay()->toDateString(),
            'items' => [['item_id' => $item->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('due_date');

        $delivery = $this->deliver([$item->id => 1], 10, 3);
        $this->post(route('returns.store', $delivery), [
            'date' => Carbon::today()->subDays(5)->toDateString(),
            'lines' => [['delivery_detail_id' => $delivery->details->first()->id, 'quantity' => 1, 'condition' => 'GOOD']],
        ])->assertSessionHasErrors('items');
    }

    public function test_item_creation_with_initial_quantity_and_generated_code(): void
    {
        $category = \App\Models\Category::factory()->create();

        $this->actingAs($this->admin)->post(route('items.store'), [
            'name' => 'Tóner HP 85A', 'type' => Item::CONSUMABLE, 'category_id' => $category->id, 'unit' => 'Unidad', 'initial_quantity' => 6,
        ])->assertSessionHasNoErrors();

        $toner = Item::where('name', 'Tóner HP 85A')->firstOrFail();
        $this->assertSame('EL-' . str_pad((string) $toner->id, 5, '0', STR_PAD_LEFT), $toner->code);
        $this->assertSame(6, $toner->stock);
        $this->assertSame('Saldo inicial', \App\Models\Entry::latest('id')->value('document'));

        // El lector de código de barras en el buscador abre el elemento directamente.
        $this->get(route('items.index', ['q' => $toner->code]))->assertRedirect(route('items.show', $toner));
        $this->getJson(route('items.lookup', ['q' => $toner->code]))->assertOk()->assertJsonCount(1)->assertJsonPath('0.stock', 6);
        $this->getJson(route('items.lookup', ['q' => 'tóner']))->assertOk()->assertJsonPath('0.name', 'Tóner HP 85A');

        // Duplicar el código no se permite.
        $this->post(route('items.store'), [
            'name' => 'Otro', 'code' => $toner->code, 'type' => Item::CONSUMABLE, 'category_id' => $category->id, 'unit' => 'Unidad',
        ])->assertSessionHasErrors('code');
    }

    public function test_returnable_with_pending_loans_cannot_become_consumable(): void
    {
        $laptop = $this->item(['type' => Item::RETURNABLE], 1);
        $this->deliver([$laptop->id => 1], 10);

        $this->actingAs($this->admin)->put(route('items.update', $laptop), [
            'name' => $laptop->name, 'code' => $laptop->code, 'type' => Item::CONSUMABLE,
            'category_id' => $laptop->category_id, 'unit' => 'Unidad',
        ])->assertSessionHas('error');
        $this->assertSame(Item::RETURNABLE, $laptop->fresh()->type);
    }
}
