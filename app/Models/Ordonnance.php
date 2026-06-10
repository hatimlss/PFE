<?php
// app/Models/Ordonnance.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 
class Ordonnance extends Model
{
    use HasFactory;
 
    protected $fillable = [
        'consultation_id', 'patient_id', 'doctor_id',
        'date_emission', 'validite_jours', 'instructions_generales',
    ];
 
    protected $casts = ['date_emission' => 'date'];
 
    public function consultation() { return $this->belongsTo(Consultation::class); }
    public function patient()      { return $this->belongsTo(Patient::class); }
    public function doctor()       { return $this->belongsTo(Doctor::class); }
    public function medicaments()  { return $this->hasMany(Medicament::class); }
 
    public function isExpired(): bool
    {
        return $this->date_emission->addDays($this->validite_jours)->isPast();
    }
}