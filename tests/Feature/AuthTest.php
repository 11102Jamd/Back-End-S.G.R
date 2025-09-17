<?php


/**
 * Prueba AuthTest Realizada por El Desarollador
 * Juan Alejandro Muñoz Devia
 */

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function user_can_login_with_valid_credentials()
    {
        // Arrange: crear usuario
        $user = User::factory()->create([
            'name1' => 'Juan',
            'name2' => 'David',
            'surname1' => 'plazas',
            'surname2' => 'hernandez',
            'email' => 'testsale3@example.com',
            'rol' => 'Cajero',
            'password' => Hash::make('juan1234')
        ]);

        // Act: enviar petición al login
        $response = $this->postJson('/api/login', [
            'email' => 'testsale3@example.com',
            'password' => 'juan1234'
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'user' => ['id', 'name1', 'name2', 'surname1', 'surname2', 'email']
        ]);
    }

    #[Test]
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'fail@example.com',
            'password' => Hash::make('secret123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'fail@example.com',
            'password' => 'wrongpass'
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Credenciales incorrectas'
        ]);
    }

    #[Test]
    public function user_can_logout_successfully()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Sesión cerrada exitosamente']);
    }


    #[Test]
    public function user_can_reset_password()
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('oldpassword')
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => 'reset@example.com',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Contraseña actualizada exitosamente. Ahora puede iniciar sesion'
        ]);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    #[Test]
    public function reset_password_fails_if_email_not_exist()
    {
        $response = $this->postJson('/api/reset-password', [
            'email' => 'noexist@example.com',
            'new_password' => 'password123',
            'new_password_confirmation' => 'password123'
        ]);

        $response->assertStatus(422); // por la validación exists:users,email
    }
}
