<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;

    protected $table = 'dde_personas';

    protected $primaryKey = 'id_persona';

    public $timestamps = true;

    protected $fillable = [
        'ci_persona',
        'exp_persona',
        'primer_apellido_persona',
        'segundo_apellido_persona',
        'nombre_persona',
        'profesion_persona',
        'genero_persona',
        'fch_nacimiento_persona',
        'telefono_persona',
        'fecha_inicio',
        'fecha_fin'
        //'imagen',
    ];

    protected $casts = [
        'fch_nacimiento_persona' => 'date',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function funcionario()
    {
        return $this->hasMany(Funcionario::class);
    }

    public function puestos_actuales()
    {
        return $this->hasMany(Puesto::class, 'persona_actual_id', 'id');
    }

    public function formacion()
    {
        return $this->hasMany(Formacion::class);
    }

    public function imagen()
    {
        return $this->hasMany(Imagen::class);
    }    

}
