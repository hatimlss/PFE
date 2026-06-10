<?php namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 
// ─── Reception ────────────────────────────────────────────────────────────────
class Reception extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'poste'];
 
    public function user()        { return $this->belongsTo(User::class); }
    public function appointments(){ return $this->hasMany(Appointment::class); }
}
 