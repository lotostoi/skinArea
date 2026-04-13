<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerifyEmailLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_link_verifies_email_and_redirects(): void
    {
        $user = User::factory()->create([
            'email' => 'verify-me@example.com',
            'email_verified_at' => null,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1((string) $user->email),
            ],
        );

        $response = $this->get($signedUrl);

        $response->assertRedirect();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_invalid_hash_returns_403(): void
    {
        $user = User::factory()->create([
            'email' => 'a@example.com',
            'email_verified_at' => null,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => 'wrong-hash',
            ],
        );

        $this->get($signedUrl)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }
}
