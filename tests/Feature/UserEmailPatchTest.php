<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserEmailPatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_set_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => null]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/user/email', [
            'email' => 'player@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.email', 'player@example.com');

        $this->assertSame('player@example.com', $user->fresh()->email);
        Notification::assertSentTo($user->fresh(), VerifyEmail::class);
    }

    public function test_patch_email_requires_present_key(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/user/email', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_email_must_be_unique_among_other_users(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => null]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/user/email', [
            'email' => 'taken@example.com',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
