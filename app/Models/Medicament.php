<?php
// app/Models/Medicament.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
 
class Medicament extends Model
{
    public $timestamps = false;
    protected $fillable = ['ordonnance_id', 'nom', 'dosage', 'frequence', 'duree', 'instructions'];
    protected $casts = ['created_at' => 'datetime'];
 
    public function ordonnance() { return $this->belongsTo(Ordonnance::class); }
}