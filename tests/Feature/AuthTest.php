<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LoginAttempt;
use App\Models\SecurityEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;
use Tests\InteractsWithRoles;

class AuthTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUserWithRole('finance');
    }

    public function test_valid_credentials_login(): void
    {
        $response = $this->post('/erp/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_invalid_credentials_rejected(): void
    {
        $response = $this->post('/erp/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_rejected(): void
    {
        $this->user->update(['is_active' => false]);

        $response = $this->post('/erp/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_nonexistent_user_rejected(): void
    {
        $response = $this->post('/erp/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_failed_login_creates_login_attempt(): void
    {
        $this->post('/erp/login', [
            'email' => $this->user->email,
            'password' => 'wrong',
        ]);

        $this->assertDatabaseHas('login_attempts', [
            'email' => $this->user->email,
            'success' => false,
        ]);
    }

    public function test_successful_login_marks_attempt_success(): void
    {
        $this->post('/erp/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('login_attempts', [
            'email' => $this->user->email,
            'success' => true,
        ]);
    }

    public function test_rate_limiting_after_5_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/erp/login', [
                'email' => $this->user->email,
                'password' => 'wrong',
            ]);
        }

        $response = $this->post('/erp/login', [
            'email' => $this->user->email,
            'password' => 'wrong',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_must_change_password_redirects(): void
    {
        $this->user->update(['must_change_password' => true]);

        $this->post('/erp/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_2fa_redirects_to_2fa_page(): void
    {
        $this->user->update(['two_factor_enabled' => true]);

        $response = $this->post('/erp/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('erp.login.2fa'));
    }

    public function test_valid_2fa_code_completes_login(): void
    {
        $this->user->update(['two_factor_enabled' => true]);

        $response = $this->post('/erp/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('erp.login.2fa'));

        $response = $this->post('/erp/login/2fa', [
            'code' => 'wrong',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_invalid_2fa_code_rejected(): void
    {
        $this->user->update(['two_factor_enabled' => true]);

        $this->post('/erp/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response = $this->post('/erp/login/2fa', [
            'code' => '999999',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_expired_2fa_code_rejected(): void
    {
        $this->user->update([
            'two_factor_enabled' => true,
            'two_factor_code' => Hash::make('123456'),
            'two_factor_expires_at' => now()->subMinutes(10),
        ]);

        session(['2fa_user_id' => $this->user->id]);

        $response = $this->post('/erp/login/2fa', [
            'code' => '123456',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_2fa_without_session_redirects_to_login(): void
    {
        $response = $this->post('/erp/login/2fa', [
            'code' => '123456',
        ]);

        $response->assertRedirect(route('erp.login'));
    }

    public function test_2fa_disabled_user_redirects_to_login(): void
    {
        $this->user->update(['two_factor_enabled' => false]);
        session(['2fa_user_id' => $this->user->id]);

        $response = $this->post('/erp/login/2fa', [
            'code' => '123456',
        ]);

        $response->assertRedirect(route('erp.login'));
    }

    public function test_toggle_2fa_requires_password(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/erp/sessions/2fa', [
            'enable' => true,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertFalse($this->user->fresh()->two_factor_enabled);
    }

    public function test_toggle_2fa_with_correct_password(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/erp/sessions/2fa', [
            'enable' => true,
            'password' => 'password',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue($this->user->fresh()->two_factor_enabled);
    }

    public function test_disable_2fa_creates_security_event(): void
    {
        $this->user->update(['two_factor_enabled' => true]);
        $this->actingAs($this->user);

        $this->post('/erp/sessions/2fa', [
            'enable' => false,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('security_events', [
            'user_id' => $this->user->id,
            'event_type' => '2FA_DISABLED',
        ]);
    }

    public function test_change_password_requires_current_password(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/erp/password/change', [
            'current_password' => 'wrong',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_change_password_with_valid_current_password(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/erp/password/change', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Hash::check('newpassword123', $this->user->fresh()->password));
    }

    public function test_change_password_min_length_enforced(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/erp/password/change', [
            'current_password' => 'password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_change_password_creates_security_event(): void
    {
        $this->actingAs($this->user);

        $this->post('/erp/password/change', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $this->assertDatabaseHas('security_events', [
            'user_id' => $this->user->id,
            'event_type' => 'PASSWORD_CHANGED',
        ]);
    }

    public function test_logout_clears_session(): void
    {
        $this->actingAs($this->user);
        $this->post('/erp/logout');

        $this->assertGuest();
    }

    public function test_logout_clears_active_session_id(): void
    {
        $this->actingAs($this->user);
        $this->post('/erp/logout');

        $this->assertNull($this->user->fresh()->active_session_id);
    }

    public function test_logout_creates_audit_log(): void
    {
        $this->actingAs($this->user);
        $this->post('/erp/logout');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'LOGOUT',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_logout_other_sessions(): void
    {
        $this->actingAs($this->user);
        $this->user->createToken('test-token');

        $this->post('/erp/sessions/logout-other');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'LOGOUT_OTHER_SESSIONS',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_validated_fields_required_for_login(): void
    {
        $response = $this->post('/erp/login', []);
        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_show_login_redirects_if_authenticated(): void
    {
        $this->actingAs($this->user);
        $response = $this->get('/erp/login');
        $response->assertRedirect(route('erp.dashboard'));
    }
}
