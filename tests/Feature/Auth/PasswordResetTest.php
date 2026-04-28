<?php

namespace Tests\Feature\Auth;

use Illuminate\Auth\Passwords\PasswordBroker as ConcretePasswordBroker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\ResetPassword as ResetPasswordNotification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    public function testForgotPasswordDoesNotNotifyBlockedUser(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'blocked-forgot@example.com',
            'status' => 'BLOCKED',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function testForgotPasswordDoesNotNotifyInactiveUser(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'inactive-forgot@example.com',
            'status' => 'INACTIVE',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function testForgotPasswordSendsNotificationToActiveUser(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'active-forgot@example.com',
            'status' => 'ACTIVE',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function testShowResetFormRedirectsBlockedUserToRequestForm(): void
    {
        $user = User::factory()->create([
            'email' => 'blocked-reset-form@example.com',
            'status' => 'BLOCKED',
        ]);

        $url = route('password.reset', ['token' => 'unused-token']);
        $response = $this->get($url . '?email=' . urlencode($user->email));

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHasErrors([
            'email' => __('passwords.blocked'),
        ]);
    }

    public function testShowResetFormRedirectsInactiveUserToRequestForm(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive-reset-form@example.com',
            'status' => 'INACTIVE',
        ]);

        $url = route('password.reset', ['token' => 'unused-token']);
        $response = $this->get($url . '?email=' . urlencode($user->email));

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHasErrors([
            'email' => __('passwords.inactive'),
        ]);
    }

    public function testShowResetFormDisplaysForActiveUser(): void
    {
        $user = User::factory()->create([
            'email' => 'active-reset-form@example.com',
            'status' => 'ACTIVE',
            'username' => 'active_reset_user',
        ]);

        $url = route('password.reset', ['token' => 'some-token']);
        $response = $this->get($url . '?email=' . urlencode($user->email));

        $response->assertOk();
        $response->assertViewIs('auth.passwords.reset');
        $response->assertViewHas('email', $user->email);
        $response->assertViewHas('username', $user->username);
        $response->assertViewHas('token', 'some-token');
    }

    public function testResetPasswordRejectsBlockedUser(): void
    {
        $user = User::factory()->create([
            'email' => 'blocked-reset-post@example.com',
            'status' => 'BLOCKED',
        ]);

        $response = $this->from(route('password.request'))->post('/password/reset', [
            'token' => 'will-not-be-used',
            'email' => $user->email,
            'username' => $user->username,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasErrors([
            'email' => __('passwords.blocked'),
        ]);
    }

    public function testResetPasswordRejectsInactiveUser(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive-reset-post@example.com',
            'status' => 'INACTIVE',
        ]);

        $response = $this->from(route('password.request'))->post('/password/reset', [
            'token' => 'will-not-be-used',
            'email' => $user->email,
            'username' => $user->username,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasErrors([
            'email' => __('passwords.inactive'),
        ]);
    }

    public function testResetPasswordRejectsWrongUsername(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'wrong-username-reset@example.com',
            'username' => 'correct_username',
            'status' => 'ACTIVE',
        ]);

        /** @var ConcretePasswordBroker $broker */
        $broker = Password::broker();
        $token = $broker->createToken($user);

        $response = $this->from(route('password.reset', ['token' => $token]))->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'username' => 'some_other_username',
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        $response->assertSessionHasErrors([
            'email' => __('passwords.account_not_found'),
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check('oneOnlyPassword', $user->password));
    }

    public function testResetPasswordUpdatesPasswordForActiveUser(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email' => 'active-reset-post@example.com',
            'status' => 'ACTIVE',
        ]);

        /** @var ConcretePasswordBroker $broker */
        $broker = Password::broker();
        $token = $broker->createToken($user);
        $plaintextSecret = 'NewSecurePass123!';

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'username' => $user->username,
            'password' => $plaintextSecret,
            'password_confirmation' => $plaintextSecret,
        ]);

        $response->assertRedirect('/password/success');
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check($plaintextSecret, $user->password));
        $this->assertAuthenticatedAs($user, 'web');
    }
}
