<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Puesto;

class PuestoController extends Controller
{
    public function getList() {
        $puestos = Puesto::select(['denominacion_puesto', 'item_puesto', 'id'])->get();
        return $this->sendSuccess($puestos);
    }

    public function getById($puestoId) {
        $puesto = Puesto::with(['persona_actual'])->select(['denominacion_puesto', 'item_puesto', 'id','persona_actual_id'])->find($puestoId);
        return $this->sendSuccess($puesto);
    }

    public function getByitem_puesto($item_puesto) {
        $puesto = Puesto::with(['persona_actual:id,nombre_completo,nombre_persona,primer_apellido_persona,segundo_apellido_persona,ci_persona,exp_persona,genero_persona'])->select(['denominacion_puesto', 'item_puesto', 'id','persona_actual_id'])->where('item_puesto', $item_puesto)->first();
        return $this->sendSuccess($puesto);
    }
}
