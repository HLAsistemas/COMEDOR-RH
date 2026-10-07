@extends('layouts.app')

@section('title', 'Menú principal | COMEDOR-RH')

@section('content')
    <section class="card">
        <h1>Menú principal</h1>
        <p class="muted">Selecciona el módulo al que deseas ingresar.</p>

        <div class="menu-grid">
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
        </div>
    </section>
@endsection
