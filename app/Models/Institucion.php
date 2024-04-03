<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institucion extends Model
{
    use HasFactory;

    protected $table = "dde_instituciones";

    protected $primaryKey = 'id_institucion';

    protected $fillable = [
        'nombre_institucion',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];
    
    public function formacion()
    {
        return $this->hasMany(Formacion::class);
    }

}
