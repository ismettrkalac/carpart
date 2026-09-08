<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_forgot_password_form_can_be_viewed(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
    }

    public function test_requesting_a_reset_link_for_a_known_email_sends_a_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $response = $this->post(route('password.email'), ['email' => $user->email]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_requesting_a_reset_link_for_an_unknown_email_shows_the_same_message(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_the_reset_password_form_can_be_viewed(): void
    {
        $response = $this->get(route('password.reset', ['token' => 'some-token']));

        $response->assertOk();
        $response->assertSee('some-token', false);
    }

    public function test_a_valid_token_resets_the_password_and_signs_the_user_in(): void
    {
        $user = User::factory()->create(['password' => 'old-password-123']);
        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertRedirect(route('login.create'));
        $response->assertSessionHas('status');
        $this->assertTrue(Auth::attempt(['email' => $user->email, 'password' => 'new-password-456']));
    }

    public function test_an_invalid_token_does_not_reset_the_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password-123']);

        $response = $this->post(route('password.update'), [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::attempt(['email' => $user->email, 'password' => 'new-password-456']));
    }

    public function test_a_mismatched_confirmation_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-456',
            'password_confirmation' => 'does-not-match',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
