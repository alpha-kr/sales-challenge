<?php

namespace Tests\Feature;

use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_success_for_valid_credentials(): void
    {
        UserFactory::new()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonMissing(['token']);
    }

    public function test_login_starts_authenticated_session(): void
    {
        $user = UserFactory::new()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_returns_422_for_invalid_credentials(): void
    {
        UserFactory::new()->create(['email' => 'user@example.com']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_login_returns_422_for_missing_fields(): void
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['code', 'message', 'details']]);
    }

    public function test_logout_returns_no_content(): void
    {
        $user = UserFactory::new()->create();

        $response = $this->actingAs($user)->postJson('/api/auth/logout');

        $response->assertNoContent();
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_current_user_returns_authenticated_user(): void
    {
        $user = UserFactory::new()->create();

        $response = $this->actingAs($user)->getJson('/api/auth/user');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_current_user_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    // --- token login ---

    public function test_login_with_type_token_returns_plain_text_token(): void
    {
        UserFactory::new()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'type' => 'token',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_with_type_session_does_not_return_token(): void
    {
        UserFactory::new()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'type' => 'session',
        ]);

        $response->assertOk()
            ->assertJsonMissing(['token']);
    }

    public function test_login_with_invalid_type_returns_422(): void
    {
        UserFactory::new()->create(['email' => 'user@example.com', 'password' => bcrypt('password123')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'type' => 'cookie',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }

    public function test_token_authenticates_protected_routes(): void
    {
        UserFactory::new()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'type' => 'token',
        ]);

        $token = $loginResponse->json('data.token');

        $this->getJson('/api/auth/user', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_logout_revokes_token_when_authenticated_via_token(): void
    {
        $user = UserFactory::new()->create();
        $newToken = $user->createToken('api');

        $this->withToken($newToken->plainTextToken)->postJson('/api/auth/logout')->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $newToken->accessToken->id,
        ]);
    }
}
