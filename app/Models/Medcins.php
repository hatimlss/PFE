<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class medcins extends Model
{
    protected $fillable =[
        "nom",
        "prenom", 
        "Specialite",
        "departement",
        "N_classe",
    ];
}
