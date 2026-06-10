<?php
// 2024_01_01_000002_create_doctors_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('specialite', 100);
            $table->string('numero_ordre', 50)->nullable();
            $table->string('diplome', 150)->nullable();
            $table->tinyInteger('experience_ans')->default(0);
            $table->decimal('consultation_fee', 10, 2)->default(0);
            $table->text('bio')->nullable();
            $table->json('horaires')->nullable(); // {"lundi":["09:00","17:00"]}
            $table->timestamps();
        });
 
        Schema::create('receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('poste', 100)->default("Agent d'accueil");
            $table->timestamps();
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('receptions');
        Schema::dropIfExists('doctors');
    }
};