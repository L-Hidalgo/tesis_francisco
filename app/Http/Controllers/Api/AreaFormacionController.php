<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AreaFormaci_personaon;
use Illuminate\Http\Request;

class AreaFormaci_personaonController extends Controller
{
    public function listar()
    {
        $areasFormaci_personaon = AreaFormaci_personaon::select(['id', 'nombre'])->get();
        return $this->sendSuccess($areasFormaci_personaon);
    }

    public function crearAreaFormaci_personaon(Request $request)
    {
        try {
            $AreaFormaci_personaon = new AreaFormaci_personaon();
            $AreaFormaci_personaon->nombre = $request->input('nombre');
            $AreaFormaci_personaon->save();

            return $this->sendSuccess($AreaFormaci_personaon);
        } catch (\Exception $e) {
            return $this->sendSuccess($e->getMessage());
        }
    }
}
