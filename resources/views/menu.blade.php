@extends('layouts.app')

@section('title', 'Menú principal | COMEDOR-RH')

@section('content')
    <section class="card">
        <h1>Menú principal</h1>
        <p class="muted">Selecciona el módulo al que deseas ingresar.</p>

        <div class="menu-grid">
            @if (auth()->user()->is_admin !== 3)
                <a class="menu-card" href="{{ route('menu-dia.index') }}">
                    <span class="menu-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 3v18M4 3c5 3 5 7 0 10M12 3v18M12 3c5 3 5 7 0 10M20 3v18M20 3c-5 3-5 7 0 10" />
                        </svg>
                    </span>
                    <strong>Menú del día</strong>
                    <p>Captura y consulta los platillos disponibles para hoy.</p>
                    <span class="menu-open">Abrir menú del día →</span>
                </a>

                <a class="menu-card" href="{{ route('consumos.index') }}">
                    <span class="menu-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 3v7a3 3 0 0 0 6 0V3M7 3v18M16 3v18M16 3c3 2 4 5 4 8h-4" />
                        </svg>
                    </span>
                    <strong>Registro de comidas</strong>
                    <p>Busca al empleado y registra su comida del día.</p>
                    <span class="menu-open">Abrir registro →</span>
                </a>
            @endif

            <a class="menu-card" href="{{ route('reportes.index') }}">
                <span class="menu-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="17" rx="2" />
                        <path d="M16 2v4M8 2v4M3 9h18M7 13h3M7 17h2M14 13h3M14 17h3" />
                    </svg>
                </span>
                <strong>Reporte por rango de fechas</strong>
                <p>Consulta cuántas comidas tuvo cada empleado en un periodo.</p>
                <span class="menu-open">Abrir reporte →</span>
            </a>

            @if (auth()->user()->is_admin === 1)
                <a class="menu-card" href="{{ route('usuarios.index') }}">
                    <span class="menu-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM20 8v6M23 11h-6" />
                        </svg>
                    </span>
                    <strong>Administrar usuarios</strong>
                    <p>Crea cuentas de acceso para los operadores del comedor.</p>
                    <span class="menu-open">Administrar usuarios →</span>
                </a>
            @endif
        </div>
    </section>
@endsection
