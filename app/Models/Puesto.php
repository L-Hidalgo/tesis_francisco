<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Puesto extends Model
{
    use HasFactory;

    protected $table = 'dde_puestos';

    protected $primaryKey = 'id_puesto';

    protected $fillable = [
        'item_puesto',
        'denominacion_puesto',
        'objetivo_puesto',
        'salario_puesto',
        'salario_literal_puesto',
        'departamento_id',
        'persona_actual_id'
        //'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function funcionario()
    {
        return $this->hasMany(Funcionario::class, 'puesto_id', 'id');
    }

    public function estado()
    {
        return $this->hasMany(Estado::class);
    }

    public function requisitos()
    {
        return $this->hasMany(Requisito::class);
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }

    public function incorporacion()
    {
        return $this->hasMany(Incorporacion::class);
    }

    public function persona_actual()
    {
        return $this->belongsTo(Persona::class, 'persona_actual_id', 'id');
    }
}
