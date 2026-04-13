<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_list_support_ticket(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/v1/support/tickets', [
            'subject' => 'Проблема с оплатой',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.subject', 'Проблема с оплатой')
            ->assertJsonPath('data.status', SupportTicketStatus::Open->value);

        $list = $this->getJson('/api/v1/support/tickets');

        $list->assertOk();
        $list->assertJsonCount(1, 'data');
    }

    public function test_user_cannot_view_foreign_ticket(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ticket = SupportTicket::query()->create([
            'user_id' => $owner->id,
            'subject' => 'Чужой тикет',
            'status' => SupportTicketStatus::Open,
        ]);

        Sanctum::actingAs($other);

        $this->getJson("/api/v1/support/tickets/{$ticket->id}")
            ->assertForbidden();
    }

    public function test_muted_user_cannot_post_message(): void
    {
        $user = User::factory()->create([
            'support_muted_until' => now()->addDay(),
        ]);
        Sanctum::actingAs($user);

        $ticket = SupportTicket::query()->create([
            'user_id' => $user->id,
            'subject' => null,
            'status' => SupportTicketStatus::Open,
        ]);

        $this->postJson("/api/v1/support/tickets/{$ticket->id}/messages", [
            'body' => 'Привет',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Отправка сообщений в техподдержку временно ограничена.');
    }
}
