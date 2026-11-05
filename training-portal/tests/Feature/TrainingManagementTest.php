<?php

namespace Tests\Feature;

use App\Models\Training;
use App\Models\TrainingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrainingManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.personnel_service.url' => 'https://personnel.example.test',
            'services.personnel_service.token' => 'test-integration-token',
        ]);
        $this->actingAs(User::factory()->create());
    }

    public function test_training_can_be_created(): void
    {
        $this->post('/trainings', [
            'title' => 'Bilgi Güvenliği',
            'description' => 'Temel güvenlik ilkeleri.',
        ])->assertRedirect();

        $this->assertDatabaseHas('trainings', [
            'title' => 'Bilgi Güvenliği',
            'description' => 'Temel güvenlik ilkeleri.',
        ]);
        $this->assertFalse(Schema::hasTable('employees'));
    }

    public function test_existing_personnel_can_be_assigned_using_the_server_side_api(): void
    {
        $training = Training::create([
            'title' => 'Güvenlik',
            'description' => 'Güvenli çalışma.',
        ]);
        Http::fake([
            'https://personnel.example.test/api/employees/7' => Http::response([
                'data' => $this->employee(7),
            ], 200),
        ]);

        $this->post(route('trainings.assignments.store', $training), ['personnel_id' => 7])
            ->assertRedirect(route('trainings.show', $training));

        $this->assertDatabaseHas('training_assignments', [
            'training_id' => $training->id,
            'personnel_id' => 7,
            'status' => 'pending',
        ]);
        Http::assertSent(fn (ClientRequest $request): bool => $request->hasHeader('Authorization', 'Bearer test-integration-token')
            && $request->url() === 'https://personnel.example.test/api/employees/7'
        );
    }

    public function test_the_same_training_cannot_be_assigned_twice_to_one_person(): void
    {
        $training = Training::create([
            'title' => 'İletişim',
            'description' => 'İletişim becerileri.',
        ]);
        TrainingAssignment::create([
            'training_id' => $training->id,
            'personnel_id' => 5,
            'status' => 'pending',
            'assigned_at' => now(),
        ]);
        Http::fake();

        $this->from(route('trainings.show', $training))
            ->post(route('trainings.assignments.store', $training), ['personnel_id' => 5])
            ->assertRedirect(route('trainings.show', $training))
            ->assertSessionHasErrors('personnel_id');

        $this->assertDatabaseCount('training_assignments', 1);
        Http::assertNothingSent();
    }

    public function test_assignment_completion_records_status_and_timestamp(): void
    {
        $training = Training::create([
            'title' => 'Oryantasyon',
            'description' => 'Başlangıç programı.',
        ]);
        $assignment = TrainingAssignment::create([
            'training_id' => $training->id,
            'personnel_id' => 9,
            'status' => 'pending',
            'assigned_at' => now()->subDay(),
        ]);

        $this->patch(route('assignments.complete', $assignment))
            ->assertRedirect(route('trainings.show', $training));

        $this->assertDatabaseHas('training_assignments', [
            'id' => $assignment->id,
            'status' => 'completed',
        ]);
        $this->assertNotNull($assignment->fresh()->completed_at);
    }

    public function test_assignment_status_api_reflects_completion_and_has_no_employee_table(): void
    {
        $training = Training::create([
            'title' => 'Oryantasyon',
            'description' => 'Başlangıç programı.',
        ]);
        $assignment = TrainingAssignment::create([
            'training_id' => $training->id,
            'personnel_id' => 17,
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        $this->getJson('/api/assignments/'.$assignment->id)
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.completed_at', null);

        $this->patch(route('assignments.complete', $assignment))
            ->assertRedirect(route('trainings.show', $training));

        $this->getJson('/api/assignments/'.$assignment->id)
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.personnel_id', 17)
            ->assertJsonPath('data.completed_at', $assignment->fresh()->completed_at->toISOString());

        $this->assertFalse(Schema::hasTable('employees'));
    }

    public function test_connection_failure_is_shown_as_a_controlled_error(): void
    {
        $training = Training::create([
            'title' => 'İSG',
            'description' => 'İş sağlığı ve güvenliği.',
        ]);
        Http::fake(fn () => throw new ConnectionException('Connection refused.'));

        $this->from(route('trainings.show', $training))
            ->post(route('trainings.assignments.store', $training), ['personnel_id' => 12])
            ->assertRedirect(route('trainings.show', $training))
            ->assertSessionHasErrors([
                'personnel_id' => 'Personel Servisi şu anda erişilemiyor. Lütfen daha sonra tekrar deneyin.',
            ]);

        $this->assertDatabaseCount('training_assignments', 0);
    }

    public function test_nonexistent_personnel_is_not_assigned(): void
    {
        $training = Training::create([
            'title' => 'İSG',
            'description' => 'İş sağlığı ve güvenliği.',
        ]);
        Http::fake([
            'https://personnel.example.test/api/employees/23' => Http::response([
                'message' => 'Not found.',
            ], 404),
        ]);

        $this->from(route('trainings.show', $training))
            ->post(route('trainings.assignments.store', $training), ['personnel_id' => 23])
            ->assertRedirect(route('trainings.show', $training))
            ->assertSessionHasErrors([
                'personnel_id' => 'Seçilen personel Personel Servisi içinde bulunamadı.',
            ]);

        $this->assertDatabaseCount('training_assignments', 0);
    }

    public function test_personnel_api_server_error_does_not_hide_assignment_history(): void
    {
        $training = Training::create([
            'title' => 'Oryantasyon',
            'description' => 'Başlangıç programı.',
        ]);
        TrainingAssignment::create([
            'training_id' => $training->id,
            'personnel_id' => 8,
            'status' => 'pending',
            'assigned_at' => now()->subDay(),
        ]);
        Http::fake([
            'https://personnel.example.test/api/employees' => Http::response([
                'message' => 'Unavailable.',
            ], 500),
        ]);

        $this->get(route('trainings.show', $training))
            ->assertOk()
            ->assertSee('Personel servisine şu anda ulaşılamıyor.')
            ->assertSee('Personel bilgisi şu anda alınamıyor')
            ->assertSee('Bekliyor');
    }

    public function test_deleted_personnel_is_marked_unavailable_in_assignment_history(): void
    {
        $training = Training::create([
            'title' => 'İletişim',
            'description' => 'Etkili iletişim.',
        ]);
        TrainingAssignment::create([
            'training_id' => $training->id,
            'personnel_id' => 999,
            'status' => 'completed',
            'assigned_at' => now()->subWeek(),
            'completed_at' => now()->subDays(5),
        ]);
        Http::fake([
            'https://personnel.example.test/api/employees' => Http::response(['data' => []], 200),
        ]);

        $this->get(route('trainings.show', $training))
            ->assertOk()
            ->assertSee('Personel bilgisi artık erişilebilir değil')
            ->assertSee('Tamamlandı');
    }

    public function test_malformed_personnel_api_payload_is_not_used_as_employee_data(): void
    {
        $training = Training::create([
            'title' => 'Güvenlik',
            'description' => 'Güvenli çalışma.',
        ]);
        Http::fake([
            'https://personnel.example.test/api/employees/7' => Http::response([
                'data' => ['id' => 7, 'first_name' => 'Ada'],
            ], 200),
        ]);

        $this->from(route('trainings.show', $training))
            ->post(route('trainings.assignments.store', $training), ['personnel_id' => 7])
            ->assertRedirect(route('trainings.show', $training))
            ->assertSessionHasErrors('personnel_id');

        $this->assertDatabaseCount('training_assignments', 0);
    }

    /**
     * @return array{id: int, first_name: string, last_name: string, email: string, department: string}
     */
    private function employee(int $id): array
    {
        return [
            'id' => $id,
            'first_name' => 'Ada',
            'last_name' => 'Yilmaz',
            'email' => 'ada@example.test',
            'department' => 'Engineering',
        ];
    }
}
