<?php
// app/Models/Doctor.php
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 
class Doctor extends Model
{
    use HasFactory;
 
    protected $fillable = [
        'user_id', 'specialite', 'numero_ordre', 'diplome',
        'experience_ans', 'consultation_fee', 'bio', 'horaires',
    ];
 
    protected $casts = [
        'horaires' => 'array',
        'consultation_fee' => 'decimal:2',
    ];
 
    public function user()        { return $this->belongsTo(User::class); }
    public function patients()    { return $this->hasMany(Patient::class, 'medecin_traitant_id'); }
    public function appointments(){ return $this->hasMany(Appointment::class); }
    public function consultations(){ return $this->hasMany(Consultation::class); }
    public function ordonnances() { return $this->hasMany(Ordonnance::class); }
 
    // Shortcut to get doctor's name from user relation
    public function getFullNameAttribute(): string
    {
        return 'Dr. ' . $this->user->name;
    }
}
 