<?php

namespace App\Http\Controllers;

use App\Services\DirectorioEmpleados;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequisicionesController extends Controller
{
    private const ESTATUS = ['pendiente', 'aceptada', 'cancelada'];
    private const MODULO_PERMISO = 'SOLICITUD_COMEDOR';

    public function __construct(private readonly DirectorioEmpleados $directorio)
    {
    }

    public function index(Request $request): View
    {
        $this->autorizar($request);
        [$estatus, $desde, $hasta] = $this->filtros($request);

        $requisiciones = $this->consulta($estatus, $desde, $hasta)
            ->orderByRaw("estatus = 'pendiente' desc")
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $items = DB::table('comedor_rh_requisicion_items')
            ->whereIn('requisicion_id', $requisiciones->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('requisicion_id');

        $solicitantes = $this->solicitantesAutorizados();

        return view('requisiciones.index', compact('requisiciones', 'items', 'estatus', 'desde', 'hasta', 'solicitantes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->autorizar($request);

        $datos = $request->validate([
            'solicitante' => ['required', 'string', 'max:20'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.insumo' => ['required', 'string', 'max:200'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'items.*.fecha_requerida' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'items.*.hora_requerida' => ['required', 'date_format:H:i'],
            'items.*.comentario' => ['nullable', 'string', 'max:500'],
        ], [
            'items.required' => 'Agrega al menos un insumo.',
            'items.*.insumo.required' => 'Indica el insumo solicitado.',
            'items.*.cantidad.required' => 'Indica la cantidad.',
            'items.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'items.*.fecha_requerida.required' => 'Indica la fecha requerida.',
            'items.*.fecha_requerida.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'items.*.hora_requerida.required' => 'Indica la hora requerida.',
        ]);

        $solicitante = $this->solicitantesAutorizados()->firstWhere('clave', $datos['solicitante']);
        if ($solicitante === null) {
            throw ValidationException::withMessages(['solicitante' => 'El solicitante no tiene permiso de solicitud al comedor.']);
        }

        DB::transaction(function () use ($datos, $solicitante, $request) {
            $now = now();
            $id = DB::table('comedor_rh_requisiciones')->insertGetId([
                'solicitante_clave' => $solicitante['clave'],
                'solicitante_nombre' => mb_substr($solicitante['nombre'], 0, 250),
                'empresa' => $solicitante['empresa'],
                'capturada_por' => $request->user()->id,
                'observaciones' => $datos['observaciones'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('comedor_rh_requisicion_items')->insert(
                collect($datos['items'])->map(fn ($item) => [
                    'requisicion_id' => $id,
                    'insumo' => trim($item['insumo']),
                    'cantidad' => $item['cantidad'],
                    'fecha_requerida' => $item['fecha_requerida'],
                    'hora_requerida' => $item['hora_requerida'],
                    'comentario' => filled($item['comentario'] ?? null) ? trim($item['comentario']) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        });

        return back()->with('status', 'Requisición registrada a nombre de '.$solicitante['nombre'].'.');
    }

    public function exportar(Request $request): StreamedResponse
    {
        $this->autorizar($request);
        [$estatus, $desde, $hasta] = $this->filtros($request);

        $requisiciones = $this->consulta($estatus, $desde, $hasta)->orderBy('id')->get();
        $items = DB::table('comedor_rh_requisicion_items')
            ->whereIn('requisicion_id', $requisiciones->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('requisicion_id');

        return response()->streamDownload(function () use ($requisiciones, $items) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, [
                'Folio', 'Fecha de solicitud', 'Solicitante', 'Clave', 'Estatus', 'Insumo', 'Cantidad',
                'Fecha requerida', 'Hora requerida', 'Comentario del insumo', 'Comentario general', 'Costo', 'Respuesta del comedor',
            ], ',', '"', '', "\r\n");

            $total = 0.0;
            foreach ($requisiciones as $req) {
                foreach ($items->get($req->id, collect()) as $item) {
                    if ($req->estatus === 'aceptada') {
                        $total += (float) $item->costo;
                    }
                    fputcsv($salida, [
                        $req->id,
                        Carbon::parse($req->created_at)->format('Y-m-d H:i'),
                        $this->texto($req->solicitante_nombre),
                        $this->texto($req->solicitante_clave),
                        strtoupper($req->estatus),
                        $this->texto($item->insumo),
                        (float) $item->cantidad,
                        $item->fecha_requerida,
                        substr($item->hora_requerida, 0, 5),
                        $this->texto($item->comentario),
                        $this->texto($req->observaciones),
                        $item->costo,
                        $this->texto($req->respuesta_comentario),
                    ], ',', '"', '', "\r\n");
                }
            }

            fputcsv($salida, array_merge(array_fill(0, 10, ''), ['TOTAL ACEPTADAS', number_format($total, 2, '.', ''), '']), ',', '"', '', "\r\n");
            fclose($salida);
        }, "requisiciones-comedor-{$desde}-{$hasta}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function responder(Request $request, int $requisicion): RedirectResponse
    {
        $this->autorizar($request);

        $accion = $request->validate([
            'accion' => ['required', 'in:aceptar,cancelar'],
        ])['accion'];

        $datos = $request->validate([
            'comentario' => ['nullable', 'string', 'max:500'],
            'costos' => ['nullable', 'array'],
            'costos.*' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
        ], [
            'costos.*.numeric' => 'Cada costo debe ser un número.',
            'costos.*.min' => 'El costo no puede ser negativo.',
        ]);

        DB::transaction(function () use ($requisicion, $accion, $datos, $request) {
            $req = DB::table('comedor_rh_requisiciones')->where('id', $requisicion)->lockForUpdate()->first();
            abort_if($req === null, 404);

            if ($req->estatus !== 'pendiente') {
                throw ValidationException::withMessages(['accion' => 'La requisición ya fue atendida.']);
            }

            $ids = DB::table('comedor_rh_requisicion_items')->where('requisicion_id', $requisicion)->pluck('id');
            $costos = $datos['costos'] ?? [];

            if ($accion === 'aceptar') {
                foreach ($ids as $id) {
                    if (! isset($costos[$id]) || $costos[$id] === '') {
                        throw ValidationException::withMessages(['accion' => 'Captura el costo de todos los insumos para aceptar.']);
                    }
                }
            }

            foreach ($ids as $id) {
                if (isset($costos[$id]) && $costos[$id] !== '') {
                    DB::table('comedor_rh_requisicion_items')->where('id', $id)->update(['costo' => $costos[$id]]);
                }
            }

            DB::table('comedor_rh_requisiciones')->where('id', $requisicion)->update([
                'estatus' => $accion === 'aceptar' ? 'aceptada' : 'cancelada',
                'respuesta_comentario' => filled($datos['comentario'] ?? null) ? trim($datos['comentario']) : null,
                'revisado_por' => $request->user()->id,
                'revisado_en' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('status', $accion === 'aceptar' ? 'Requisición aceptada.' : 'Requisición cancelada.');
    }

    // Solo administradores (1) y operadores (2).
    private function autorizar(Request $request): void
    {
        abort_unless(in_array($request->user()?->is_admin, [1, 2], true), 403);
    }

    private function filtros(Request $request): array
    {
        $datos = $request->validate([
            'estatus' => ['nullable', 'in:'.implode(',', self::ESTATUS)],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);

        return [
            $datos['estatus'] ?? null,
            $datos['desde'] ?? today()->startOfMonth()->toDateString(),
            $datos['hasta'] ?? today()->toDateString(),
        ];
    }

    private function consulta(?string $estatus, string $desde, string $hasta)
    {
        return DB::table('comedor_rh_requisiciones')
            ->when($estatus, fn ($q) => $q->where('estatus', $estatus))
            ->whereDate('created_at', '>=', $desde)
            ->whereDate('created_at', '<=', $hasta);
    }

    // Personas con permiso SOLICITUD_COMEDOR en HLA, con nombre tomado del directorio de empleados.
    private function solicitantesAutorizados(): Collection
    {
        if (! Schema::hasTable('permisos_modulos')) {
            return collect();
        }

        return DB::table('permisos_modulos')
            ->where('modulo', self::MODULO_PERMISO)
            ->get(['numero_empleado', 'nombre_empresa'])
            ->map(function ($p) {
                $clave = trim((string) $p->numero_empleado);
                $empresaClave = match (strtoupper(trim((string) $p->nombre_empresa))) {
                    'HILATURAS', 'HILATURAS LOS ANGELES' => 'hilaturas',
                    'SHIELDTEX' => 'shieldtex',
                    'GECOAD' => 'gecoad',
                    default => null,
                };
                $empleado = $empresaClave ? $this->directorio->encontrar($empresaClave, $clave) : null;

                return [
                    'clave' => $clave,
                    'empresa' => $p->nombre_empresa,
                    'nombre' => $empleado['nombre'] ?? 'Empleado '.$clave,
                ];
            })
            ->filter(fn ($s) => $s['clave'] !== '')
            ->unique('clave')
            ->sortBy('nombre')
            ->values();
    }

    private function texto(?string $valor): string
    {
        $valor = (string) $valor;

        return preg_match('/^[=+\-@\t\r]/', $valor) === 1 ? "'{$valor}" : $valor;
    }
}
