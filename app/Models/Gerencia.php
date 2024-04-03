<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gerencia extends Model
{
    use HasFactory;

    protected $table = 'dde_gerencias';

    protected $primaryKey = 'id_gerencia';

    protected $fillable = [
        'nombre_gerencia',
        'abreviatura_gerencia',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function departamento()
    {
        return $this->hasMany(Departamento::class);
    }

}
