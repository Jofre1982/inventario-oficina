<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\Item;
use App\Models\ItemReturn;
use App\Models\ItemReturnDetail;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Provider;
use App\Models\Responsible;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PagesTest extends InventoryTestCase
{
    private Delivery $delivery;
    private ItemReturn $return;
    private Item $laptop;

    protected function setUp(): void
    {
        parent::setUp();

        $paper = $this->item(['name' => 'Resma papel carta', 'unit' => 'Resma', 'min_stock' => 30], 40);
        $this->laptop = $this->item(['name' => 'Portátil Lenovo', 'type' => Item::RETURNABLE, 'serial' => 'Placa 0001'], 2);
        $this->delivery = $this->deliver([$paper->id => 12, $this->laptop->id => 2], 15, 2);
        $this->return = $this->inventory()->registerReturn($this->delivery, ['date' => now()->toDateString()], $this->admin->id, [
            ['delivery_detail_id' => $this->delivery->details->firstWhere('item_id', $this->laptop->id)->id, 'quantity' => 1, 'condition' => ItemReturnDetail::DAMAGED],
        ]);
    }

    public function test_guests_are_sent_to_login(): void
    {
        foreach (['/home', '/items', '/deliveries', '/users', '/reports/loans'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->get('/login')->assertOk()->assertSee('Ingresar');
        $this->get('/register')->assertNotFound();
    }

    public function test_login_and_logout(): void
    {
        $this->post('/login', ['email' => 'admin@inventario.com', 'password' => 'mala'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'admin@inventario.com', 'password' => 'secret'])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($this->admin);
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_every_page_loads_for_the_admin(): void
    {
        $role = Role::where('slug', 'almacenista')->first();
        $item = $this->delivery->details->first()->item;
        $provider = Provider::factory()->create();

        $urls = [
            route('home'), route('profile.edit'),
            route('users.index'), route('users.create'), route('users.show', $this->admin), route('users.edit', $this->admin),
            route('roles.index'), route('roles.create'), route('roles.show', $role), route('roles.edit', $role),
            route('organization.index'),
            route('categories.index'), route('categories.create'), route('categories.edit', $item->category),
            route('providers.index'), route('providers.create'), route('providers.edit', $provider),
            route('dependencies.index'), route('dependencies.create'), route('dependencies.show', $this->dependency), route('dependencies.edit', $this->dependency),
            route('responsibles.index'), route('responsibles.create'), route('responsibles.show', $this->person), route('responsibles.edit', $this->person),
            route('items.index'), route('items.index', ['low' => 1, 'type' => 'RETURNABLE', 'q' => 'a', 'status' => 'all']), route('items.create'),
            route('items.show', $item), route('items.edit', $item), route('items.show', $this->laptop),
            route('entries.index'), route('entries.create'), route('entries.show', \App\Models\Entry::first()),
            route('deliveries.index'), route('deliveries.index', ['status' => 'pending', 'q' => 'Luz']), route('deliveries.create'),
            route('deliveries.create', ['responsible' => $this->person->id]), route('deliveries.show', $this->delivery),
            route('returns.create', $this->delivery), route('returns.index'), route('returns.show', $this->return),
            route('loans.index'), route('loans.overdue'),
            route('reports.movements'), route('reports.movements', ['mode' => 'responsible']),
            route('reports.movements', ['mode' => 'responsible', 'responsible' => $this->person->id]),
            route('reports.movements', ['mode' => 'dependency', 'dependency' => $this->dependency->id]),
            route('reports.movements', ['mode' => 'date', 'from' => '2020-01-01', 'type' => 'PRESTAMO']),
            route('reports.loans'),
        ];

        $this->actingAs($this->admin);
        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('items.show', $this->laptop))->assertSee('Luz Marina Gómez')->assertSee('Placa 0001');
        $this->get(route('responsibles.show', $this->person))->assertSee('Portátil Lenovo');
        $this->get(route('home'))->assertSee('Resma papel carta'); // stock bajo (28 < 30)
        $this->get(route('reports.movements', ['mode' => 'responsible', 'responsible' => $this->person->id]))
            ->assertSee('Resma papel carta')->assertSee('Salida N.º');
    }

    public function test_pdfs_are_generated(): void
    {
        $this->actingAs($this->admin);

        foreach ([
            route('deliveries.pdf', $this->delivery),
            route('returns.pdf', $this->return),
            route('entries.pdf', \App\Models\Entry::first()),
            route('reports.loans', ['pdf' => 1]),
            route('reports.movements', ['mode' => 'date', 'pdf' => 1]),
        ] as $url) {
            $response = $this->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }
    }

    public function test_almacenista_and_consulta_roles(): void
    {
        $keeper = $this->userWithRole('almacenista');
        $this->actingAs($keeper);
        $this->get(route('deliveries.create'))->assertOk();
        $this->get(route('home'))->assertOk()->assertSee('NUEVA SALIDA')->assertDontSee('USUARIOS');
        $this->get(route('users.index'))->assertForbidden();
        $this->put(route('organization.update'), ['name' => 'X'])->assertForbidden();

        $viewer = $this->userWithRole('consulta');
        $this->actingAs($viewer);
        $this->get(route('loans.index'))->assertOk();
        $this->get(route('reports.loans'))->assertOk();
        $this->get(route('deliveries.pdf', $this->delivery))->assertOk();
        $this->get(route('deliveries.create'))->assertForbidden();
        $this->get(route('returns.create', $this->delivery))->assertForbidden();
        $this->get(route('items.create'))->assertForbidden();
        $this->patch(route('deliveries.cancel', $this->delivery))->assertForbidden();
        $this->getJson(route('items.lookup', ['q' => 'a']))->assertOk();
    }

    public function test_user_without_role_cannot_do_anything(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('home'))->assertOk();
        $this->get(route('items.index'))->assertForbidden();
        $this->get(route('loans.index'))->assertForbidden();
        $this->getJson(route('items.lookup', ['q' => 'a']))->assertForbidden();
    }

    public function test_admin_manages_users_roles_and_organization(): void
    {
        $this->actingAs($this->admin);
        $keeperRole = Role::where('slug', 'almacenista')->first();

        $this->post(route('users.store'), [
            'name' => 'Ana Almacén', 'email' => 'ana@alcaldia.gov.co',
            'password' => 'secreto1', 'password_confirmation' => 'secreto1', 'roles' => [$keeperRole->id],
        ])->assertRedirect(route('users.index'))->assertSessionHasNoErrors();

        $ana = User::where('email', 'ana@alcaldia.gov.co')->firstOrFail();
        $this->assertTrue(Hash::check('secreto1', $ana->password));
        $this->assertTrue($ana->can('deliveries.create'));
        $this->assertFalse($ana->can('users.index'));

        $this->post(route('roles.store'), [
            'name' => 'Préstamos',
            'permissions' => Permission::whereIn('slug', ['loans.index', 'returns.create'])->pluck('id')->all(),
        ])->assertSessionHasNoErrors();
        $this->assertSame(2, Role::where('slug', 'prestamos')->first()->permissions()->count());

        $this->delete(route('users.destroy', $this->admin))->assertSessionHas('error');
        $this->delete(route('roles.destroy', Role::where('slug', 'admin')->first()))->assertSessionHas('error');

        $this->put(route('organization.update'), [
            'name' => 'Alcaldía de Villa Esperanza', 'nit' => '890.123.456-7', 'city' => 'Villa Esperanza',
            'keeper_name' => 'Rosa Elena Cárdenas', 'keeper_title' => 'Almacenista general',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Alcaldía de Villa Esperanza', Organization::current()->name);
        $this->get(route('home'))->assertSee('Alcaldía de Villa Esperanza');
    }

    public function test_catalogs_and_quick_responsible_creation(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('dependencies.store'), ['name' => 'Tesorería', 'head_name' => 'Jorge', 'active' => 1])->assertSessionHasNoErrors();
        $treasury = \App\Models\Dependency::where('name', 'Tesorería')->firstOrFail();

        $this->postJson(route('responsibles.store'), [
            'document' => '1023987654', 'name' => 'Jorge Iván Martínez', 'position' => 'Tesorero', 'dependency_id' => $treasury->id,
        ])->assertCreated()->assertJsonPath('name', 'Jorge Iván Martínez');

        $this->postJson(route('responsibles.store'), [
            'document' => '1023987654', 'name' => 'Otro', 'dependency_id' => $treasury->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('document');

        $this->post(route('categories.store'), ['name' => 'Aseo'])->assertSessionHasNoErrors();
        $this->post(route('providers.store'), ['name' => 'Papelería Central'])->assertSessionHasNoErrors();

        // No se pueden borrar registros en uso.
        $this->delete(route('dependencies.destroy', $this->dependency))->assertSessionHas('error');
        $this->delete(route('responsibles.destroy', $this->person))->assertSessionHas('error');
        $this->delete(route('categories.destroy', $this->laptop->category))->assertSessionHas('error');
        $this->delete(route('items.destroy', $this->laptop))->assertSessionHas('error');

        $unused = Category::factory()->create();
        $this->delete(route('categories.destroy', $unused))->assertSessionHas('success');
        $this->assertSame(1, Responsible::where('document', '1023987654')->count());
    }

    public function test_profile_password_change(): void
    {
        $this->actingAs($this->admin);
        $this->put(route('profile.password'), ['old_password' => 'mala', 'password' => 'nueva123', 'password_confirmation' => 'nueva123'])
            ->assertSessionHasErrors('old_password');
        $this->put(route('profile.password'), ['old_password' => 'secret', 'password' => 'nueva123', 'password_confirmation' => 'nueva123'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('nueva123', $this->admin->fresh()->password));
    }

    public function test_setup_command_used_by_the_installer(): void
    {
        $this->artisan('inventario:configurar')
            ->expectsQuestion('Nombre de la alcaldía u oficina', 'Alcaldía de San Pedro')
            ->expectsQuestion('NIT (opcional, - para dejar vacío)', '800.111.222-3')
            ->expectsQuestion('Municipio (opcional)', 'San Pedro')
            ->expectsQuestion('Dirección y teléfono (opcional)', '-')
            ->expectsQuestion('Nombre de quien entrega los elementos y firma las actas (almacenista)', 'Rosa Cárdenas')
            ->expectsQuestion('Cargo de quien entrega', 'Almacenista')
            ->expectsQuestion('Correo para ingresar', 'almacen@sanpedro.gov.co')
            ->expectsQuestion('Nueva contraseña (mínimo 6 caracteres, no se ve al escribir)', 'clave123')
            ->expectsQuestion('Repita la contraseña', 'clave123')
            ->assertExitCode(0);

        $organization = Organization::current();
        $this->assertSame('Alcaldía de San Pedro', $organization->name);
        $this->assertNull($organization->address);
        $this->assertSame('Rosa Cárdenas', $organization->keeper_name);
        $this->assertTrue(Hash::check('clave123', $this->admin->fresh()->password));
        $this->assertSame('almacen@sanpedro.gov.co', $this->admin->fresh()->email);
    }
}
