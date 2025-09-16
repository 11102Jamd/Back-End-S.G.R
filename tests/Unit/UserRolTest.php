<?php

/**
 * Pruebas de autorización por rol para la creación de usuarios
 * Realizado por: Juan Alejandro Muñoz Devia
 */

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UserRolTest extends TestCase
{
    use DatabaseTransactions;

    private array $payload = [
        'name1' => 'Carlos',
        'name2' => 'Andrés',
        'surname1' => 'Gómez',
        'surname2' => 'Martínez',
        'email' => 'nuevo@example.com',
        'rol' => 'Cajero',
        'password' => 'password123'
    ];

    #[Test]
    public function admin_can_create_user()
    {
        // Arrange: crear usuario admin
        $admin = User::factory()->create([
            'rol' => 'Administrador',
            'password' => Hash::make('admin1234')
        ]);
        $token = $admin->createToken('auth_token')->plainTextToken;

        // Act: intentar crear usuario
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user', $this->payload);

        // Assert: éxito
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'name1',
            'email',
            'rol'
        ]);
    }

    #[Test]
    public function cashier_cannot_create_user()
    {
        $cashier = User::factory()->create([
            'rol' => 'Cajero',
            'password' => Hash::make('cashier123')
        ]);
        $token = $cashier->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user', $this->payload);

        $response->assertStatus(403);
        $response->assertSeeText('Acceso solo para administradores');
    }

    #[Test]
    public function baker_cannot_create_user()
    {
        $baker = User::factory()->create([
            'rol' => 'Panadero',
            'password' => Hash::make('baker123')
        ]);
        $token = $baker->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/user', $this->payload);

        $response->assertStatus(403);
        $response->assertSeeText('Acceso solo para administradores');
    }

    #[Test]
    public function admin_can_update_user()
    {
        // Arrange: crear admin y un usuario normal
        $admin = User::factory()->create([
            'rol' => 'Administrador',
            'password' => Hash::make('admin1234')
        ]);
        $token = $admin->createToken('auth_token')->plainTextToken;

        $user = User::factory()->create();

        $payload = [
            'name1' => 'NombreActualizado',
            'name2' => $user->name2,
            'surname1' => $user->surname1,
            'surname2' => $user->surname2,
            'email' => $user->email, // se permite mantener el mismo email
            'rol' => $user->rol,
        ];

        // Act
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/user/{$user->id}", $payload);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name1' => 'NombreActualizado'
        ]);
    }

    #[Test]
    public function cashier_cannot_update_user()
    {
        $cashier = User::factory()->create(['rol' => 'Cajero']);
        $token = $cashier->createToken('auth_token')->plainTextToken;

        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/user/{$user->id}", ['name1' => 'CambioNoPermitido']);

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_disable_user()
    {
        $admin = User::factory()->create(['rol' => 'Administrador']);
        $token = $admin->createToken('auth_token')->plainTextToken;

        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/user/{$user->id}/disable");

        $response->assertStatus(200);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    #[Test]
    public function baker_cannot_disable_user()
    {
        $baker = User::factory()->create(['rol' => 'Panadero']);
        $token = $baker->createToken('auth_token')->plainTextToken;

        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/user/{$user->id}/disable");

        $response->assertStatus(403);
    }

    #[Test]
    public function it_returns_true_if_user_is_admin()
    {
        $user = new User(['rol' => 'Administrador']);
        $this->assertTrue($user->isAdmin());
    }

    #[Test]
    public function it_returns_false_if_user_is_not_admin()
    {
        $user = new User(['rol' => 'Cajero']);
        $this->assertFalse($user->isAdmin());
    }

    public function it_returns_true_if_user_is_cashier()
    {
        $user = new User(['rol' => 'Cajero']);
        $this->assertTrue($user->isCashier());
    }

    #[Test]
    public function it_returns_false_if_user_is_not_cashier()
    {
        $user = new User(['rol' => 'Cajero']);
        $this->assertFalse($user->isAdmin());
    }
}
