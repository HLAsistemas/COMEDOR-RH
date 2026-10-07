@extends('layouts.app')

@section('title', 'Iniciar sesión | COMEDOR-RH')

@section('content')
    <section class="card login">
        <h1>Iniciar sesión</h1>
        <p class="muted">Acceso para operadores autorizados del comedor.</p>
        <form method="post" action="{{ route('login.store') }}">
            @csrf
            <div style="margin-bottom: 1rem">
                <label for="email">Correo</label>
                <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required autofocus>
            </div>
            <div style="margin-bottom: 1rem">
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button type="submit">Entrar</button>
        </form>
    </section>
@endsection
