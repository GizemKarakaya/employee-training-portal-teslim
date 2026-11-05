<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Ada', 'Yilmaz', 'ada.yilmaz@example.test', 'Mühendislik'],
            ['Deniz', 'Kaya', 'deniz.kaya@example.test', 'İnsan Kaynakları'],
            ['Ece', 'Demir', 'ece.demir@example.test', 'Finans'],
            ['Mert', 'Arslan', 'mert.arslan@example.test', 'Operasyon'],
            ['Selin', 'Celik', 'selin.celik@example.test', 'Pazarlama'],
            ['Can', 'Aydin', 'can.aydin@example.test', 'Müşteri Desteği'],
        ] as [$firstName, $lastName, $email, $department]) {
            Employee::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'department' => $department,
                ],
            );
        }
    }
}
