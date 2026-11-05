<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.personnel_api_token' => 'test-service-token']);
        $this->actingAs(User::factory()->create());
    }

    public function test_employee_api_requires_a_configured_bearer_token(): void
    {
        $this->getJson('/api/employees')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthorized.');

        $this->withToken('wrong-token')
            ->getJson('/api/employees')
            ->assertUnauthorized();
    }

    public function test_empty_configured_token_never_authorizes_a_request(): void
    {
        config(['services.personnel_api_token' => '']);

        $this->withToken('anything')
            ->getJson('/api/employees')
            ->assertUnauthorized();
    }

    public function test_authorized_api_returns_employee_list_and_detail(): void
    {
        $employee = Employee::create([
            'first_name' => 'Ada',
            'last_name' => 'Yilmaz',
            'email' => 'ada@example.test',
            'department' => 'Engineering',
        ]);

        $this->withToken('test-service-token')
            ->getJson('/api/employees')
            ->assertOk()
            ->assertJsonPath('data.0.id', $employee->id)
            ->assertJsonPath('data.0.email', 'ada@example.test');

        $this->withToken('test-service-token')
            ->getJson('/api/employees/'.$employee->id)
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Ada');
    }

    public function test_employee_can_be_created_and_validation_rejects_invalid_data(): void
    {
        $this->post('/employees', [
            'first_name' => 'Deniz',
            'last_name' => 'Kaya',
            'email' => 'deniz@example.test',
            'department' => 'People',
        ])->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', ['email' => 'deniz@example.test']);

        $this->from('/employees/create')
            ->post('/employees', [
                'first_name' => '',
                'last_name' => 'Kaya',
                'email' => 'not-an-email',
                'department' => '',
            ])
            ->assertRedirect('/employees/create')
            ->assertSessionHasErrors(['first_name', 'email', 'department']);
    }

    public function test_employee_email_must_be_unique(): void
    {
        Employee::create([
            'first_name' => 'Ada',
            'last_name' => 'Yilmaz',
            'email' => 'ada@example.test',
            'department' => 'Engineering',
        ]);

        $this->from('/employees/create')
            ->post('/employees', [
                'first_name' => 'Ece',
                'last_name' => 'Demir',
                'email' => 'ada@example.test',
                'department' => 'Finance',
            ])
            ->assertRedirect('/employees/create')
            ->assertSessionHasErrors('email');
    }

    public function test_employee_can_be_edited_while_retaining_its_unique_email(): void
    {
        $employee = Employee::create([
            'first_name' => 'Ada',
            'last_name' => 'Yilmaz',
            'email' => 'ada@example.test',
            'department' => 'Mühendislik',
        ]);

        $this->get(route('employees.edit', $employee))
            ->assertOk()
            ->assertSee('Personel Düzenle');

        $this->put(route('employees.update', $employee), [
            'first_name' => 'Ada',
            'last_name' => 'Yilmaz',
            'email' => 'ada@example.test',
            'department' => 'Ürün',
        ])->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'department' => 'Ürün',
        ]);
    }
}
