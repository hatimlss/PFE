<?php
// app/Models/Consultation.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 
class Consultation extends Model
{
    use HasFactory;
 
    protected $fillable = [
        'appointment_id', 'patient_id', 'doctor_id',
        'diagnostic', 'symptomes', 'traitement',
        'tension', 'poids', 'taille', 'temperature',
        'observations', 'prochain_rdv',
    ];
 
    protected $casts = [
        'prochain_rdv' => 'date',
        'poids'        => 'decimal:2',
        'taille'       => 'decimal:2',
        'temperature'  => 'decimal:1',
    ];
 
    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function patient()     { return $this->belongsTo(Patient::class); }
    public function doctor()      { return $this->belongsTo(Doctor::class); }
    public function ordonnances() { return $this->hasMany(Ordonnance::class); }
}