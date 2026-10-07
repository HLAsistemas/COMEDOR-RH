<?php

namespace App\Http\Controllers;

use App\Services\DirectorioEmpleados;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsumosController extends Controller
{
    public function __construct(private readonly DirectorioEmpleados $directorio)
    {
    }

    public function index(): View
    {
        return view('consumos.index', [
            'empresas' => $this->directorio->empresas(),
        ]);
    }

    public function reporte(Request $request): View
    {
        [$filters, $desde, $hasta, $vista] = $this->filtrosReporte($request);
        $consulta = $this->consultaReporte($desde, $hasta, $filters['empresa'] ?? null);

        $totalComidas = (clone $consulta)->count();
        if ($vista === 'detalle') {
            $reporte = (clone $consulta)
                ->select('empresa', 'empleado_clave')
                ->selectRaw('MAX(empleado_nombre) as empleado_nombre, COUNT(*) as comidas')
                ->groupBy('empresa', 'empleado_clave')
                ->orderBy('empleado_nombre')
                ->paginate(50)
                ->withQueryString();

            if ($reporte->isNotEmpty()) {
                $fechasPorEmpleado = (clone $consulta)
                    ->select('empresa', 'empleado_clave', 'fecha_consumo')
                    ->where(function ($query) use ($reporte) {
                        foreach ($reporte as $empleado) {
                            $query->orWhere(function ($query) use ($empleado) {
                                $query->where('empresa', $empleado->empresa)
                                    ->where('empleado_clave', $empleado->empleado_clave);
                            });
                        }
                    })
                    ->orderBy('fecha_consumo')
                    ->get()
                    ->groupBy(fn ($comida) => $comida->empresa . '|' . $comida->empleado_clave);

                foreach ($reporte as $empleado) {
                    $clave = $empleado->empresa . '|' . $empleado->empleado_clave;
                    $empleado->fechas = $fechasPorEmpleado
                        ->get($clave, collect())
                        ->pluck('fecha_consumo');
                }
            }
        } else {
            $reporte = (clone $consulta)
                ->select('empresa', 'empleado_clave')
                ->selectRaw('MAX(empleado_nombre) as empleado_nombre, COUNT(*) as comidas')
                ->groupBy('empresa', 'empleado_clave')
                ->orderBy('empleado_nombre')
                ->paginate(50)
                ->withQueryString();
        }

        return view('consumos.reporte', [
            'empresas' => $this->directorio->empresas(),
            'empresaSeleccionada' => $filters['empresa'] ?? '',
            'vista' => $vista,
            'desde' => $desde,
            'hasta' => $hasta,
            'totalComidas' => $totalComidas,
            'reporte' => $reporte,
        ]);
    }

    public function exportarReporte(Request $request): StreamedResponse
    {
        [$filters, $desde, $hasta, $vista] = $this->filtrosReporte($request);
        $consulta = $this->consultaReporte($desde, $hasta, $filters['empresa'] ?? null);
        $empresas = collect($this->directorio->empresas())->keyBy('clave');
        $nombreArchivo = "reporte-comidas-{$vista}-{$desde}-{$hasta}.csv";

        return response()->streamDownload(function () use ($consulta, $empresas, $vista) {
            $salida = fopen('php://output', 'w');
            if ($salida === false) {
                throw new \RuntimeException('No fue posible generar el archivo del reporte.');
            }

            fwrite($salida, "\xEF\xBB\xBF");
            if ($vista === 'detalle') {
                fputcsv($salida, ['Empresa', 'Clave', 'Empleado', 'Comidas', 'Fechas registradas'], ',', '"', '', "\r\n");

                $empleadoActual = null;
                $fechas = [];
                foreach ((clone $consulta)
                    ->select('empresa', 'empleado_clave', 'empleado_nombre', 'fecha_consumo')
                    ->orderBy('empresa')
                    ->orderBy('empleado_clave')
                    ->orderBy('fecha_consumo')
                    ->cursor() as $consumo) {
                    $clave = $consumo->empresa . '|' . $consumo->empleado_clave;
                    if ($empleadoActual !== null && $empleadoActual['clave'] !== $clave) {
                        $this->escribirFilaDetalle($salida, $empleadoActual, $fechas, $empresas);
                        $fechas = [];
                    }

                    $empleadoActual = [
                        'clave' => $clave,
                        'empresa' => $consumo->empresa,
                        'empleado_clave' => $consumo->empleado_clave,
                        'empleado_nombre' => $consumo->empleado_nombre,
                    ];
                    $fechas[] = Carbon::parse($consumo->fecha_consumo)->format('d/m/Y');
                }

                if ($empleadoActual !== null) {
                    $this->escribirFilaDetalle($salida, $empleadoActual, $fechas, $empresas);
                }
            } else {
                fputcsv($salida, ['Empresa', 'Clave', 'Empleado', 'Comidas'], ',', '"', '', "\r\n");
                foreach ((clone $consulta)
                    ->select('empresa', 'empleado_clave')
                    ->selectRaw('MAX(empleado_nombre) as empleado_nombre, COUNT(*) as comidas')
                    ->groupBy('empresa', 'empleado_clave')
                    ->orderBy('empleado_nombre')
                    ->cursor() as $fila) {
                    fputcsv($salida, [
                        $empresas->get($fila->empresa)['nombre'] ?? $fila->empresa,
                        $this->valorTextoExcel($fila->empleado_clave),
                        $this->valorTextoExcel($fila->empleado_nombre),
                        $fila->comidas,
                    ], ',', '"', '', "\r\n");
                }
            }

            fclose($salida);
        }, $nombreArchivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtrosReporte(Request $request): array
    {
        $filters = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'empresa' => ['nullable', 'in:hilaturas,shieldtex,gecoad'],
            'vista' => ['nullable', 'in:resumen,detalle'],
        ]);
        $desde = $filters['desde'] ?? now()->startOfMonth()->toDateString();
        $hasta = $filters['hasta'] ?? today()->toDateString();
        $vista = $filters['vista'] ?? 'resumen';

        if ($hasta < $desde) {
            throw ValidationException::withMessages([
                'hasta' => 'La fecha final debe ser igual o posterior a la inicial.',
            ]);
        }

        return [$filters, $desde, $hasta, $vista];
    }

    private function consultaReporte(string $desde, string $hasta, ?string $empresa)
    {
        return DB::table('comedor_rh_consumos')
            ->whereBetween('fecha_consumo', [$desde, $hasta])
            ->when($empresa, fn ($query, string $empresa) => $query->where('empresa', $empresa));
    }

    private function escribirFilaDetalle($salida, array $empleado, array $fechas, $empresas): void
    {
        fputcsv($salida, [
            $empresas->get($empleado['empresa'])['nombre'] ?? $empleado['empresa'],
            $this->valorTextoExcel($empleado['empleado_clave']),
            $this->valorTextoExcel($empleado['empleado_nombre']),
            count($fechas),
            implode(' | ', $fechas),
        ], ',', '"', '', "\r\n");
    }

    private function valorTextoExcel(string $valor): string
    {
        return preg_match('/^[=+\-@\t\r]/', $valor) === 1 ? "'{$valor}" : $valor;
    }

    public function buscarEmpleados(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'empresa' => ['nullable', 'in:hilaturas,shieldtex,gecoad'],
        ]);

        $tablasFaltantes = $this->directorio->tablasFaltantes($filters['empresa'] ?? null);
        if ($tablasFaltantes !== []) {
            return response()->json([
                'message' => 'No están disponibles las tablas del padrón de empleados en la base configurada: '
                    . implode(', ', $tablasFaltantes)
                    . '. Verifica que COMEDOR-RH use la base de HLA y que el padrón esté instalado y sincronizado.',
            ], 503);
        }

        return response()->json([
            'empleados' => $this->directorio->buscar(
                $filters['q'],
                $filters['empresa'] ?? null
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $datos = $request->validate([
            'empresa' => ['required', 'in:hilaturas,shieldtex,gecoad'],
            'empleado_clave' => ['required', 'string', 'max:10'],
            'fecha_consumo' => ['prohibited'],
        ]);
        $fechaConsumo = today()->toDateString();

        $empleado = $this->directorio->encontrar($datos['empresa'], $datos['empleado_clave']);
        if ($empleado === null) {
            throw ValidationException::withMessages([
                'empleado_clave' => 'El empleado no existe o no está activo en el padrón de HLA.',
            ]);
        }

        $yaRegistrado = DB::table('comedor_rh_consumos')
            ->where('empresa', $empleado['empresa'])
            ->where('empleado_clave', $empleado['clave'])
            ->where('fecha_consumo', $fechaConsumo)
            ->exists();

        if ($yaRegistrado) {
            throw ValidationException::withMessages([
                'empleado_clave' => 'Ya existe una comida registrada para este empleado en esa fecha.',
            ]);
        }

        try {
            DB::table('comedor_rh_consumos')->insert([
                'empresa' => $empleado['empresa'],
                'empleado_clave' => $empleado['clave'],
                'empleado_nombre' => $empleado['nombre'],
                'fecha_consumo' => $fechaConsumo,
                'registrado_por' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (QueryException $exception) {
            if (! $this->esDuplicadoConsumo($exception)) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'empleado_clave' => 'Ya existe una comida registrada para este empleado en esa fecha.',
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Comida registrada.',
                'empleado' => $empleado,
            ]);
        }

        return redirect()
            ->route('consumos.index')
            ->with('status', "Comida registrada para {$empleado['nombre']} ({$empleado['clave']}).");
    }

    private function esDuplicadoConsumo(QueryException $exception): bool
    {
        $estado = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $mensaje = strtolower($exception->getMessage());

        return in_array($estado, ['23000', '23505'], true)
            && (
                str_contains($mensaje, 'comedor_rh_consumo_empleado_fecha_unique')
                || str_contains($mensaje, 'comedor_rh_consumos.empresa')
            );
    }
}
