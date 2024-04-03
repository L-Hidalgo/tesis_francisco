<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Funcionario extends Model
{
    use HasFactory;

    protected $table = 'dde_funcionarios';

    protected $primaryKey = 'id_funcionario';

    protected $fillable = [
        'codigo_file_funcionario', //jalado de la planila
        'fch_inicio_sin_funcionario',
        'fch_fin_sin_funcionario',
        'fch_inicio_puesto_funcionario',
        'fch_fin_puesto_funcionario',
        'motivo_baja_funcionario',
        //'personal_antiguo_en_el_cargo',
        'puesto_id',
        'persona_id',
        //'estado',        
        //'creador_user_id',
        //'actualizador_user_id'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id', 'id');
    }

    public function puesto()
    {
        return $this->belongsTo(Puesto::class);
    }

}
