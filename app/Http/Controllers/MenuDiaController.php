<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MenuDiaController extends Controller
{
    public function index(): View
    {
        $fecha = today()->toDateString();

        return view('menu-dia.index', [
            'fecha' => $fecha,
            'platillos' => DB::table('comedor_rh_menu_dia_platillos')
                ->where('fecha', $fecha)
                ->orderBy('id')
                ->get(),
            'extras' => DB::table('comedor_rh_menu_dia_extras')
                ->where('fecha', $fecha)
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'platillos' => ['required', 'array', 'min:1'],
            'platillos.*.nombre' => ['required', 'string', 'max:150'],
            'platillos.*.descripcion' => ['required', 'string', 'max:2000'],
        ], [
            'platillos.required' => 'Agrega al menos un platillo.',
            'platillos.min' => 'Agrega al menos un platillo.',
            'platillos.*.nombre.required' => 'Escribe el nombre de cada platillo.',
            'platillos.*.nombre.max' => 'El nombre de cada platillo no debe exceder 150 caracteres.',
            'platillos.*.descripcion.required' => 'Escribe la descripción de cada platillo.',
            'platillos.*.descripcion.max' => 'La descripción de cada platillo no debe exceder 2000 caracteres.',
        ]);

        $ahora = now();
        $fecha = today()->toDateString();
        $platillos = array_map(
            fn (array $platillo) => [
                'fecha' => $fecha,
                'nombre' => $platillo['nombre'],
                'descripcion' => $platillo['descripcion'],
                'registrado_por' => $request->user()->id,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            $datos['platillos']
        );

        DB::table('comedor_rh_menu_dia_platillos')->insert($platillos);

        return redirect()
            ->route('menu-dia.index')
            ->with('status', 'Platillos agregados al menú del día.');
    }

    public function storeExtras(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'extras' => ['required', 'array', 'min:1'],
            'extras.*.nombre' => ['required', 'string', 'max:150'],
            'extras.*.descripcion' => ['required', 'string', 'max:2000'],
        ], [
            'extras.required' => 'Agrega al menos un platillo extra.',
            'extras.min' => 'Agrega al menos un platillo extra.',
            'extras.*.nombre.required' => 'Escribe el nombre de cada platillo extra.',
            'extras.*.nombre.max' => 'El nombre de cada platillo extra no debe exceder 150 caracteres.',
            'extras.*.descripcion.required' => 'Escribe la descripción de cada platillo extra.',
            'extras.*.descripcion.max' => 'La descripción de cada platillo extra no debe exceder 2000 caracteres.',
        ]);

        $ahora = now();
        $fecha = today()->toDateString();
        $extras = array_map(
            fn (array $platillo) => [
                'fecha' => $fecha,
                'nombre' => $platillo['nombre'],
                'descripcion' => $platillo['descripcion'],
                'registrado_por' => $request->user()->id,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
            $datos['extras']
        );

        DB::table('comedor_rh_menu_dia_extras')->insert($extras);

        return redirect()
            ->route('menu-dia.index')
            ->with('status', 'Platillos extra agregados correctamente.');
    }
}
