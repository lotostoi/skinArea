<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserEmailVerificationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_resend_sends_notification_when_unverified(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'r@example.com',
            'email_verified_at' => null,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/user/email/verification-notification')
            ->assertStatus(202);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_returns_422_when_email_missing(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => null,
            'email_verified_at' => null,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/user/email/verification-notification')
            ->assertStatus(422);

        Notification::assertNothingSent();
    }
}
