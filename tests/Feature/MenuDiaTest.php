<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MenuDiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_the_daily_menu(): void
    {
        $this->get(route('menu-dia.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_operator_can_open_the_daily_menu(): void
    {
        $operator = $this->crearOperador();

        $this->actingAs($operator)
            ->get(route('menu-dia.index'))
            ->assertOk()
            ->assertSee('Menú del día')
            ->assertSee('Platillos incluidos en el menú')
            ->assertSee('Platillos extra')
            ->assertSee('Aún no hay platillos registrados para hoy.')
            ->assertSee('Aún no hay platillos extra registrados para hoy.');
    }

    public function test_operator_can_register_multiple_dishes_for_today(): void
    {
        $operator = $this->crearOperador();

        $this->actingAs($operator)
            ->post(route('menu-dia.store'), [
                'platillos' => [
                    ['nombre' => 'Sopa de verduras', 'descripcion' => 'Con verduras de temporada.'],
                    ['nombre' => 'Pollo en mole', 'descripcion' => 'Acompañado de arroz.'],
                ],
            ])
            ->assertRedirect(route('menu-dia.index'))
            ->assertSessionHas('status', 'Platillos agregados al menú del día.');

        $this->assertDatabaseCount('comedor_rh_menu_dia_platillos', 2);
        $this->assertDatabaseHas('comedor_rh_menu_dia_platillos', [
            'fecha' => today()->toDateString(),
            'nombre' => 'Pollo en mole',
            'descripcion' => 'Acompañado de arroz.',
            'registrado_por' => $operator->id,
        ]);

        $this->actingAs($operator)
            ->get(route('menu-dia.index'))
            ->assertSee('Sopa de verduras')
            ->assertSee('Con verduras de temporada.')
            ->assertSee('Pollo en mole')
            ->assertSee('Acompañado de arroz.');
    }

    public function test_invalid_dish_prevents_the_whole_batch_from_being_saved(): void
    {
        $operator = $this->crearOperador();

        $this->actingAs($operator)
            ->from(route('menu-dia.index'))
            ->post(route('menu-dia.store'), [
                'platillos' => [
                    ['nombre' => 'Sopa de verduras', 'descripcion' => 'Con verduras de temporada.'],
                    ['nombre' => '', 'descripcion' => 'Sin nombre.'],
                ],
            ])
            ->assertRedirect(route('menu-dia.index'))
            ->assertSessionHasErrors('platillos.1.nombre');

        $this->assertDatabaseCount('comedor_rh_menu_dia_platillos', 0);
    }

    public function test_operator_can_register_multiple_extra_dishes_separately_from_the_daily_menu(): void
    {
        $operator = $this->crearOperador();

        $this->actingAs($operator)
            ->post(route('menu-dia.extras.store'), [
                'extras' => [
                    ['nombre' => 'Quesadillas', 'descripcion' => 'Con queso y guarnición.'],
                    ['nombre' => 'Ensalada extra', 'descripcion' => 'Porción individual.'],
                ],
            ])
            ->assertRedirect(route('menu-dia.index'))
            ->assertSessionHas('status', 'Platillos extra agregados correctamente.');

        $this->assertDatabaseCount('comedor_rh_menu_dia_extras', 2);
        $this->assertDatabaseCount('comedor_rh_menu_dia_platillos', 0);
        $this->assertDatabaseHas('comedor_rh_menu_dia_extras', [
            'fecha' => today()->toDateString(),
            'nombre' => 'Quesadillas',
            'descripcion' => 'Con queso y guarnición.',
            'registrado_por' => $operator->id,
        ]);

        $this->actingAs($operator)
            ->get(route('menu-dia.index'))
            ->assertSee('Quesadillas')
            ->assertSee('Con queso y guarnición.')
            ->assertSee('Ensalada extra')
            ->assertSee('Porción individual.');
    }

    public function test_invalid_extra_dish_prevents_the_whole_batch_from_being_saved(): void
    {
        $operator = $this->crearOperador();

        $this->actingAs($operator)
            ->from(route('menu-dia.index'))
            ->post(route('menu-dia.extras.store'), [
                'extras' => [
                    ['nombre' => 'Quesadillas', 'descripcion' => 'Con queso.'],
                    ['nombre' => '', 'descripcion' => 'Sin nombre.'],
                ],
            ])
            ->assertRedirect(route('menu-dia.index'))
            ->assertSessionHasErrors('extras.1.nombre');

        $this->assertDatabaseCount('comedor_rh_menu_dia_extras', 0);
    }

    public function test_operator_can_add_a_fixed_extra_that_appears_automatically_on_future_days(): void
    {
        $operator = $this->crearOperador();

        $this->actingAs($operator)
            ->post(route('menu-dia.extras-fijos.store'), [
                'nombre' => 'Frijoles de la olla',
                'descripcion' => 'Porción disponible diariamente.',
            ])
            ->assertRedirect(route('menu-dia.index'))
            ->assertSessionHas('status', 'Platillo extra fijo agregado. Aparecerá todos los días.');

        $this->assertDatabaseHas('comedor_rh_menu_dia_extras_fijos', [
            'nombre' => 'Frijoles de la olla',
            'descripcion' => 'Porción disponible diariamente.',
            'is_active' => true,
            'registrado_por' => $operator->id,
        ]);
        $this->assertDatabaseCount('comedor_rh_menu_dia_extras', 0);

        $this->travelTo(today()->addDay());

        $this->actingAs($operator)
            ->get(route('menu-dia.index'))
            ->assertOk()
            ->assertSee('Frijoles de la olla')
            ->assertSee('Platillos extra fijos disponibles hoy');
    }

    public function test_operator_can_disable_and_reenable_a_fixed_extra(): void
    {
        $operator = $this->crearOperador();
        $fixedExtraId = DB::table('comedor_rh_menu_dia_extras_fijos')
            ->insertGetId([
                'nombre' => 'Arroz',
                'descripcion' => 'Arroz blanco.',
                'is_active' => true,
                'registrado_por' => $operator->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $this->actingAs($operator)
            ->post(route('menu-dia.extras-fijos.toggle', $fixedExtraId))
            ->assertRedirect(route('menu-dia.index'))
            ->assertSessionHas('status', 'El platillo fijo se desactivó y ya no aparecerá en los próximos menús.');

        $this->assertDatabaseHas('comedor_rh_menu_dia_extras_fijos', [
            'id' => $fixedExtraId,
            'is_active' => false,
        ]);

        $this->actingAs($operator)
            ->get(route('menu-dia.index'))
            ->assertOk()
            ->assertSee('Arroz')
            ->assertSee('No hay platillos extra fijos activos.');

        $this->actingAs($operator)
            ->post(route('menu-dia.extras-fijos.toggle', $fixedExtraId))
            ->assertRedirect(route('menu-dia.index'))
            ->assertSessionHas('status', 'El platillo fijo se activó y aparecerá todos los días.');

        $this->assertDatabaseHas('comedor_rh_menu_dia_extras_fijos', [
            'id' => $fixedExtraId,
            'is_active' => true,
        ]);
    }

    public function test_operator_can_add_a_daily_menu_card_to_the_main_menu(): void
    {
        $this->actingAs($this->crearOperador())
            ->get(route('menu'))
            ->assertOk()
            ->assertSee('Menú del día')
            ->assertSee(route('menu-dia.index'));
    }

    private function crearOperador(): User
    {
        return User::query()->create([
            'name' => 'Operador',
            'username' => 'operador',
            'email' => 'operador@example.test',
            'password' => 'a-password-long-enough',
            'is_active' => true,
        ]);
    }
}
