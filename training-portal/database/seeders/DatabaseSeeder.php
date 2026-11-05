<?php

namespace Database\Seeders;

use App\Models\Training;
use App\Models\TrainingAssignment;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $trainingIds = [];

        foreach ([
            'İş Sağlığı ve Güvenliği' => 'İş yerinde güvenli çalışma ilkeleri ve temel iş sağlığı kuralları.',
            'Bilgi Güvenliği Farkındalığı' => 'Kurumsal verileri güvenli biçimde korumaya yönelik temel farkındalık.',
            'Yeni Personel Oryantasyonu' => 'Kuruma yeni katılan çalışanlar için süreç ve çalışma biçimi tanıtımı.',
            'Kurumsal İletişim Eğitimi' => 'İş ortamında etkili ve yapıcı iletişim yöntemleri.',
        ] as $title => $description) {
            $training = Training::updateOrCreate(
                ['title' => $title],
                ['description' => $description],
            );
            $trainingIds[$title] = $training->id;
        }

        $now = now();

        foreach ([
            ['title' => 'İş Sağlığı ve Güvenliği', 'personnel_id' => 1, 'status' => 'completed', 'days_ago' => 35],
            ['title' => 'Bilgi Güvenliği Farkındalığı', 'personnel_id' => 1, 'status' => 'pending', 'days_ago' => 12],
            ['title' => 'Yeni Personel Oryantasyonu', 'personnel_id' => 2, 'status' => 'completed', 'days_ago' => 24],
            ['title' => 'Kurumsal İletişim Eğitimi', 'personnel_id' => 3, 'status' => 'pending', 'days_ago' => 5],
        ] as $assignment) {
            $assignedAt = $now->copy()->subDays($assignment['days_ago']);

            TrainingAssignment::updateOrCreate(
                [
                    'training_id' => $trainingIds[$assignment['title']],
                    'personnel_id' => $assignment['personnel_id'],
                ],
                [
                    'status' => $assignment['status'],
                    'assigned_at' => $assignedAt,
                    'completed_at' => $assignment['status'] === 'completed' ? $assignedAt->copy()->addDays(2) : null,
                ],
            );
        }
    }
}
