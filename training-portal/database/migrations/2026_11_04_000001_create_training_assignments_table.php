<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('personnel_id');
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamp('assigned_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['training_id', 'personnel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_assignments');
    }
};
