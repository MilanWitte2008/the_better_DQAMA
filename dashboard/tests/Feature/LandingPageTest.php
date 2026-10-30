<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_landing_page_links_to_login_without_exposing_a_login_form(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Kleine stappen.')
            ->assertSee('voorbeeldgegevens')
            ->assertSee(route('login'))
            ->assertDontSee('name="password"', false);
    }

    public function test_login_page_retains_the_form_and_link_back_home(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('Log in op je account')
            ->assertSee('name="password"', false)
            ->assertSee(route('home'));
    }

    public function test_guest_dashboard_access_goes_to_the_dedicated_login_page(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_validation_returns_to_login_and_preserves_email(): void
    {
        $this->from('/login')->post('/login', ['email' => 'student@example.com'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('password')
            ->assertSessionHasInput('email', 'student@example.com');
    }

    public function test_signed_in_visitors_can_go_straight_to_their_dashboard(): void
    {
        $user = new User;
        $user->forceFill(['idlid' => 1, 'voornaam' => 'Test']);
        $this->actingAs($user)->get('/login')->assertRedirect('/dashboard');
        $this->get('/')->assertOk()->assertSee('Mijn dashboard')->assertSee(route('dashboard'));
    }
}
