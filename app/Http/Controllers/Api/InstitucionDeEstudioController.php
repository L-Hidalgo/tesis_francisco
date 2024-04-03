<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Instituci_personaon;
use Illuminate\Http\Request;

class Instituci_personaonDeEstudioController extends Controller
{
    public function listar()
    {
        $dde_instituci_personaonesDeEstudio = Instituci_personaon::select(['id', 'nombre'])->get();
        return $this->sendSuccess($dde_instituci_personaonesDeEstudio);
    }

    public function crear(Request $request)
    {
        try {
            $instituci_personaonDeEstudio = new Instituci_personaon();
            $instituci_personaonDeEstudio->nombre = $request->input('nombre');
            $instituci_personaonDeEstudio->save();

            return $this->sendSuccess($instituci_personaonDeEstudio);
        } catch (\Exception $e) {
            return $this->sendSuccess($e->getMessage());
        }
    }
}
