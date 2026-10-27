<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

Artisan::command('comedor:operator', function () {
    $name = trim((string) $this->ask('Nombre del operador'));
    $username = strtolower(trim((string) $this->ask('Usuario de acceso')));
    $email = strtolower(trim((string) $this->ask('Correo de acceso (opcional)')));

    if (
        $name === ''
        || ! preg_match('/^[a-z0-9._-]{3,50}$/', $username)
        || ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL))
    ) {
        $this->error('Ingresa un nombre y un usuario válido (3 a 50 caracteres); si proporcionas correo, debe ser válido.');

        return Command::FAILURE;
    }

    if (
        User::query()->where('username', $username)
            ->when($email !== '', fn ($query) => $query->orWhere('email', $email))
            ->exists()
    ) {
        $this->error('Ya existe una cuenta con ese usuario o correo.');

        return Command::FAILURE;
    }

    $password = (string) $this->secret('Contraseña (mínimo 12 caracteres)');
    $confirmation = (string) $this->secret('Confirma la contraseña');

    if (strlen($password) < 12 || ! hash_equals($password, $confirmation)) {
        $this->error('La contraseña debe tener al menos 12 caracteres y coincidir.');

        return Command::FAILURE;
    }

    $isFirstAdmin = ! User::query()->where('is_admin', true)->exists();

    User::query()->create([
        'name' => $name,
        'username' => $username,
        'email' => $email !== '' ? $email : null,
        'password' => $password,
        'is_active' => true,
        'is_admin' => $isFirstAdmin,
    ]);

    $this->info($isFirstAdmin
        ? 'Primera cuenta creada como administradora.'
        : 'Cuenta de operador creada.');

    return Command::SUCCESS;
})->purpose('Crear una cuenta de acceso para un operador de COMEDOR-RH');

Artisan::command('comedor:admin {username}', function (string $username) {
    $user = User::query()->where('username', strtolower(trim($username)))->first();

    if (! $user) {
        $this->error('No existe una cuenta con ese usuario.');

        return Command::FAILURE;
    }

    $user->forceFill(['is_admin' => true])->save();
    $this->info("La cuenta {$user->username} ahora es administradora.");

    return Command::SUCCESS;
})->purpose('Otorgar permisos de administrador a una cuenta existente');
