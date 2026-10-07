<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

Artisan::command('comedor:operator', function () {
    $name = trim((string) $this->ask('Nombre del operador'));
    $email = strtolower(trim((string) $this->ask('Correo de acceso')));

    if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Ingresa un nombre y un correo válido.');

        return Command::FAILURE;
    }

    if (User::query()->where('email', $email)->exists()) {
        $this->error('Ya existe una cuenta con ese correo.');

        return Command::FAILURE;
    }

    $password = (string) $this->secret('Contraseña (mínimo 12 caracteres)');
    $confirmation = (string) $this->secret('Confirma la contraseña');

    if (strlen($password) < 12 || ! hash_equals($password, $confirmation)) {
        $this->error('La contraseña debe tener al menos 12 caracteres y coincidir.');

        return Command::FAILURE;
    }

    User::query()->create([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'is_active' => true,
    ]);

    $this->info('Cuenta de operador creada.');

    return Command::SUCCESS;
})->purpose('Crear una cuenta de acceso para un operador de COMEDOR-RH');
