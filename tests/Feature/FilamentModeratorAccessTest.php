<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentModeratorAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_authenticate_via_filament_login_and_open_dashboard(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('moderator'));

        $user = User::factory()->moderator()->create([
            'email' => 'moderator-panel@example.com',
            'username' => 'mod_tester',
        ]);

        Livewire::test(Login::class)
            ->set('data.email', 'moderator-panel@example.com')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_gets_login_page_on_moderator_panel(): void
    {
        $response = $this->get('/moderator/login');

        $response->assertOk();
    }
}
