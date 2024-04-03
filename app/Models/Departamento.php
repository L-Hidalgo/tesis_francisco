<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;

    protected $table = 'dde_personas';

    protected $primaryKey = 'id_departamento';

    protected $fillable = [
        'nombre_departamento',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function puesto()
    {
        return $this->hasMany(Puesto::class);
    }

    public function Gerencia() {
        return $this->belongsTo(Gerencia::class);
    }

}


