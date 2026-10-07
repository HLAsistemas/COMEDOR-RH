<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DirectorioEmpleados
{
    private const EMPRESAS = [
        'hilaturas' => [
            'nombre' => 'Hilaturas',
            'tabla' => 'trabajadores_hilatura_rh',
        ],
        'shieldtex' => [
            'nombre' => 'Shieldtex',
            'tabla' => 'trabajadores_shieldtex_rh',
        ],
        'gecoad' => [
            'nombre' => 'GECOAD',
            'tabla' => 'trabajadores_gecoad_rh',
        ],
    ];

    public function empresas(): array
    {
        return collect(self::EMPRESAS)
            ->map(fn (array $empresa, string $clave) => [
                'clave' => $clave,
                'nombre' => $empresa['nombre'],
            ])
            ->values()
            ->all();
    }

    public function tablasFaltantes(?string $empresa = null): array
    {
        $empresas = $empresa === null
            ? self::EMPRESAS
            : array_intersect_key(self::EMPRESAS, [$empresa => true]);

        return collect($empresas)
            ->filter(fn (array $datos) => ! Schema::hasTable($datos['tabla']))
            ->pluck('tabla')
            ->values()
            ->all();
    }

    public function buscar(string $termino, ?string $empresa = null): array
    {
        $termino = trim($termino);
        $empresas = $empresa === null
            ? self::EMPRESAS
            : array_intersect_key(self::EMPRESAS, [$empresa => true]);
        $resultados = [];

        foreach ($empresas as $claveEmpresa => $datosEmpresa) {
            $empleados = $this->consultaActivos($datosEmpresa['tabla'])
                ->where(function (Builder $query) use ($termino) {
                    $query->where('CLAVE', 'like', $termino . '%')
                        ->orWhere('NOMBRE', 'like', '%' . $termino . '%')
                        ->orWhere('AP_PAT_', 'like', '%' . $termino . '%')
                        ->orWhere('AP_MAT_', 'like', '%' . $termino . '%');
                })
                ->orderByRaw('CASE WHEN CLAVE = ? THEN 0 ELSE 1 END', [$termino])
                ->orderBy('NOMBRE')
                ->orderBy('AP_PAT_')
                ->limit(10)
                ->get(['CLAVE', 'NOMBRE', 'AP_PAT_', 'AP_MAT_']);

            foreach ($empleados as $empleado) {
                $resultados[] = $this->formatear($claveEmpresa, $empleado);
            }
        }

        return $resultados;
    }

    public function encontrar(string $empresa, string $clave): ?array
    {
        $datosEmpresa = self::EMPRESAS[$empresa] ?? null;
        if ($datosEmpresa === null) {
            return null;
        }

        $empleado = $this->consultaActivos($datosEmpresa['tabla'])
            ->where('CLAVE', $clave)
            ->first(['CLAVE', 'NOMBRE', 'AP_PAT_', 'AP_MAT_']);

        return $empleado ? $this->formatear($empresa, $empleado) : null;
    }

    private function consultaActivos(string $tabla): Builder
    {
        return DB::table($tabla)
            ->where(function (Builder $query) {
                $query->whereNull('STATUS')
                    ->orWhere('STATUS', '!=', 'B');
            });
    }

    private function formatear(string $empresa, object $empleado): array
    {
        $nombre = trim(implode(' ', array_filter([
            $empleado->NOMBRE,
            $empleado->AP_PAT_,
            $empleado->AP_MAT_,
        ], fn ($parte) => trim((string) $parte) !== '')));

        return [
            'empresa' => $empresa,
            'empresa_nombre' => self::EMPRESAS[$empresa]['nombre'],
            'clave' => trim((string) $empleado->CLAVE),
            'nombre' => $nombre,
            'foto_url' => 'https://intranethlangeles.com/img/FOTOS%20PERSONAL%20HLA%202022/'
                . rawurlencode(strtoupper(self::EMPRESAS[$empresa]['nombre']))
                . '/'
                . rawurlencode('0' . trim((string) $empleado->CLAVE) . '_' . $nombre . '.jpg'),
        ];
    }
}
