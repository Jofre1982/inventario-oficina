<?php

namespace Tests\Feature;

use App\Models\Dependency;
use App\Models\Item;
use App\Models\Responsible;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

abstract class InventoryTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Dependency $dependency;
    protected Responsible $person;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@inventario.com')->firstOrFail();
        $this->dependency = Dependency::factory()->create(['name' => 'Secretaría de Gobierno']);
        $this->person = Responsible::factory()->create(['name' => 'Luz Marina Gómez', 'document' => '52789345', 'dependency_id' => $this->dependency->id]);
    }

    protected function inventory(): InventoryService
    {
        return app(InventoryService::class);
    }

    /** Elemento con existencias iniciales cargadas por una entrada. */
    protected function item(array $attributes = [], int $stock = 0): Item
    {
        $item = Item::factory()->create($attributes);
        if ($stock > 0) {
            $this->inventory()->registerEntry(['date' => Carbon::today()->subDays(40)->toDateString()], $this->admin->id, [
                ['item_id' => $item->id, 'quantity' => $stock],
            ]);
        }

        return $item->fresh();
    }

    protected function userWithRole(string $slug): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', $slug)->firstOrFail());

        return $user;
    }

    protected function deliver(array $lines, ?int $dueInDays = null, int $daysAgo = 0)
    {
        return $this->inventory()->registerDelivery([
            'date' => Carbon::today()->subDays($daysAgo)->toDateString(),
            'due_date' => $dueInDays === null ? null : Carbon::today()->subDays($daysAgo)->addDays($dueInDays)->toDateString(),
            'responsible_id' => $this->person->id,
            'dependency_id' => $this->dependency->id,
        ], $this->admin->id, collect($lines)->map(fn ($q, $id) => ['item_id' => $id, 'quantity' => $q])->values()->all());
    }
}
