<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AreaFormaci_personaon;
use App\Models\GradoAcademico;
use Illuminate\Http\Request;

class GradoAcademicoController extends Controller
{
    public function listar()
    {
        $gradosAcademicos = GradoAcademico::select(['id', 'nombre'])->get();
        return $this->sendSuccess($gradosAcademicos);
    }

    public function crear(Request $request)
    {
        try {
            $gradoAcademico = new GradoAcademico();
            $gradoAcademico->nombre = $request->input('nombre');
            $gradoAcademico->save();

            return $this->sendSuccess($gradoAcademico);
        } catch (\Exception $e) {
            return $this->sendSuccess($e->getMessage());
        }
    }
}
