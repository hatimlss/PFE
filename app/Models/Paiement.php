<?php
// app/Models/Paiement.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 
class Paiement extends Model
{
    use HasFactory;
 
    protected $fillable = [
        'patient_id', 'appointment_id', 'montant',
        'mode_paiement', 'status', 'reference',
        'date_paiement', 'notes', 'created_by',
    ];
 
    protected $casts = [
        'montant'        => 'decimal:2',
        'date_paiement'  => 'date',
    ];
 
    public function patient()     { return $this->belongsTo(Patient::class); }
    public function appointment() { return $this->belongsTo(Appointment::class); }
    public function createdBy()   { return $this->belongsTo(User::class, 'created_by'); }
}