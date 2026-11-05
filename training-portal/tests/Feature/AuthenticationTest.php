<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\TrainingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertOk();

        $this->post('/logout')->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
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

        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_training_management_and_assignment_mutations_require_authentication(): void
    {
        $training = Training::create(['title' => 'QA', 'description' => 'Isolated']);
        $assignment = TrainingAssignment::create([
            'training_id' => $training->id,
            'personnel_id' => 88,
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        $this->get(route('trainings.index'))->assertRedirect(route('login'));
        $this->post(route('trainings.store'), ['title' => 'No auth', 'description' => 'Blocked'])
            ->assertRedirect(route('login'));
        $this->post(route('trainings.assignments.store', $training), ['personnel_id' => 88])
            ->assertRedirect(route('login'));
        $this->patch(route('assignments.complete', $assignment))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('trainings', ['title' => 'No auth']);
        $this->assertDatabaseHas('training_assignments', ['id' => $assignment->id, 'status' => 'pending']);
    }

    public function test_assignment_api_requires_authentication_and_does_not_allow_cors_origins(): void
    {
        $training = Training::create(['title' => 'QA', 'description' => 'Isolated']);
        $assignment = TrainingAssignment::create([
            'training_id' => $training->id,
            'personnel_id' => 89,
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        $this->withHeader('Origin', 'https://attacker.example')
            ->getJson('/api/assignments/'.$assignment->id)
            ->assertUnauthorized()
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_login_errors_and_invalid_form_referers_stay_on_the_app(): void
    {
        $this->withHeader('Referer', 'https://attacker.example/login')
            ->post('/login', ['email' => 'invalid', 'password' => 'x'])
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->withHeader('Referer', 'https://attacker.example/trainings')
            ->post('/trainings', ['title' => '', 'description' => ''])
            ->assertRedirect(route('trainings.create'));
    }

    public function test_same_host_referer_on_an_untrusted_port_uses_the_canonical_app_url(): void
    {
        $this->actingAs(User::factory()->create())
            ->withHeader('Referer', 'http://localhost:9999/trainings/create')
            ->post('/trainings', ['title' => '', 'description' => ''])
            ->assertRedirect(route('trainings.create'));
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

    public function test_training_output_escapes_personnel_data(): void
    {
        $training = Training::create(['title' => 'XSS check', 'description' => 'Escaping']);
        TrainingAssignment::create([
            'training_id' => $training->id,
            'personnel_id' => 91,
            'status' => 'pending',
            'assigned_at' => now(),
        ]);
        Http::fake([
            '*' => Http::response(['data' => [[
                'id' => 91,
                'first_name' => '<script>alert(1)</script>',
                'last_name' => 'Employee',
                'email' => 'xss@example.test',
                'department' => 'QA',
            ]]], 200),
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('trainings.show', $training))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
