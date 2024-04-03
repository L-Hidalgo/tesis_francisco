<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incorporacion extends Model
{
    use HasFactory;

    protected $table = 'dde_incorporaciones';

    protected $primaryKey = 'id_incorporacion';

    protected $fillable = [
        // Section: Evaluaci_personaon
        'paso_incorporacion',
        'persona_id',
        'puesto_actual_id',
        'puesto_nevo_id',
        'evaluacion_estado_incorporacion', // 1:inici_personao, 2: con_formulario, 3: cumple, 4: no_cumple, finalizado
        // !Section
        // Section: Incoporaci_personaon
        'estado_incorporacion',
        'gerente_acta_posicion_incorporacion',
        //'seguimiento_estado',
        //'respaldo_formaci_personaon',
        'cumple_exp_profesional_incorporacion',
        'cumple_exp_especifica_incorporacion',
        'cumple_exp_mando_incorporacion',
        'cumple_formacion_incorporacion',
        'fch_incorporacion',
        'hp_incorporacion',
        'cite_nota_minuta_incorporacion',
        'codigo_nota_minuta_incorporacion',
        'fch_nota_minuta_incorporacion',
        'fch_recepcion_nota_incorporacion',
        'cite_informe_incorporacion',
        'fch_informe_incorporacion',
        'cite_memorandum_incorporacion',
        'codigo_memorandum_incorporacion',
        'fch_memorandum_incorporacion',
        'cite_rap_incorporacion',
        'codigo_rap_incorporacion',
        'fch_rap_incorporacion',
        //'responsable',
        'observacion_incorporacion'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];
    
    public function puesto_actual()
    {
        return $this->belongsTo(Puesto::class, 'puesto_actual_id', 'id');
    }

    public function puesto_nuevo()
    {
        return $this->belongsTo(Puesto::class, 'puesto_nuevo_id', 'id');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id', 'id');
    }

}
