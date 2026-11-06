@extends('layouts.app')

@section('title', 'Requisiciones | COMEDOR-RH')

@section('content')
    <section class="card">
        <h1>Requisiciones de insumos</h1>
        <p class="muted">Revisa las solicitudes enviadas desde HLA, captura el costo de cada insumo y acéptalas o cancélalas.</p>

        <details class="card" @if ($errors->has('solicitante') || $errors->has('items') || $errors->has('items.*')) open @endif>
            <summary><strong>Capturar requisición a nombre de un solicitante</strong></summary>
            <p class="muted">Para personas con permiso de solicitud al comedor que olvidaron subir su requisición.</p>

            @if ($solicitantes->isEmpty())
                <p class="form-error">No hay usuarios con permiso SOLICITUD_COMEDOR.</p>
            @else
                <form method="post" action="{{ route('requisiciones.store') }}" id="form-nueva-requisicion">
                    @csrf
                    <label for="solicitante">Solicitante</label>
                    <select id="solicitante" name="solicitante" required>
                        <option value="">Selecciona...</option>
                        @foreach ($solicitantes as $s)
                            <option value="{{ $s['clave'] }}" @selected(old('solicitante') === $s['clave'])>{{ $s['nombre'] }} ({{ $s['clave'] }} · {{ $s['empresa'] }})</option>
                        @endforeach
                    </select>
                    @error('solicitante') <p class="form-error">{{ $message }}</p> @enderror
                    @foreach ($errors->get('items.*') as $mensajes)
                        @foreach (array_unique($mensajes) as $mensaje) <p class="form-error">{{ $mensaje }}</p> @endforeach
                    @endforeach
                    @error('items') <p class="form-error">{{ $message }}</p> @enderror

                    <div id="nueva-items"></div>
                    <button class="secondary" type="button" id="agregar-item" style="margin-top: .75rem">Agregar otro insumo</button>

                    <label for="observaciones" style="display:block; margin-top: 1rem">Comentario general (opcional)</label>
                    <input id="observaciones" name="observaciones" maxlength="1000" value="{{ old('observaciones') }}">

                    <button type="submit" style="margin-top: 1rem">Registrar requisición</button>
                </form>

                <template id="plantilla-item">
                    <div class="grid item-nuevo" style="margin-top: .75rem; padding: .75rem; border: 1px solid #ddd; border-radius: 8px">
                        <div><label>Insumo</label><input data-campo="insumo" maxlength="200" required></div>
                        <div><label>Cantidad</label><input data-campo="cantidad" type="number" min="0.01" step="any" value="1" required></div>
                        <div><label>Fecha</label><input data-campo="fecha_requerida" type="date" min="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}" required></div>
                        <div><label>Hora</label><input data-campo="hora_requerida" type="time" value="08:00" required></div>
                        <div><label>Comentario</label><input data-campo="comentario" maxlength="500"></div>
                        <div><button class="secondary quitar-item" type="button">Quitar</button></div>
                    </div>
                </template>
            @endif
        </details>

        <form method="get" class="grid" style="margin-bottom: 1rem">
            <div>
                <label for="desde">Solicitadas desde</label>
                <input id="desde" name="desde" type="date" value="{{ $desde }}" required>
            </div>
            <div>
                <label for="hasta">Hasta</label>
                <input id="hasta" name="hasta" type="date" value="{{ $hasta }}" required>
            </div>
            <div>
                <label for="estatus">Estatus</label>
                <select id="estatus" name="estatus">
                    <option value="">Todas</option>
                    @foreach (['pendiente' => 'Pendientes', 'aceptada' => 'Aceptadas', 'cancelada' => 'Canceladas'] as $valor => $texto)
                        <option value="{{ $valor }}" @selected($estatus === $valor)>{{ $texto }}</option>
                    @endforeach
                </select>
            </div>
            <div><button type="submit">Consultar</button></div>
            <div><a class="button" href="{{ route('requisiciones.exportar', ['desde' => $desde, 'hasta' => $hasta, 'estatus' => $estatus ?: null]) }}">Descargar Excel (CSV)</a></div>
        </form>
        @error('accion') <p class="form-error">{{ $message }}</p> @enderror
        @error('costos.*') <p class="form-error">{{ $message }}</p> @enderror

        @forelse ($requisiciones as $req)
            @php
                $lineas = $items->get($req->id, collect());
                $total = $lineas->sum(fn ($i) => (float) $i->costo);
            @endphp
            <form method="post" action="{{ route('requisiciones.responder', $req->id) }}" class="card">
                @csrf
                <h2>
                    #{{ $req->id }} · {{ $req->solicitante_nombre }}
                    <small class="muted">({{ $req->solicitante_clave }}{{ $req->empresa ? ' · '.$req->empresa : '' }})</small>
                    — <strong>{{ strtoupper($req->estatus) }}</strong>
                </h2>
                <p class="muted">Enviada el {{ \Illuminate\Support\Carbon::parse($req->created_at)->format('d/m/Y H:i') }}</p>
                @if ($req->observaciones)
                    <p><strong>Comentario del solicitante:</strong> {{ $req->observaciones }}</p>
                @endif

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th>Cantidad</th>
                                <th>Para</th>
                                <th>Comentario</th>
                                <th>Costo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lineas as $linea)
                                <tr>
                                    <td>{{ $linea->insumo }}</td>
                                    <td>{{ rtrim(rtrim(number_format($linea->cantidad, 2, '.', ''), '0'), '.') }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($linea->fecha_requerida)->format('d/m/Y') }} {{ substr($linea->hora_requerida, 0, 5) }}</td>
                                    <td>{{ $linea->comentario }}</td>
                                    <td>
                                        @if ($req->estatus === 'pendiente')
                                            <input type="number" name="costos[{{ $linea->id }}]" min="0" step="0.01" value="{{ old('costos.'.$linea->id, $linea->costo) }}" style="width: 8rem">
                                        @else
                                            {{ $linea->costo !== null ? '$'.number_format($linea->costo, 2) : '—' }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        @if ($req->estatus !== 'pendiente')
                            <tfoot>
                                <tr><th colspan="4">Total</th><th>${{ number_format($total, 2) }}</th></tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                @if ($req->estatus === 'pendiente')
                    <label for="comentario-{{ $req->id }}">Comentario de respuesta (opcional)</label>
                    <input id="comentario-{{ $req->id }}" name="comentario" maxlength="500">
                    <div style="margin-top: 1rem; display: flex; gap: .5rem">
                        <button type="submit" name="accion" value="aceptar">Aceptar</button>
                        <button class="secondary" type="submit" name="accion" value="cancelar" onclick="return confirm('¿Cancelar esta requisición?')">Cancelar</button>
                    </div>
                @elseif ($req->respuesta_comentario)
                    <p><strong>Respuesta:</strong> {{ $req->respuesta_comentario }}</p>
                @endif
            </form>
        @empty
            <p class="muted">No hay requisiciones.</p>
        @endforelse

        {{ $requisiciones->links() }}
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const contenedor = document.getElementById('nueva-items');
            const plantilla = document.getElementById('plantilla-item');
            if (!contenedor || !plantilla) return;

            const reindexar = () => {
                contenedor.querySelectorAll('.item-nuevo').forEach((fila, i) => {
                    fila.querySelectorAll('[data-campo]').forEach((campo) => {
                        campo.name = `items[${i}][${campo.dataset.campo}]`;
                    });
                });
            };
            const agregar = () => {
                contenedor.appendChild(plantilla.content.cloneNode(true));
                reindexar();
            };

            document.getElementById('agregar-item').addEventListener('click', agregar);
            contenedor.addEventListener('click', (evento) => {
                if (!evento.target.classList.contains('quitar-item')) return;
                if (contenedor.children.length > 1) {
                    evento.target.closest('.item-nuevo').remove();
                    reindexar();
                }
            });
            agregar();
        })();
    </script>
@endpush
