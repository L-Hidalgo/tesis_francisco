<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;

    protected $table = 'dde_personas';

    protected $primaryKey = 'id_persona';

    protected $fillable = [
        'ci_persona',
        'exp_persona',
        'primer_apellido_persona',
        'segundo_apellido_persona',
        'nombre_persona',
        'profesion_persona',
        //'formaci_personaon',
        //'grado_academico_id',
        //'area_formacion_id',
        //'institucion_id',
        //'anio_conclusion',
        //'con_respaldo',
        'genero_persona',
        'fch_nacimiento_persona',
        'telefono_persona',
        //'fch_inicio_puesto_funcionarion_sin',
        //'imagen',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function formacion()
    {
        return $this->hasMany(Formacion::class);
    }

    public function imagen()
    {
        return $this->hasMany(Imagen::class);
    }

    public function funcionario()
    {
        return $this->hasMany(Funcionario::class);
    }











    
   

   
    public function incorporaci_personaonFormulario()
    {
        return $this->hasMany(Incorporaci_personaon::class);
    }

    public function puestos_actuales()
    {
        return $this->hasMany(Puesto::class, 'persona_actual_id', 'id');
    }

    

}
