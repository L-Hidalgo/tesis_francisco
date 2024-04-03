<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogAccionUsuario extends Model
{
    use HasFactory;

    protected $table = 'dde_log_accion_usuarios';

    protected $primaryKey = 'id_log';

    protected $fillable = [
        'nombre_vista_log',
        'accion_persona_log',
        'usuario_id_log',
    ];

    protected $casts = [
        'fecha_hora_log' => 'datetime',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function Funcionario()
    {
        return $this->hasMany(Funcionario::class);
    }

    public function usuario()
    {
        return $this->hasOne(User::class, 'persona_id');
    }

    public function incorporaci_personaonFormulario()
    {
        return $this->hasMany(Incorporaci_personaon::class);
    }

    public function puestos_actuales()
    {
        return $this->hasMany(Puesto::class, 'persona_actual_id', 'id');
    }

    public function grado_academico()
    {
        return $this->belongsTo(GradoAcademico::class, 'grado_academico_id', 'id');
    }

    public function area_formaci_personaon()
    {
        return $this->belongsTo(AreaFormaci_personaon::class, 'area_formacion_id', 'id');
    }

    public function instituci_personaon()
    {
        return $this->belongsTo(Instituci_personaon::class, 'institucion_id', 'id');
    }
}
