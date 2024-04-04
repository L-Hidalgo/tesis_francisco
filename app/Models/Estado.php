<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    use HasFactory;

    protected $table = 'dde_estados';

    protected $primaryKey = 'id_estado';

    protected $fillable = [
        'nombre_estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function puesto() {
        return $this->belongsTo(Puesto::class);
    }

}
