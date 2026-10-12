<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConsumosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'trabajadores_hilatura_rh',
            'trabajadores_shieldtex_rh',
            'trabajadores_gecoad_rh',
        ] as $table) {
            Schema::create($table, function (Blueprint $blueprint) {
                $blueprint->string('CLAVE', 10)->primary();
                $blueprint->string('NOMBRE', 80)->nullable();
                $blueprint->string('AP_PAT_', 80)->nullable();
                $blueprint->string('AP_MAT_', 80)->nullable();
                $blueprint->string('STATUS', 1)->nullable();
            });
        }
    }

    public function test_guests_are_sent_to_the_operator_login(): void
    {
        $this->get(route('consumos.index'))
            ->assertRedirect(route('login'));
    }

    public function test_operator_can_sign_in_with_their_comedor_account(): void
    {
        $operator = $this->crearOperador();

        $this->withSession(['url.intended' => '/menu'])
            ->post(route('login.store'), [
                'email' => $operator->email,
                'password' => 'a-password-long-enough',
            ])
            ->assertRedirect(route('menu'));

        $this->assertAuthenticatedAs($operator);
    }

    public function test_authenticated_home_shows_menu_options_for_meals_and_reports(): void
    {
        $this->actingAs($this->crearOperador())
            ->get('/')
            ->assertRedirect(route('menu'));

        $this->actingAs(User::query()->first())
            ->get(route('menu'))
            ->assertOk()
            ->assertSee('Registro de comidas')
            ->assertSee('Reporte por rango de fechas')
            ->assertSee(route('consumos.index'))
            ->assertSee(route('reportes.index'));
    }

    public function test_meal_registration_page_does_not_show_the_report(): void
    {
        $this->actingAs($this->crearOperador())
            ->get(route('consumos.index'))
            ->assertOk()
            ->assertSee('Registro de comidas')
            ->assertSee(today()->locale('es')->translatedFormat('l, j \d\e F \d\e Y'))
            ->assertDontSee('name="fecha_consumo"')
            ->assertDontSee('Reporte por rango de fechas');
    }

    public function test_report_page_does_not_show_meal_registration(): void
    {
        $this->actingAs($this->crearOperador())
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('Reporte por rango de fechas')
            ->assertDontSee('Buscar o escanear clave')
            ->assertDontSee('Registrar una comida');
    }

    public function test_employee_search_explains_when_the_selected_hla_directory_table_is_missing(): void
    {
        $operator = $this->crearOperador();
        Schema::drop('trabajadores_gecoad_rh');

        $this->actingAs($operator)
            ->getJson(route('empleados.buscar', ['q' => '29', 'empresa' => 'gecoad']))
            ->assertStatus(503)
            ->assertJsonPath(
                'message',
                'No están disponibles las tablas del padrón de empleados en la base configurada: '
                    . 'trabajadores_gecoad_rh. Verifica que COMEDOR-RH use la base de HLA y que el padrón esté instalado y sincronizado.'
            );
    }

    public function test_employee_search_prioritizes_an_exact_key_match(): void
    {
        $operator = $this->crearOperador();
        foreach (range(1, 11) as $number) {
            DB::table('trabajadores_hilatura_rh')->insert([
                'CLAVE' => '29' . str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'NOMBRE' => 'Ana',
                'STATUS' => 'A',
            ]);
        }
        DB::table('trabajadores_hilatura_rh')->insert([
            'CLAVE' => '29',
            'NOMBRE' => 'Zoe',
            'STATUS' => 'A',
        ]);

        $this->actingAs($operator)
            ->getJson(route('empleados.buscar', ['q' => '29', 'empresa' => 'hilaturas']))
            ->assertOk()
            ->assertJsonPath('empleados.0.clave', '29');
    }

    public function test_employee_search_returns_the_encoded_photo_url(): void
    {
        $operator = $this->crearOperador();
        DB::table('trabajadores_hilatura_rh')->insert([
            'CLAVE' => '00123',
            'NOMBRE' => 'Ana',
            'AP_PAT_' => 'López',
            'AP_MAT_' => 'Ruiz',
            'STATUS' => 'A',
        ]);

        $this->actingAs($operator)
            ->getJson(route('empleados.buscar', ['q' => '00123', 'empresa' => 'hilaturas']))
            ->assertOk()
            ->assertJsonPath(
                'empleados.0.foto_url',
                'https://intranethlangeles.com/img/FOTOS%20PERSONAL%20HLA%202022/'
                    . 'HILATURAS/000123_Ana%20L%C3%B3pez%20Ruiz.jpg'
            );
    }

    public function test_operator_can_register_one_meal_per_employee_and_day(): void
    {
        $operator = $this->crearOperador();
        DB::table('trabajadores_hilatura_rh')->insert([
            'CLAVE' => '00123',
            'NOMBRE' => 'Ana',
            'AP_PAT_' => 'López',
            'AP_MAT_' => 'Ruiz',
            'STATUS' => 'A',
        ]);

        $datos = [
            'empresa' => 'hilaturas',
            'empleado_clave' => '00123',
        ];

        $this->actingAs($operator)
            ->post(route('consumos.store'), $datos)
            ->assertRedirect(route('consumos.index'))
            ->assertSessionHas('status');

        $this->actingAs($operator)
            ->from(route('consumos.index'))
            ->post(route('consumos.store'), $datos)
            ->assertRedirect(route('consumos.index'))
            ->assertSessionHasErrors('empleado_clave');

        $this->assertDatabaseCount('comedor_rh_consumos', 1);
        $this->assertDatabaseHas('comedor_rh_consumos', [
            'empleado_clave' => '00123',
            'fecha_consumo' => today()->toDateString(),
        ]);
    }

    public function test_scanned_meal_registration_returns_employee_data_for_confirmation_modal(): void
    {
        $operator = $this->crearOperador();
        DB::table('trabajadores_gecoad_rh')->insert([
            'CLAVE' => '00123',
            'NOMBRE' => 'Ana',
            'AP_PAT_' => 'López',
            'AP_MAT_' => 'Ruiz',
            'STATUS' => 'A',
        ]);

        $this->actingAs($operator)
            ->postJson(route('consumos.store'), [
                'empresa' => 'gecoad',
                'empleado_clave' => '00123',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Comida registrada.')
            ->assertJsonPath('empleado.nombre', 'Ana López Ruiz')
            ->assertJsonPath(
                'empleado.foto_url',
                'https://intranethlangeles.com/img/FOTOS%20PERSONAL%20HLA%202022/'
                    . 'GECOAD/000123_Ana%20L%C3%B3pez%20Ruiz.jpg'
            );

        $this->assertDatabaseCount('comedor_rh_consumos', 1);
    }

    public function test_operator_cannot_submit_a_meal_for_a_different_date(): void
    {
        $operator = $this->crearOperador();
        DB::table('trabajadores_hilatura_rh')->insert([
            'CLAVE' => '00123',
            'NOMBRE' => 'Ana',
            'STATUS' => 'A',
        ]);

        $this->actingAs($operator)
            ->from(route('consumos.index'))
            ->post(route('consumos.store'), [
                'empresa' => 'hilaturas',
                'empleado_clave' => '00123',
                'fecha_consumo' => today()->subDay()->toDateString(),
            ])
            ->assertRedirect(route('consumos.index'))
            ->assertSessionHasErrors('fecha_consumo');

        $this->assertDatabaseCount('comedor_rh_consumos', 0);
    }

    public function test_report_counts_meals_only_inside_the_requested_date_range(): void
    {
        $operator = $this->crearOperador();
        $desde = today()->subDays(2)->toDateString();
        $hasta = today()->subDay()->toDateString();
        $fueraDelRango = today()->toDateString();
        $empleado = [
            'empresa' => 'hilaturas',
            'empleado_clave' => '00123',
            'empleado_nombre' => 'Ana López Ruiz',
            'registrado_por' => $operator->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('comedor_rh_consumos')->insert([
            $empleado + ['fecha_consumo' => $desde],
            $empleado + ['fecha_consumo' => $hasta],
            $empleado + ['fecha_consumo' => $fueraDelRango],
        ]);

        $this->actingAs($operator)
            ->get(route('reportes.index') . '?desde=' . $desde . '&hasta=' . $hasta)
            ->assertOk()
            ->assertViewHas('totalComidas', 2)
            ->assertViewHas('reporte', fn ($reporte) => (int) $reporte->first()->comidas === 2)
            ->assertSee('Ana López Ruiz');
    }

    public function test_report_can_show_each_meal_with_its_date(): void
    {
        $operator = $this->crearOperador();
        $desde = today()->subDays(2)->toDateString();
        $hasta = today()->subDay()->toDateString();
        $empleado = [
            'empresa' => 'hilaturas',
            'empleado_clave' => '00123',
            'empleado_nombre' => 'Ana López Ruiz',
            'registrado_por' => $operator->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('comedor_rh_consumos')->insert([
            $empleado + ['fecha_consumo' => $desde],
            $empleado + ['fecha_consumo' => $hasta],
            $empleado + ['fecha_consumo' => today()->toDateString()],
            array_merge($empleado, [
                'empleado_clave' => '00456',
                'empleado_nombre' => 'Beto García',
                'fecha_consumo' => $desde,
            ]),
        ]);

        $this->actingAs($operator)
            ->get(route('reportes.index') . '?desde=' . $desde . '&hasta=' . $hasta . '&vista=detalle')
            ->assertOk()
            ->assertViewHas('vista', 'detalle')
            ->assertViewHas('totalComidas', 3)
            ->assertViewHas('reporte', fn ($reporte) => $reporte->count() === 2
                && (int) $reporte->first()->comidas === 2
                && count($reporte->first()->fechas) === 2)
            ->assertSee('Detalle por fecha')
            ->assertSee(\Illuminate\Support\Carbon::parse($desde)->format('d/m/Y'))
            ->assertSee(\Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y'))
            ->assertDontSee(\Illuminate\Support\Carbon::parse(today()->toDateString())->format('d/m/Y'));
    }

    public function test_summary_report_can_be_downloaded_as_excel_compatible_csv(): void
    {
        $operator = $this->crearOperador();
        $fecha = today()->subDay()->toDateString();
        DB::table('comedor_rh_consumos')->insert([
            'empresa' => 'hilaturas',
            'empleado_clave' => '00123',
            'empleado_nombre' => 'Ana López Ruiz',
            'fecha_consumo' => $fecha,
            'registrado_por' => $operator->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($operator)
            ->get(route('reportes.exportar', [
                'desde' => $fecha,
                'hasta' => $fecha,
                'empresa' => 'hilaturas',
                'vista' => 'resumen',
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('content-disposition', 'attachment; filename=reporte-comidas-resumen-' . $fecha . '-' . $fecha . '.csv');

        $filas = $this->leerCsv($response->streamedContent());
        $this->assertSame(['Empresa', 'Clave', 'Empleado', 'Comidas'], $filas[0]);
        $this->assertSame(['Hilaturas', '00123', 'Ana López Ruiz', '1'], $filas[1]);
    }

    public function test_detail_report_download_groups_meal_dates_by_employee(): void
    {
        $operator = $this->crearOperador();
        $desde = today()->subDays(2)->toDateString();
        $hasta = today()->subDay()->toDateString();
        foreach ([$desde, $hasta] as $fecha) {
            DB::table('comedor_rh_consumos')->insert([
                'empresa' => 'shieldtex',
                'empleado_clave' => '00042',
                'empleado_nombre' => 'Luis Pérez',
                'fecha_consumo' => $fecha,
                'registrado_por' => $operator->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->actingAs($operator)
            ->get(route('reportes.exportar', [
                'desde' => $desde,
                'hasta' => $hasta,
                'vista' => 'detalle',
            ]))
            ->assertOk();

        $filas = $this->leerCsv($response->streamedContent());
        $this->assertSame(['Empresa', 'Clave', 'Empleado', 'Comidas', 'Fechas registradas'], $filas[0]);
        $this->assertSame([
            'Shieldtex',
            '00042',
            'Luis Pérez',
            '2',
            \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y')
                . ' | '
                . \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y'),
        ], $filas[1]);
        $this->assertCount(2, $filas);
    }

    private function leerCsv(string $contenido): array
    {
        $contenido = substr($contenido, 3);
        $lineas = array_filter(explode("\r\n", trim($contenido)));

        return array_map(
            fn (string $linea) => str_getcsv($linea, ',', '"', ''),
            $lineas
        );
    }

    private function crearOperador(): User
    {
        return User::query()->create([
            'name' => 'Operador',
            'email' => 'operador@example.test',
            'password' => 'a-password-long-enough',
            'is_active' => true,
        ]);
    }
}
