<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_logout(): void
    {
        User::factory()->create([
            'email' => 'admin@example.test',
            'password' => Hash::make('a-strong-test-password'),
        ]);

        $this->post('/login', [
            'email' => 'admin@example.test',
            'password' => 'a-strong-test-password',
        ])->assertRedirect(route('employees.index'));

        $this->get(route('employees.index'))->assertOk();

        $this->post('/logout')->assertRedirect(route('login'));
        $this->get(route('employees.index'))->assertRedirect(route('login'));
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        User::factory()->create([
            'email' => 'admin@example.test',
            'password' => Hash::make('a-strong-test-password'),
        ]);

        $this->post('/login', [
            'email' => 'admin@example.test',
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->get(route('employees.index'))->assertRedirect(route('login'));
    }

    public function test_employee_management_requires_authentication(): void
    {
        $this->get(route('employees.index'))->assertRedirect(route('login'));
        $this->get(route('employees.create'))->assertRedirect(route('login'));
        $this->post(route('employees.store'), [
            'first_name' => 'QA',
            'last_name' => 'User',
            'email' => 'qa.user@example.test',
            'department' => 'Test',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('employees', ['email' => 'qa.user@example.test']);
    }

    public function test_login_errors_cannot_redirect_to_an_external_referer(): void
    {
        $this->withHeader('Referer', 'https://attacker.example/login')
            ->post('/login', ['email' => 'invalid', 'password' => 'x'])
            ->assertRedirect(route('login'));
    }

    public function test_same_host_referer_on_an_untrusted_port_uses_the_canonical_app_url(): void
    {
        $this->actingAs(User::factory()->create())
            ->withHeader('Referer', 'http://localhost:9999/employees/create')
            ->post('/employees', [
                'first_name' => '',
                'last_name' => '',
                'email' => 'invalid',
                'department' => '',
            ])
            ->assertRedirect(route('employees.create'));
    }

    public function test_login_page_has_security_headers(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_admin_command_creates_a_hashed_password(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Admin name', 'Demo Admin')
            ->expectsQuestion('Admin email', 'demo.admin@example.test')
            ->expectsQuestion('Admin password (at least 12 characters)', 'long-demo-password')
            ->expectsQuestion('Confirm admin password', 'long-demo-password')
            ->assertExitCode(0);

        $user = User::query()->where('email', 'demo.admin@example.test')->firstOrFail();
        $this->assertNotSame('long-demo-password', $user->password);
        $this->assertTrue(Hash::check('long-demo-password', $user->password));
    }

    public function test_api_rate_limit_returns_too_many_requests_after_the_configured_limit(): void
    {
        config(['services.personnel_api_token' => 'rate-limit-token']);

        for ($request = 0; $request < 120; $request++) {
            $this->withToken('rate-limit-token')->getJson('/api/employees')->assertOk();
        }

        $this->withToken('rate-limit-token')->getJson('/api/employees')->assertTooManyRequests();
    }

    public function test_employee_names_are_rendered_as_text_not_html(): void
    {
        Employee::create([
            'first_name' => '<script>alert(1)</script>',
            'last_name' => 'Safety',
            'email' => 'xss@example.test',
            'department' => 'QA',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
