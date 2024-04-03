<?php

use App\Http\Controllers\Controller;
use App\Models\Persona;

class PersonaController extends Controller
{
    public function mostrarDatosEnTabla()
    {
        $personas = Persona::with('puestoPersona.puesto')->get();
        return view('tu_vista', compact('personas'));
    }
}
