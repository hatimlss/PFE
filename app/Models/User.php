<?php
// app/Models/User.php
namespace App\Models;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
 
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
 
    protected $fillable = ['name', 'email', 'password', 'role', 'avatar', 'phone', 'is_active'];
 
    protected $hidden = ['password', 'remember_token'];
 
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];
 
    // Relationships
    public function doctor(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Doctor::class);
    }
 
    public function reception(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Reception::class);
    }
 
    // Role helpers
    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isDoctor(): bool   { return $this->role === 'doctor'; }
    public function isReception(): bool{ return $this->role === 'reception'; }
}