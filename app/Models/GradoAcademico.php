<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradoAcademico extends Model
{
    use HasFactory;

    protected $table = 'dde_grado_academicos';

    protected $primaryKey = 'id_grado_academico';

    protected $fillable = [
        'nombre_grado_academico',
        'fecha_inicio',
        'fecha_fin',
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
