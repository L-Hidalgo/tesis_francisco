<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Puesto;
use Illuminate\Support\Facades\DB;

class FuncionarioController extends Controller
{
    public function listarPuesto(Request $request)
    {
        $limit = 9;
        $page = $request->input('page', 1);

        // Filtros
        $item_puesto = $request->input('item_puesto');
        $GerenciasIds = $request->input('GerenciasIds');
        $departamentosIds = $request->input('departamentosIds');
        $estado = $request->input('estado');
        $tipoMovimiento = $request->input('tipoMovimiento');

        $query = DB::table('puestos')
            ->join('departamentos', 'puestos.departamento_id', '=', 'departamentos.id')
            ->join('Gerencias', 'departamentos.Gerencia_id', '=', 'Gerencias.id')
            ->leftJoin('requisitos', 'puestos.id', '=', 'requisitos.puesto_id')
            ->leftJoin('personas_puestos', 'puestos.id', '=', 'personas_puestos.puesto_id')
            ->leftJoin('personas', 'personas.id', '=', 'puestos.persona_actual_id');

        if (isset($item_puesto)) {
            $query = $query->where('puestos.item_puesto', $item_puesto);
        }
        if (isset($departamentosIds) && count($departamentosIds) > 0) {
            $query = $query->whereIn('departamentos.id', $departamentosIds);
        }
        if (isset($GerenciasIds) && count($GerenciasIds) > 0) {
            $query = $query->whereIn('departamentos.Gerencia_id', $GerenciasIds);
        }
        if (isset($estado)) {
            $query = $query->where('puestos.estado', $estado);
        }

        $query = $query->select([
            'personas.ci_persona',
            'personas.exp_persona',
            'personas.nombre_completo',
            'personas.formaci_personaon',
            'personas.fch_nacimiento_persona',
            'personas.fch_inicio_puesto_funcionarion_sin',
            'personas_puestos.fch_inicio_puesto_funcionario as fch_inicio_puesto_funcionario',
            'personas.imagen',
            'puestos.id',
            'puestos.item_puesto',
            'puestos.denominacion_puesto',
            'puestos.estado',
            'puestos.salario_puesto',
            'Gerencias.nombre as Gerencia',
            'departamentos.nombre as departamento',
            'puestos.objetivo_puesto',
            'requisitos.formacion_requisito as formacion_requisito',
            'requisitos.exp_cargo_requisito as exp_cargo_requisito',
            'requisitos.exp_area_requisito as exp_area_requisito',
            'requisitos.exp_mando_requisito as exp_mando_requisito',
            'puestos.persona_actual_id'
        ]);

        $query = $query->orderBy('puestos.item_puesto');

        // paginaci_personaon
        $Funcionarios = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json($Funcionarios);
    }


    public function obtenerInfoDeFuncionario($puestoId)
    {
        $Funcionario = Puesto::with(['persona_actual', 'departamento.Gerencia', 'requisitos', 'Funcionario'])->find($puestoId);

        return response()->json($Funcionario);
    }

    public function filtrarAutoComplete(Request $request)
    {
        $keyword = $request->input('keyword', '');
        $result = DB::table('puestos')
            ->leftJoin('personas', 'personas.id', '=', 'puestos.persona_actual_id')
            ->orWhere(DB::raw('CAST(puestos.item_puesto AS CHAR)'), 'LIKE', $keyword . "%")
            ->orWhere('personas.nombre_completo', 'LIKE', $keyword . "%")
            ->select(['puestos.item_puesto as item_puesto', 'personas.nombre_completo as nombre_completo'])
            ->limit(6)->get();
        $results = [];
        if (ctype_digit($keyword)) {
            $results = $result->map(function ($obj) {
                return (object) ['text' => "" . $obj->item_puesto . ": " . ($obj->nombre_completo ? $obj->nombre_completo : "ACEFALIA"), 'item_puesto' => $obj->item_puesto];
            });
        } else {
            $results = $result->map(function ($obj) {
                return (object) ['text' => $obj->nombre_completo . " [" . $obj->item_puesto . "]", 'item_puesto' => $obj->item_puesto];
            });
        }
        return response()->json(['elementos' => $results], 200);
    }
}
