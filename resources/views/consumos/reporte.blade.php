@extends('layouts.app')

@section('title', 'Reporte de comidas | COMEDOR-RH')

@section('content')
    <section class="card">
        <h1>Reporte por rango de fechas</h1>
        <p class="muted">Consulta las comidas registradas dentro del periodo seleccionado.</p>
        <form method="get" action="{{ route('reportes.index') }}">
            <div class="grid">
                <div>
                    <label for="desde">Desde</label>
                    <input id="desde" name="desde" type="date" value="{{ $desde }}" required>
                </div>
                <div>
                    <label for="hasta">Hasta</label>
                    <input id="hasta" name="hasta" type="date" value="{{ $hasta }}" required>
                </div>
                <div>
                    <label for="empresa-reporte">Empresa</label>
                    <select id="empresa-reporte" name="empresa">
                        <option value="">Todas</option>
                        @foreach ($empresas as $empresa)
                            <option value="{{ $empresa['clave'] }}" @selected($empresaSeleccionada === $empresa['clave'])>{{ $empresa['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="vista-reporte">Vista del reporte</label>
                    <select id="vista-reporte" name="vista">
                        <option value="resumen" @selected($vista === 'resumen')>Resumen por empleado</option>
                        <option value="detalle" @selected($vista === 'detalle')>Detalle por fecha</option>
                    </select>
                </div>
                <div>
                    <button type="submit">Consultar</button>
                </div>
            </div>
        </form>
        <a class="button" href="{{ route('reportes.exportar', ['desde' => $desde, 'hasta' => $hasta, 'empresa' => $empresaSeleccionada ?: null, 'vista' => $vista]) }}">Descargar Excel (CSV)</a>
        <div class="summary"><span>Total de comidas en el rango:</span><strong>{{ number_format($totalComidas) }}</strong></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Clave</th>
                        <th>Empleado</th>
                        @if ($vista === 'resumen')
                            <th>Comidas</th>
                        @else
                            <th>Fechas registradas</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reporte as $fila)
                        <tr>
                            <td>{{ collect($empresas)->firstWhere('clave', $fila->empresa)['nombre'] ?? $fila->empresa }}</td>
                            <td>{{ $fila->empleado_clave }}</td>
                            <td>{{ $fila->empleado_nombre }}</td>
                            @if ($vista === 'resumen')
                                <td><strong>{{ number_format($fila->comidas) }}</strong></td>
                            @else
                                <td>
                                    <strong>{{ number_format($fila->comidas) }} comidas</strong>
                                    <ul>
                                        @foreach ($fila->fechas as $fecha)
                                            <li>{{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">No hay comidas registradas en este rango.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $reporte->links() }}</div>
    </section>
@endsection
