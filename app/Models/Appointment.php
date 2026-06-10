<?php
// app/Models/Appointment.php
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 
class Appointment extends Model
{
    use HasFactory;
 
    protected $fillable = [
        'patient_id', 'doctor_id', 'reception_id',
        'date_rdv', 'heure_debut', 'heure_fin',
        'motif', 'status', 'type',
        'notes_reception', 'notes_doctor',
    ];
 
    protected $casts = [
        'date_rdv' => 'date',
    ];
 
    public function patient()   { return $this->belongsTo(Patient::class); }
    public function doctor()    { return $this->belongsTo(Doctor::class); }
    public function reception() { return $this->belongsTo(Reception::class); }
    public function consultation() { return $this->hasOne(Consultation::class); }
    public function paiement()  { return $this->hasOne(Paiement::class); }
 
    // Scope: upcoming appointments
    public function scopeUpcoming($query)
    {
        return $query->where('date_rdv', '>=', now()->toDateString())
                     ->whereIn('status', ['en_attente', 'confirme'])
                     ->orderBy('date_rdv')->orderBy('heure_debut');
    }
 
    // Scope: by doctor
    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }
 
    // Scope: by date range
    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween('date_rdv', [$from, $to]);
    }
}