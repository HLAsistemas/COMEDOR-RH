<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_user_administration(): void
    {
        $this->get(route('usuarios.index'))
            ->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_user_administration(): void
    {
        $this->actingAs($this->crearUsuario())
            ->get(route('usuarios.index'))
            ->assertForbidden();
    }

    public function test_administrator_can_view_and_create_operator_accounts(): void
    {
        $admin = $this->crearUsuario(['username' => 'admin', 'is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('Administrar usuarios')
            ->assertSee($admin->email);

        $this->actingAs($admin)
            ->post(route('usuarios.store'), [
                'name' => 'Nuevo operador',
                'username' => 'nuevo.operador',
                'password' => 'secure-password-123',
                'password_confirmation' => 'secure-password-123',
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('status', 'El usuario se creó correctamente.');

        $this->assertDatabaseHas('comedor_rh_users', [
            'name' => 'Nuevo operador',
            'username' => 'nuevo.operador',
            'email' => null,
            'is_active' => true,
            'is_admin' => false,
        ]);
    }

    public function test_administrator_cannot_create_a_duplicate_username(): void
    {
        $admin = $this->crearUsuario(['username' => 'admin', 'is_admin' => true]);
        $this->crearUsuario(['username' => 'operador']);

        $this->actingAs($admin)
            ->from(route('usuarios.index'))
            ->post(route('usuarios.store'), [
                'name' => 'Otro operador',
                'username' => 'OPERADOR',
                'email' => 'otro@example.test',
                'password' => 'secure-password-123',
                'password_confirmation' => 'secure-password-123',
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHasErrors('username');

        $this->assertDatabaseCount('comedor_rh_users', 2);
    }

    private function crearUsuario(array $attributes = []): User
    {
        static $counter = 0;
        $counter++;

        return User::factory()->create(array_merge([
            'name' => 'Operador',
            'username' => 'operador'.$counter,
            'email' => 'admin'.$counter.'@example.test',
        ], $attributes));
    }
}
