<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formacion extends Model
{
    use HasFactory;

    protected $table = 'dde_formaciones';

    protected $primaryKey = 'id_formacion';

    protected $fillable = [
        'persona_id',
        'institucion_id',
        'grado_academico_id',
        'area_formacion_id',
        'gestion_formacion',
        'estado_formacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function gradoAcademico()
    {
        return $this->belongsTo(GradoAcademico::class);
    }

    public function areaFormacion()
    {
        return $this->belongsTo(AreaFormacion::class);
    }

    public function institucion()
    {
        return $this->belongsTo(Institucion::class);
    }
}
