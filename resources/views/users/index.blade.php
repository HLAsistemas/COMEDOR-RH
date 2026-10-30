@extends('layouts.app')

@section('title', 'Administrar usuarios | COMEDOR-RH')

@section('content')
    <section class="card">
        <h1>Administrar usuarios</h1>
        <p class="muted">Crea cuentas para operadores. El usuario y la contraseña se usan para iniciar sesión.</p>

        <form method="post" action="{{ route('usuarios.store') }}" class="card">
            @csrf
            <h2>Crear usuario</h2>
            <div class="grid">
                <div>
                    <label for="name">Nombre completo</label>
                    <input id="name" name="name" value="{{ old('name') }}" maxlength="255" required>
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="username">Usuario</label>
                    <input id="username" name="username" value="{{ old('username') }}" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" autocomplete="username" required>
                    @error('username') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email">Correo (opcional)</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email">
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="is_admin">Tipo de acceso</label>
                    <select id="is_admin" name="is_admin" required>
                        <option value="1" @selected(old('is_admin', '2') == '1')>1 - Administrador</option>
                        <option value="2" @selected(old('is_admin', '2') == '2')>2 - Operador</option>
                        <option value="3" @selected(old('is_admin', '2') == '3')>3 - Solo reportes</option>
                    </select>
                    @error('is_admin') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password">Contraseña (mínimo 12 caracteres)</label>
                    <input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required>
                    @error('password') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation">Confirmar contraseña</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
                </div>
            </div>
            <button type="submit" style="margin-top: 1rem">Crear usuario</button>
        </form>

        <h2>Usuarios registrados</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ match ($user->is_admin) { 1 => '1 - Administrador', 2 => '2 - Operador', 3 => '3 - Solo reportes', default => 'Sin tipo' } }}</td>
                            <td>{{ $user->is_active ? 'Activo' : 'Inactivo' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No hay usuarios registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $users->links() }}</div>
    </section>
@endsection
