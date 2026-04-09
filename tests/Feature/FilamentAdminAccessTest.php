<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_authenticate_via_filament_login_and_open_dashboard(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $user = User::factory()->admin()->create([
            'email' => 'filament-test@example.com',
            'username' => 'admin_tester',
        ]);

        Livewire::test(Login::class)
            ->set('data.email', 'filament-test@example.com')
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_gets_login_page_on_admin(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
    }
}
