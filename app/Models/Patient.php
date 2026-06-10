<?php
// app/Models/Patient.php
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 
class Patient extends Model
{
    use HasFactory;
 
    protected $fillable = [
        'nom', 'prenom', 'cin', 'date_naissance', 'sexe',
        'telephone', 'email', 'adresse', 'ville', 'groupe_sanguin',
        'description_maladie', 'antecedents', 'allergies',
        'assurance_nom', 'assurance_numero', 'medecin_traitant_id', 'is_active',
    ];
 
    protected $casts = [
        'date_naissance' => 'date',
        'is_active'      => 'boolean',
    ];
 
    // Full name accessor
    public function getFullNameAttribute(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }
 
    // Age accessor
    public function getAgeAttribute(): ?int
    {
        return $this->date_naissance?->age;
    }
 
    public function medecinTraitant() { return $this->belongsTo(Doctor::class, 'medecin_traitant_id'); }
    public function appointments()    { return $this->hasMany(Appointment::class); }
    public function consultations()   { return $this->hasMany(Consultation::class); }
    public function ordonnances()     { return $this->hasMany(Ordonnance::class); }
    public function paiements()       { return $this->hasMany(Paiement::class); }
}
 