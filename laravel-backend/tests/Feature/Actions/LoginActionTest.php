<?php

namespace Tests\Feature\Actions;

use App\Domain\Identity\Actions\LoginAction;
use App\Domain\Identity\DTOs\LoginData;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LoginActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticates_user_with_valid_credentials(): void
    {
        $user = UserFactory::new()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $data = LoginData::from(['email' => 'test@example.com', 'password' => 'secret123']);

        app(LoginAction::class)->execute($data);

        $this->assertAuthenticatedAs($user);
    }

    public function test_throws_validation_exception_for_invalid_credentials(): void
    {
        UserFactory::new()->create(['email' => 'test@example.com']);

        $data = LoginData::from(['email' => 'test@example.com', 'password' => 'wrongpassword']);

        $this->expectException(ValidationException::class);

        app(LoginAction::class)->execute($data);
    }
}
