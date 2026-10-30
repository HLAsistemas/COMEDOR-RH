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
                'is_admin' => 2,
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('status', 'El usuario se creó correctamente.');

        $this->assertDatabaseHas('comedor_rh_users', [
            'name' => 'Nuevo operador',
            'username' => 'nuevo.operador',
            'email' => null,
            'is_active' => true,
            'is_admin' => 2,
        ]);
    }

    public function test_administrator_can_create_users_with_each_access_type(): void
    {
        $admin = $this->crearUsuario(['username' => 'admin', 'is_admin' => 1]);

        foreach ([1, 2, 3] as $accessType) {
            $username = 'usuario.tipo'.$accessType;

            $this->actingAs($admin)
                ->post(route('usuarios.store'), [
                    'name' => 'Usuario tipo '.$accessType,
                    'username' => $username,
                    'password' => 'secure-password-123',
                    'password_confirmation' => 'secure-password-123',
                    'is_admin' => $accessType,
                ])
                ->assertRedirect(route('usuarios.index'))
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('comedor_rh_users', [
                'username' => $username,
                'is_admin' => $accessType,
            ]);
        }
    }

    public function test_administrator_cannot_create_a_user_with_an_invalid_access_type(): void
    {
        $admin = $this->crearUsuario(['username' => 'admin', 'is_admin' => 1]);

        $this->actingAs($admin)
            ->from(route('usuarios.index'))
            ->post(route('usuarios.store'), [
                'name' => 'Usuario inválido',
                'username' => 'usuario-invalido',
                'password' => 'secure-password-123',
                'password_confirmation' => 'secure-password-123',
                'is_admin' => 4,
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHasErrors('is_admin');

        $this->assertDatabaseMissing('comedor_rh_users', [
            'username' => 'usuario-invalido',
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
                'is_admin' => 2,
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
