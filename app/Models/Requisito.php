<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requisito extends Model
{
    use HasFactory;

    protected $table = 'dde_requisitos';

    protected $primaryKey = 'id_requisito';

    protected $fillable = [
        'formacion_requisito',
        'exp_cargo_requisito',
        'exp_area_requisito',
        'exp_mando_requisito',
        'puesto_id'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];
    
    public function puesto()
    {
        return $this->belongsTo(Puesto::class);
    }

}
