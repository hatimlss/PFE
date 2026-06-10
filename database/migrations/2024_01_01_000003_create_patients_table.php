<?php
// 2024_01_01_000003_create_patients_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 80);
            $table->string('prenom', 80);
            $table->string('cin', 20)->unique();
            $table->date('date_naissance')->nullable();
            $table->enum('sexe', ['M', 'F', 'autre'])->default('M');
            $table->string('telephone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('adresse')->nullable();
            $table->string('ville', 80)->nullable();
            $table->enum('groupe_sanguin', ['A+','A-','B+','B-','AB+','AB-','O+','O-'])->nullable();
            $table->text('description_maladie')->nullable();
            $table->text('antecedents')->nullable();
            $table->text('allergies')->nullable();
            $table->string('assurance_nom', 100)->nullable();
            $table->string('assurance_numero', 100)->nullable();
            $table->foreignId('medecin_traitant_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};