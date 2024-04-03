<?php

namespace App\Http\Controllers;

use App\Models\Incorporaci_personaon;
use App\Models\AreaFormaci_personaon;
use App\Models\GradoAcademico;
use App\Models\Instituci_personaon;
use App\Models\Persona;
use App\Models\Puesto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Incorporaci_personaonesController extends Controller
{

    public function listarIncorporaci_personaones(Request $request)
    {
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);

        //Mostrar datos o listar datos
        $paso_incorporacion = $request->input('paso_incorporacion');
        $personaId = $request->input('personaId');
        $GerenciasIds = $request->input('GerenciasIds');
        $departamentosIds = $request->input('departamentosIds');
        $estado = $request->input('estado');

        $query = Incorporaci_personaon::with([
            'persona',
            'persona.incorporaci_personaonFormulario',
            'puesto_actual.departamento.Gerencia',
            'puesto_nuevo.departamento.Gerencia',
            'puesto_nuevo.persona_actual',
            'puesto_nuevo.Funcionario.persona',
            'puesto_nuevo.requisitos'
        ]);

        if (isset($personaId)) {
            $query = $query->where('persona_id', $personaId);
        }
        if (isset($paso_incorporacion)) {
            $query = $query->where('paso_incorporacion', $paso_incorporacion);
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

        // $query = $query->whereNotNull('puesto_actual_id')->whereNotNull('puesto_nuevo_id');

        $query->orderBy('created_at', 'desc');

        // Paginaci_personaon de incorporaci_personaones
        $incorporaci_personaones = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json($incorporaci_personaones);
    }

    public function filtrarAutoComplete(Request $request)
    {
        $keyword = $request->input('keyword', '');
        $result = DB::table('incorporaci_personaon_formularios')
            ->leftJoin('personas', 'incorporaci_personaon_formularios.persona_id', '=', 'personas.id')
            ->orWhere('personas.nombreCompleto', 'LIKE', $keyword . "%")
            ->orWhere('personas.ci_persona', 'LIKE', $keyword . "%")
            ->select(['personas.id as idPersona', 'personas.nombreCompleto as nombreCompleto', 'personas.ci_persona as ci_persona'])
            ->limit(6)->get();
        $results = [];
        if (ctype_digit($keyword)) {
            $results = $result->map(function ($obj) {
                return (object) ['text' => "" . $obj->ci_persona . ": " . $obj->nombreCompleto, 'idPersona' => $obj->idPersona];
            });
        } else {
            $results = $result->map(function ($obj) {
                return (object) ['text' => $obj->nombreCompleto . " [" . $obj->ci_persona . "]", 'idPersona' => $obj->idPersona];
            });
        }
        return response()->json(['elementos' => $results], 200);
    }

    //Crear evaluaci_personaon para cambio de item_puesto
    public function crearEvaluaci_personaon(Request $request)
    {
        $validatedData = $request->validate([
            'persona_id' => 'integer',
            'persona.nombre_persona' => 'string|nullable',  // Hacer el campo opci_personaonal y nulo
            'persona.primer_apellido_persona' => 'string',
            'persona.segundo_apellido_persona' => 'string',
            'persona.ci_persona' => 'string',
            'persona.exp_persona' => 'string',
            'persona.genero_persona' => 'string',
            'persona.fch_nacimiento_persona' => 'date',
            'puesto_actual_id' => 'integer',
            'puesto_nuevo_id' => 'required|integer',
            'observacion_incorporacion' => 'required|string',
        ]);

        // if (empty($request->gradoAcademico_id) || empty($request->areaFormaci_personaon_id) || empty($request->institucion_id) || empty($request->anioConclusion) || empty($request->observacion_incorporacion)) {
        //     return response()->json(['error' => 'Campos requeridos vacíos'], 400);
        // }
        if (!empty($request->persona_id)) {
            $persona = Persona::find($validatedData['persona_id']);
        } else {
            $persData = $validatedData['persona'];
            $fechaNaci_personamiento = isset($persData['fch_nacimiento_persona']) ? Carbon::parse($persData['fch_nacimiento_persona'])->toDateString() : null;

            $persona = Persona::create([
                'nombre_persona' => $persData['nombre_persona'],
                'primer_apellido_persona' => $persData['primer_apellido_persona'],
                'segundo_apellido_persona' => $persData['segundo_apellido_persona'],
                'nombre_completo' => $persData['nombre_persona'] . " " .
                    $persData['primer_apellido_persona'] . " " .
                    $persData['segundo_apellido_persona'],
                'ci_persona' => $persData['ci_persona'],
                'exp_persona' => $persData['exp_persona'],
                'genero_persona' => $persData['genero_persona'] ?? null,
                'fch_nacimiento_persona' => $fechaNaci_personamiento,
            ]);
        }

        // puesto dar de baja y alta
        $puestoNuevo = Puesto::find($validatedData['puesto_nuevo_id']);
        if (isset($validatedData['puesto_actual_id'])) {
            $puestoActual = Puesto::find($validatedData['puesto_actual_id']);
            if (isset($puestoActual) && $puestoActual->persona_actual_id > 0) {
                $puestoActual->persona_actual_id = null;
                $puestoActual->estado = 'ACEFALIA';
                $puestoActual->save();
            }
        }
        if (isset($puestoNuevo)) {
            $puestoNuevo->persona_actual_id = $persona->id;
            $puestoNuevo->estado = 'OCUPADO';
            $puestoNuevo->save();
        }

        if ($persona) {
            $dataPersona = $request->input('persona');
            if ($dataPersona['anio_conclusion']) {
                $anioConclusion = Carbon::parse($dataPersona['anio_conclusion'])->setTimezone('UTC')->format('Y-m-d');
                $persona->anio_conclusion = $anioConclusion;
            }
            if ($dataPersona['fch_nacimiento_persona']) {
                $fechaNacFormated = Carbon::parse($dataPersona['fch_nacimiento_persona'])->setTimezone('UTC')->format('Y-m-d');
                $persona->fch_nacimiento_persona = $fechaNacFormated;
            }
            $persona->grado_academico_id = $dataPersona['grado_academico_id'] ?? null;
            $persona->area_formacion_id = $dataPersona['area_formacion_id'] ?? null;
            $persona->institucion_id = $dataPersona['institucion_id'] ?? null;
            $persona->con_respaldo = $dataPersona['con_respaldo'] ?? null;
            $persona->nombre_persona = $dataPersona['nombre_persona'] ?? null;
            $persona->primer_apellido_persona = $dataPersona['primer_apellido_persona'] ?? null;
            $persona->segundo_apellido_persona = $dataPersona['segundo_apellido_persona'];
            $persona->nombre_completo = $dataPersona['nombre_persona'] . " " .
                $dataPersona['primer_apellido_persona'] . " " .
                $dataPersona['segundo_apellido_persona'];
            $persona->ci_persona = $dataPersona['ci_persona'] ?? null;
            $persona->genero_persona = $dataPersona['genero_persona'] ?? null;
            $persona->save();
        }

        $incForm = new Incorporaci_personaon();
        $incForm->persona_id = $persona->id;
        $incForm->puesto_actual_id = $request->input('puesto_actual_id');
        $incForm->puesto_nuevo_id = $validatedData['puesto_nuevo_id'];
        $incForm->observacion_incorporacion = $validatedData['observacion_incorporacion'];
        $incForm->evaluacion_estado_incorporacion = 1;
        $incForm->paso_incorporacion = 1;
        $incForm->cumple_exp_profesional_incorporacion = $request->input('cumple_exp_profesional_incorporacion');
        $incForm->cumple_exp_especifica_incorporacion = $request->input('cumple_exp_especifica_incorporacion');
        $incForm->cumple_exp_mando_incorporacion = $request->input('cumple_exp_mando_incorporacion');
        $incForm->cumple_formacion_incorporacion = $request->input('cumple_formacion_incorporacion');

        if ($request->input('fch_incorporacion')) {
            $fechaIncFormated = Carbon::parse($request->input('fch_incorporacion'))->setTimezone('UTC')->format('Y-m-d');
            $incForm->fch_incorporacion = $fechaIncFormated;
        }
        $incForm->hp = $request->input('hp');
        $incForm->cite_nota_minuta_incorporacion = $request->input('cite_nota_minuta_incorporacion');
        $incForm->codigo_nota_minuta_incorporacion = $request->input('codigo_nota_minuta_incorporacion');
        if ($request->input('fch_nota_minuta_incorporacion')) {
            $fch_nota_minuta_incorporacion = Carbon::parse($request->input('fch_nota_minuta_incorporacion'))->setTimezone('UTC')->format('Y-m-d');
            $incForm->fch_nota_minuta_incorporacion = $fch_nota_minuta_incorporacion;
        }
        if ($request->input('fch_recepcion_nota_incorporacion')) {
            $fch_recepcion_nota_incorporacion = Carbon::parse($request->input('fch_recepcion_nota_incorporacion'))->setTimezone('UTC')->format('Y-m-d');
            $incForm->fch_recepcion_nota_incorporacion = $fch_recepcion_nota_incorporacion;
        }
        $incForm->cite_informe_incorporacion = $request->input('cite_informe_incorporacion');
        if ($request->input('fch_informe_incorporacion')) {
            $fch_informe_incorporacion = Carbon::parse($request->input('fch_informe_incorporacion'))->setTimezone('UTC')->format('Y-m-d');
            $incForm->fch_informe_incorporacion = $fch_informe_incorporacion;
        }
        $incForm->cite_memorandum_incorporacion = $request->input('cite_memorandum_incorporacion');
        $incForm->codigo_memorandum_incorporacion = $request->input('codigo_memorandum_incorporacion');
        if ($request->input('fch_memorandum_incorporacion')) {
            $fch_memorandum_incorporacion = Carbon::parse($request->input('fch_memorandum_incorporacion'))->setTimezone('UTC')->format('Y-m-d');
            $incForm->fch_memorandum_incorporacion = $fch_memorandum_incorporacion;
        }
        $incForm->cite_rap_incorporacion = $request->input('cite_rap_incorporacion');
        $incForm->codigo_rap_incorporacion = $request->input('codigo_rap_incorporacion');
        if ($request->input('fch_rap_incorporacion')) {
            $fch_rap_incorporacion = Carbon::parse($request->input('fch_rap_incorporacion'))->setTimezone('UTC')->format('Y-m-d');
            $incForm->fch_rap_incorporacion = $fch_rap_incorporacion;
        }
        $incForm->responsable = $request->input('responsable');

        if (
            $request->input('cite_informe_incorporacion') &&
            $request->input('fch_informe_incorporacion') &&
            $request->input('cite_memorandum_incorporacion') &&
            $request->input('codigo_memorandum_incorporacion') &&
            $request->input('fch_memorandum_incorporacion') &&
            $request->input('cite_rap_incorporacion') &&
            $request->input('codigo_rap_incorporacion') &&
            $request->input('fch_rap_incorporacion')
        ) {
            $incForm->estado_incorporacion = 2;
        }
        $incForm->save();
        $incForm->persona;
        $incForm->puesto_actual;
        $incForm->puesto_nuevo;
        $incForm->save();
        return $this->sendSuccess($incForm);
    }

    //Buscar por Persona
    public function buscarPersona(Request $request)
    {
        $puestoActual = $request->input('puesto_actual', '');

        $persona = Persona::with('puestos_actuales.departamento.Gerencia')
            ->whereHas('puestos_actuales', function ($query) use ($puestoActual) {
                $query->where('item_puesto', $puestoActual);
            })
            ->orWhere('ci_persona', $puestoActual)
            ->first();

        if ($persona) {
            return response()->json($persona);
        }
        return response()->json(['message' => 'Persona sin item_puesto!'], 404);
    }

    //Buscar por item_puesto Nuevo
    public function buscaritem_puestoApi($item_puesto)
    {
        $puesto = Puesto::with(['persona_actual', 'requisitos_puesto.requisito', 'departamento.Gerencia'])->where('item_puesto', $item_puesto)->first();
        if (isset($puesto)) {
            $puesto->persona_actual;
            return response()->json($puesto);
        } else {
            return response()->json(['message' => 'item_puesto no existe!'], 404);
        }
    }

    //genera el word R-1023 cambio de item_puesto
    public function generarFormularioEvalucaion($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);
        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        if ($incorporaci_personaon->evaluacion_estado_incorporacion == 1) {
            $incorporaci_personaon->evaluacion_estado_incorporacion = 2;
            $incorporaci_personaon->save();
        }

        $disk = Storage::disk('form_templates');
        $pathTemplate = $disk->path('R-1023-01-Cambioitem_puesto.docx'); // ruta de plantilla
        $templateProcessor = new TemplateProcessor($pathTemplate);
        $templateProcessor->setValue('persona.nombreCompleto', $incorporaci_personaon->persona->nombre_completo);
        $templateProcessor->setValue('persona.grado', isset($incorporaci_personaon->persona->gradoAcademico) ? $incorporaci_personaon->persona->gradoAcademico->nombre : '');
        $templateProcessor->setValue('persona.formaci_personaon', isset($incorporaci_personaon->persona->areaFormaci_personaon) ? $incorporaci_personaon->persona->areaFormaci_personaon->nombre : '');

        if (!$incorporaci_personaon->puesto_actual->Funcionario->isEmpty()) {
            $fechaDesignaci_personaon = $incorporaci_personaon->puesto_actual->Funcionario->first()->fch_inicio_puesto_funcionario;
            $carbonFecha = Carbon::parse($fechaDesignaci_personaon);
            setlocale(LC_TIME, 'es_UY');
            $carbonFecha->locale('es_UY');
            $fechaFormateada = $carbonFecha->isoFormat('LL');
            $templateProcessor->setValue('puesto_actual.fechaDeUltimaDesignaci_personaon', $fechaFormateada);
        }

        $templateProcessor->setValue('puesto_actual.item_puesto', $incorporaci_personaon->puesto_actual->item_puesto);
        $templateProcessor->setValue('puesto_actual.Gerencia', $incorporaci_personaon->puesto_actual->departamento->Gerencia->nombre);
        $templateProcessor->setValue('puesto_actual.departamento', $incorporaci_personaon->puesto_actual->departamento->nombre);
        $templateProcessor->setValue('puesto_actual.denominacion_puesto', $incorporaci_personaon->puesto_actual->denominacion_puesto);
        $templateProcessor->setValue('puesto_actual.salario_puesto', $incorporaci_personaon->puesto_actual->salario_puesto);

        $templateProcessor->setValue('puesto_nuevo.item_puesto', $incorporaci_personaon->puesto_nuevo->item_puesto);
        $templateProcessor->setValue('puesto_nuevo.Gerencia', $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre);
        $templateProcessor->setValue('puesto_nuevo.departamento', $incorporaci_personaon->puesto_nuevo->departamento->nombre);
        $templateProcessor->setValue('puesto_nuevo.denominacion_puesto', $incorporaci_personaon->puesto_nuevo->denominacion_puesto);
        $templateProcessor->setValue('puesto_nuevo.salario_puesto', $incorporaci_personaon->puesto_nuevo->salario_puesto);

        foreach ($incorporaci_personaon->puesto_nuevo->requisitos as $requisito) {
            if ($requisito) {
                $templateProcessor->setValue('puesto_nuevo.formaci_personaonRequerida', $requisito->formacion_requisito);
                $templateProcessor->setValue('puesto_nuevo.exp_personaerienci_personaaProfesionalSegunCargo', $requisito->exp_cargo_requisito);
                $templateProcessor->setValue('puesto_nuevo.exp_personaerienci_personaaRelaci_personaonadoAlArea', $requisito->exp_area_requisito);
                $templateProcessor->setValue('puesto_nuevo.exp_personaerienci_personaaEnFunci_personaonesDeMando', $requisito->exp_mando_requisito);
                break;
            }
        }

        $templateProcessor->setValue('incorporaci_personaon.observacion_incorporacion', strtoupper($incorporaci_personaon->observacion_incorporacion));
        $fileName = 'R-1023-01-Cambioitem_puesto_' . $incorporaci_personaon->persona->nombre_completo;
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);
        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }

    //genera el word R-1129 Cmabio de item_puesto
    public function generarFormularioCambioitem_puesto($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);
        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        $disk = Storage::disk('form_templates');
        $pathTemplate = $disk->path('R-1129-01-Cambioitem_puesto.docx'); // ruta de plantilla
        $templateProcessor = new TemplateProcessor($pathTemplate);

        $templateProcessor->setValue('persona.nombreCompleto', $incorporaci_personaon->persona->nombre_completo);
        $templateProcessor->setValue('persona.ci_persona', $incorporaci_personaon->persona->ci_persona);
        $templateProcessor->setValue('persona.exp_persona', $incorporaci_personaon->persona->exp_persona);

        $fileName = 'R-1129-01-Cambioitem_puesto_' . $incorporaci_personaon->persona->nombre_completo;
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);
        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }

    //genera el words R-0980 Cambio de item_puesto y Nueva Incorporaci_personaon
    public function generarFormularioDocumentosCambioitem_puesto($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);
        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        $disk = Storage::disk('form_templates');
        $pathTemplate = $disk->path('R-0980-01.docx');
        $templateProcessor = new TemplateProcessor($pathTemplate);

        $templateProcessor->setValue('puesto_nuevo.Gerencia', $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre);

        $carbonFechaInfo = Carbon::parse($incorporaci_personaon->fch_informe_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaInfo->locale('es_UY');
        $fechaInfoFormateada = $carbonFechaInfo->isoFormat('LL');
        $templateProcessor->setValue('incorporaci_personaon.fechaInfo', $fechaInfoFormateada);

        $templateProcessor->setValue('puesto_nuevo.Gerencia', $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre);

        $Gerencia = $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre;
        $GerenciasDepartamentos = array(
            "Gerencia Distrital La Paz I" => "el Departamento Administrativo y Recursos Humanos",
            "Gerencia Distrital La Paz II" => "la Administrativo y Recursos Humanos",
            "Gerencia GRACO La Paz" => "la Administrativo y Recursos Humanos",
            "Gerencia Distrital El Alto" => "la Administrativo y Recursos Humanos",
            "Gerencia Distrital Cochabamba" => "el Departamento Administrativo y Recursos Humanos",
            "Gerencia GRACO Cochabamba" => "el Departamento Administrativo y Recursos Humanos",
            "Gerencia Distrital Santa Cruz I" => "el Departamento Administrativo y Recursos Humanos",
            "Gerencia Distrital Santa Cruz II" => "la Administrativo y Recursos Humanos",
            "Gerencia GRACO Santa Cruz" => "la Administrativo y Recursos Humanos",
            "Gerencia Distrital Montero" => "la Administrativo y Recursos Humanos",
            "Gerencia Distrital Chuquisaca" => "la Administrativo y Recursos Humanos",
            "Gerencia Distrital Tarija" => "la Administrativo y Recursos Humanos",
            "Gerencia Distrital Yacuiba" => "la Administrativo y Recursos Humanos",
            "Gerencia Distrital Oruro" => "la Administrativo y Recursos Humanos",
            "Gerencia Distrital Potosí" => "la Administrativo y Recursos Humanos",
            "Gerencia Distrital Beni" => "la Administrativo y Recursos Humanos",
            "Gerencia Distrital Pando" => "la Administrativo y Recursos Humanos",
        );

        if (isset($GerenciasDepartamentos[$Gerencia])) {
            $departamento = $GerenciasDepartamentos[$Gerencia];
        } else {
            $departamento = "el Departamento de Dotaci_personaón y Evaluaci_personaón";
        }
        $templateProcessor->setValue('puesto_nuevo.departamento', $departamento);

        $templateProcessor->setValue('persona.nombreCompleto', $incorporaci_personaon->persona->nombre_completo);
        $templateProcessor->setValue('persona.ci_persona', $incorporaci_personaon->persona->ci_persona);
        $templateProcessor->setValue('persona.exp_persona', $incorporaci_personaon->persona->exp_persona);

        $fileName = 'R-0980-01_' . $incorporaci_personaon->persona->nombre_completo;
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);
        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }

    //genera el words R-0078 Nueva Incorporaci_personaon
    public function generarFormularioEvalR0078($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);
        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        if ($incorporaci_personaon->evaluacion_estado_incorporacion == 1) {
            $incorporaci_personaon->evaluacion_estado_incorporacion = 2;
            $incorporaci_personaon->save();
        }

        $disk = Storage::disk('form_templates');
        $pathTemplate = $disk->path('R-0078-01.docx'); // ruta de plantilla
        $templateProcessor = new TemplateProcessor($pathTemplate);

        $templateProcessor->setValue('persona.nombreCompleto', $incorporaci_personaon->persona->nombre_completo);
        $templateProcessor->setValue('persona.gradoAcademico', $incorporaci_personaon->persona->grado_academico->nombre);
        $templateProcessor->setValue('persona.formaci_personaon', $incorporaci_personaon->persona->area_formaci_personaon->nombre);

        $fechaNaci_personamiento = Carbon::parse($incorporaci_personaon->persona->fch_nacimiento_persona);
        $edad = $fechaNaci_personamiento->age;
        $templateProcessor->setValue('persona.edad', $edad);

        $templateProcessor->setValue('puesto_nuevo.item_puesto', $incorporaci_personaon->puesto_nuevo->item_puesto);
        $templateProcessor->setValue('puesto_nuevo.Gerencia', $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre);
        $templateProcessor->setValue('puesto_nuevo.departamento', $incorporaci_personaon->puesto_nuevo->departamento->nombre);
        $templateProcessor->setValue('puesto_nuevo.denominacion_puesto', $incorporaci_personaon->puesto_nuevo->denominacion_puesto);
        $templateProcessor->setValue('puesto_nuevo.salario_puesto', $incorporaci_personaon->puesto_nuevo->salario_puesto);

        foreach ($incorporaci_personaon->puesto_nuevo->requisitos as $requisito) {
            if ($requisito) {
                $templateProcessor->setValue('puesto_nuevo.formaci_personaonRequerida', $requisito->formacion_requisito);
                $templateProcessor->setValue('puesto_nuevo.exp_personaerienci_personaaProfesionalSegunCargo', $requisito->exp_cargo_requisito);
                $templateProcessor->setValue('puesto_nuevo.exp_personaerienci_personaaRelaci_personaonadoAlArea', $requisito->exp_area_requisito);
                $templateProcessor->setValue('puesto_nuevo.exp_personaerienci_personaaEnFunci_personaonesDeMando', $requisito->exp_mando_requisito);
                break;
            }
        }

        $templateProcessor->setValue('incorporaci_personaon.observacion_incorporacion', strtoupper($incorporaci_personaon->observacion_incorporacion));
        $fileName = 'R-0078_' . $incorporaci_personaon->persona->nombre_completo;
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);
        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }
    public function genFormEvalR1401($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);

        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        $incorporaci_personaon->estado_incorporacion = 3;
        $incorporaci_personaon->save();

        $disk = Storage::disk('form_templates');
        $pathTemplate = $disk->path('R-1401-01.docx');
        $templateProcessor = new TemplateProcessor($pathTemplate);

        $templateProcessor->setValue('persona.nombreCompleto', $incorporaci_personaon->persona->nombre_completo);
        $templateProcessor->setValue('persona.ci_persona', $incorporaci_personaon->persona->ci_persona);
        $templateProcessor->setValue('persona.exp_persona', $incorporaci_personaon->persona->exp_persona);

        $nombreGerencia = $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre;
        switch ($nombreGerencia) {
            case 'El Alto':
                $ubicaci_personaon = 'El Alto';
                break;
            case 'Cochabamba':
            case 'GRACO Cochabamba':
                $ubicaci_personaon = 'Cochabamba';
                break;
            case 'Quillacollo':
                $ubicaci_personaon = 'Quillacollo';
                break;
            case 'Santa Cruz I':
            case 'Santa Cruz II':
            case 'GRACO Santa Cruz':
                $ubicaci_personaon = 'Santa Cruz';
                break;
            case 'Montero':
                $ubicaci_personaon = 'Montero';
                break;
            case 'Chuquisaca':
                $ubicaci_personaon = 'Chuquisaca';
                break;
            case 'Tarija':
                $ubicaci_personaon = 'Tarija';
                break;
            case 'Yacuiba':
                $ubicaci_personaon = 'Yacuiba';
                break;
            case 'Oruro':
                $ubicaci_personaon = 'Oruro';
                break;
            case 'Potosí':
                $ubicaci_personaon = 'Potosí';
                break;
            case 'Beni':
                $ubicaci_personaon = 'Beni';
                break;
            case 'Pando':
                $ubicaci_personaon = 'Pando';
                break;
            default:
                $ubicaci_personaon = 'La Paz';
                break;
        }
        $templateProcessor->setValue('ubicaci_personaon', $ubicaci_personaon);

        $carbonFechaIncorporaci_personaon = Carbon::parse($incorporaci_personaon->fch_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaIncorporaci_personaon->locale('es_UY');
        $fechaIncorporaci_personaonFormateada = $carbonFechaIncorporaci_personaon->isoFormat('LL');
        $templateProcessor->setValue('fechaIncorporaci_personaon', $fechaIncorporaci_personaonFormateada);
        $fileName = 'R-1401_' . $incorporaci_personaon->persona->nombre_completo;
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);
        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }
    //Para el R-1469, Remision de documentos
    public function genFormRemisionDeDocumentos($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);

        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        $incorporaci_personaon->estado_incorporacion = 3;
        $incorporaci_personaon->save();

        $disk = Storage::disk('form_templates');
        $pathTemplate = $disk->path('R-1469-01-Cambioitem_puesto.docx');
        $templateProcessor = new TemplateProcessor($pathTemplate);

        $templateProcessor->setValue('puesto_nuevo.Gerencia', strtoupper($incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre));
        $templateProcessor->setValue('incoporaci_personaon.hp', strtoupper($incorporaci_personaon->hp));

        mb_internal_encoding("UTF-8");
        $templateProcessor->setValue('puesto_nuevo.departamento', mb_strtoupper($incorporaci_personaon->puesto_nuevo->departamento->nombre, "UTF-8"));

        $templateProcessor->setValue('persona.nombreCompleto', strtoupper($incorporaci_personaon->persona->nombre_completo));

        $templateProcessor->setValue('fechaMemo', $incorporaci_personaon->fch_memorandum_incorporacion);
        $templateProcessor->setValue('incorporaci_personaon.fechaRAP', $incorporaci_personaon->fch_rap_incorporacion);
        $templateProcessor->setValue('incorporaci_personaon.fechaDeIncorporaci_personaon', $incorporaci_personaon->fch_incorporacion);

        if (isset($incorporaci_personaon->puesto_actual)) {
            $fileName = 'R-1469-01-Cambioitem_puesto_' . $incorporaci_personaon->persona->nombre_completo;
        } else {
            $fileName = 'R-1469-01_' . $incorporaci_personaon->persona->nombre_completo;
        }
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);

        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }

    //Informe RAP
    public function genFormRAP($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);

        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        $incorporaci_personaon->estado_incorporacion = 3;
        $incorporaci_personaon->save();

        $disk = Storage::disk('form_templates');
        if (isset($incorporaci_personaon->puesto_actual)) {
            $pathTemplate = $disk->path('RAPCambioitem_puesto.docx');
        } else {
            $pathTemplate = $disk->path('RAP.docm');
        }
        $templateProcessor = new TemplateProcessor($pathTemplate);

        $templateProcessor->setValue('incorporaci_personaon.ci_personateRAP', $incorporaci_personaon->cite_rap_incorporacion);
        $templateProcessor->setValue('incorporaci_personaon.codigoRAP', $incorporaci_personaon->codigo_rap_incorporacion);
        $templateProcessor->setValue('codigo', $incorporaci_personaon->codigo_rap_incorporacion);

        $carbonFechaRap = Carbon::parse($incorporaci_personaon->fch_rap_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaRap->locale('es_UY');
        $fechaRapFormateada = $carbonFechaRap->isoFormat('LL');
        $templateProcessor->setValue('incorporaci_personaon.fechaRAP', $fechaRapFormateada);

        $templateProcessor->setValue('incorporaci_personaon.ci_personateInforme', $incorporaci_personaon->cite_informe_incorporacion);

        $carbonFechaInforme = Carbon::parse($incorporaci_personaon->fch_informe_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaInforme->locale('es_UY');
        $fechaInformeFormateada = $carbonFechaInforme->isoFormat('LL');
        $templateProcessor->setValue('incorporaci_personaon.fechaInforme', $fechaInformeFormateada);

        if (isset($incorporaci_personaon->puesto_actual)) {
            $descripci_personaon = 'recomienda el cambio del Ítem N°' . $incorporaci_personaon->puesto_actual->item_puesto . ', al Ítem N°' . $incorporaci_personaon->puesto_nuevo->item_puesto;

        } else {
            $descripci_personaon = 'recomienda la designaci_personaón al Ítem N°' . $incorporaci_personaon->puesto_nuevo->item_puesto;
        }
        $templateProcessor->setValue('descripci_personaon', $descripci_personaon);

        $nombreCompleto = $incorporaci_personaon->persona->nombre_completo;
        $genero_persona = $incorporaci_personaon->persona->genero_persona;

        if ($genero_persona === 'F') {
            $templateProcessor->setValue('persona.deLa', 'de la servidora publica ' . $nombreCompleto);
            $templateProcessor->setValue('persona.reasignada', 'a la servidora publica interina ' . $nombreCompleto);
        } else {
            $templateProcessor->setValue('persona.deLa', 'del servidor publico ' . $nombreCompleto);
            $templateProcessor->setValue('persona.reasignada', 'al servidor publico interino ' . $nombreCompleto);
        }

        $templateProcessor->setValue('persona.ci_persona', $incorporaci_personaon->persona->ci_persona);
        $templateProcessor->setValue('persona.exp_persona', $incorporaci_personaon->persona->exp_persona);
        $templateProcessor->setValue('puesto_nuevo.denominacion_puesto', $incorporaci_personaon->puesto_nuevo->denominacion_puesto);

        $nombreDepartamento = $incorporaci_personaon->puesto_nuevo->departamento->nombre;
        $inici_personaalDepartamento = strtoupper(substr($nombreDepartamento, 0, 1));
        if (in_array($inici_personaalDepartamento, ['D'])) {
            $valorDepartamento = 'del ' . $nombreDepartamento;
        } elseif (in_array($inici_personaalDepartamento, ['G', 'A', 'U', 'P'])) {
            $valorDepartamento = 'de la ' . $nombreDepartamento;
        } else {
            $valorDepartamento = 'de ' . $nombreDepartamento;
        }
        $templateProcessor->setValue('puesto_nuevo.departamento', $valorDepartamento . ' ');

        $valorGerencia = $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre;
        $templateProcessor->setValue('puesto_nuevo.Gerencia', $valorGerencia . ' ');

        $templateProcessor->setValue('puesto_nuevo.item_puesto', $incorporaci_personaon->puesto_nuevo->item_puesto);
        $templateProcessor->setValue('puesto_nuevo.salario_puesto', $incorporaci_personaon->puesto_nuevo->salario_puesto);
        $templateProcessor->setValue('puesto_nuevo.salario_puestoLiteral', $incorporaci_personaon->puesto_nuevo->salario_literal_puesto);

        $carbonFechaIncorporaci_personaon = Carbon::parse($incorporaci_personaon->fch_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaIncorporaci_personaon->locale('es_UY');
        $fechaIncorporaci_personaonFormateada = $carbonFechaIncorporaci_personaon->isoFormat('LL');
        $templateProcessor->setValue('incorporaci_personaon.fechaDeIncorporaci_personaon', $fechaIncorporaci_personaonFormateada);

        $templateProcessor->setValue('incorporaci_personaon.hp', $incorporaci_personaon->hp);

        if (isset($incorporaci_personaon->puesto_actual)) {
            $fileName = 'RAPCambioitem_puesto_' . $incorporaci_personaon->persona->nombre_completo;
        } else {
            $fileName = 'RAP_' . $incorporaci_personaon->persona->nombre_completo;
        }
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);

        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }

    //PARA MEMORANDUM
    public function genFormMemo($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);

        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        $incorporaci_personaon->estado_incorporacion = 3;
        $incorporaci_personaon->save();

        $disk = Storage::disk('form_templates');
        if (isset($incorporaci_personaon->puesto_actual)) {
            $pathTemplate = $disk->path('MemoCambioitem_puesto.docx');
        } else {
            $pathTemplate = $disk->path('memorandum.docx');
        }

        $templateProcessor = new TemplateProcessor($pathTemplate);

        $templateProcessor->setValue('incorporaci_personaon.codigoMemorandum', $incorporaci_personaon->codigo_memorandum_incorporacion);
        $templateProcessor->setValue('incorporaci_personaon.ci_personateMemorandum', $incorporaci_personaon->cite_memorandum_incorporacion);

        $carbonFechaMemo = Carbon::parse($incorporaci_personaon->fch_memorandum_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaMemo->locale('es_UY');
        $fechaMemoFormateada = $carbonFechaMemo->isoFormat('LL');
        $templateProcessor->setValue('fechaMemo', $fechaMemoFormateada);

        $templateProcessor->setValue('persona.nombreCompleto', $incorporaci_personaon->persona->nombre_completo);

        if (isset($incorporaci_personaon->puesto_actual)) {
            $denominacion_puesto = $incorporaci_personaon->puesto_actual->denominacion_puesto;
        } else {
            $denominacion_puesto = $incorporaci_personaon->puesto_nuevo->denominacion_puesto;
        }
        $denominacion_puestoEnMayusculas = mb_strtoupper($denominacion_puesto, 'UTF-8');
        $templateProcessor->setValue('denominacion_puestoPuesto', $denominacion_puestoEnMayusculas);

        $primerApellido = $incorporaci_personaon->persona->primer_apellido_persona;
        $genero_persona = $incorporaci_personaon->persona->genero_persona;

        if ($genero_persona === 'F') {
            $templateProcessor->setValue('persona.para', 'Señora ' . $primerApellido);
            $templateProcessor->setValue('persona.reasignada', 'reasignada' . ' ');
        } else {
            $templateProcessor->setValue('persona.para', 'Señor ' . $primerApellido);
            $templateProcessor->setValue('persona.reasignada', 'reasignado' . ' ');

        }

        $templateProcessor->setValue('incoporaci_personaon.codigoRap', $incorporaci_personaon->codigo_rap_incorporacion);
        $templateProcessor->setValue('puesto_nuevo.denominacion_puesto', $incorporaci_personaon->puesto_nuevo->denominacion_puesto);

        $nombreDepartamento = $incorporaci_personaon->puesto_nuevo->departamento->nombre;
        $inici_personaalDepartamento = strtoupper(substr($nombreDepartamento, 0, 1));
        if (in_array($inici_personaalDepartamento, ['D'])) {
            $valorDepartamento = 'del ' . $nombreDepartamento;
        } elseif (in_array($inici_personaalDepartamento, ['G', 'A', 'U', 'P'])) {
            $valorDepartamento = 'de la ' . $nombreDepartamento;
        } else {
            $valorDepartamento = 'de ' . $nombreDepartamento;
        }
        $templateProcessor->setValue('puesto_nuevo.departamento', $valorDepartamento);

        $templateProcessor->setValue('puesto_nuevo.Gerencia', $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre);
        $templateProcessor->setValue('puesto_nuevo.item_puesto', $incorporaci_personaon->puesto_nuevo->item_puesto);
        $templateProcessor->setValue('puesto_nuevo.salario_puesto', $incorporaci_personaon->puesto_nuevo->salario_puesto);
        $templateProcessor->setValue('puesto_nuevo.salario_puestoLiteral', $incorporaci_personaon->puesto_nuevo->salario_literal_puesto);

        $carbonFechaIncorporaci_personaon = Carbon::parse($incorporaci_personaon->fch_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaIncorporaci_personaon->locale('es_UY');
        $fechaIncorporaci_personaonFormateada = $carbonFechaIncorporaci_personaon->isoFormat('LL');
        $templateProcessor->setValue('incorporaci_personaon.fechaDeIncorporaci_personaon', $fechaIncorporaci_personaonFormateada);

        $templateProcessor->setValue('incorporaci_personaon.hp', $incorporaci_personaon->hp);

        if (isset($incorporaci_personaon->puesto_actual)) {
            $fileName = 'MemoCambioitem_puesto_' . $incorporaci_personaon->persona->nombre_completo;
        } else {
            $fileName = 'Memorandum_' . $incorporaci_personaon->persona->nombre_completo;
        }
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);

        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }

    //para acta de posesion de cambio de item_puesto
    public function genFormActaDePosesion($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);

        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        $incorporaci_personaon->estado_incorporacion = 3;
        $incorporaci_personaon->save();

        $disk = Storage::disk('form_templates');
        $pathTemplate = $disk->path('ActaDePosesionCambioDeitem_puesto.docx');
        if (isset($incorporaci_personaon->puesto_actual)) {
            $pathTemplate = $disk->path('ActaDePosesionCambioDeitem_puesto.docx');
        } else {
            $pathTemplate = $disk->path('R-0242-01.docx');
        }
        $templateProcessor = new TemplateProcessor($pathTemplate);

        $nombreGerencia = $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre;
        switch ($nombreGerencia) {
            case 'El Alto':
                $ubicaci_personaon = 'El Alto';
                break;
            case 'Cochabamba':
            case 'GRACO Cochabamba':
                $ubicaci_personaon = 'Cochabamba';
                break;
            case 'Quillacollo':
                $ubicaci_personaon = 'Quillacollo';
                break;
            case 'Santa Cruz I':
            case 'Santa Cruz II':
            case 'GRACO Santa Cruz':
                $ubicaci_personaon = 'Santa Cruz';
                break;
            case 'Montero':
                $ubicaci_personaon = 'Montero';
                break;
            case 'Chuquisaca':
                $ubicaci_personaon = 'Chuquisaca';
                break;
            case 'Tarija':
                $ubicaci_personaon = 'Tarija';
                break;
            case 'Yacuiba':
                $ubicaci_personaon = 'Yacuiba';
                break;
            case 'Oruro':
                $ubicaci_personaon = 'Oruro';
                break;
            case 'Potosí':
                $ubicaci_personaon = 'Potosí';
                break;
            case 'Beni':
                $ubicaci_personaon = 'Beni';
                break;
            case 'Pando':
                $ubicaci_personaon = 'Pando';
                break;
            default:
                $ubicaci_personaon = 'La Paz';
                break;
        }
        $templateProcessor->setValue('ubicaci_personaon', $ubicaci_personaon);

        $carbonFechaIncorporaci_personaon = Carbon::parse($incorporaci_personaon->fch_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaIncorporaci_personaon->locale('es_UY');
        $fechaIncorporaci_personaonFormateada = $carbonFechaIncorporaci_personaon->isoFormat('LL');
        $nombreDiaIncorporaci_personaon = $carbonFechaIncorporaci_personaon->isoFormat('dddd');
        $templateProcessor->setValue('incorporaci_personaon.nombreDiaDeIncorporaci_personaon', $nombreDiaIncorporaci_personaon);

        $carbonFechaIncorporaci_personaon = Carbon::parse($incorporaci_personaon->fch_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaIncorporaci_personaon->locale('es_UY');
        $fechaIncorporaci_personaonFormateada = $carbonFechaIncorporaci_personaon->isoFormat('LL');
        $templateProcessor->setValue('incorporaci_personaon.fechaDeIncorporaci_personaon', $fechaIncorporaci_personaonFormateada);

        $genero_persona = $incorporaci_personaon->persona->genero_persona;

        if ($genero_persona === 'F') {
            $templateProcessor->setValue('ci_personaudadano', 'la ci_personaudadana');
        } else {
            $templateProcessor->setValue('ci_personaudadano', 'el ci_personaudadano');

        }

        $templateProcessor->setValue('persona.nombreCompleto', $incorporaci_personaon->persona->nombre_completo);
        $templateProcessor->setValue('persona.ci_persona', $incorporaci_personaon->persona->ci_persona);
        $templateProcessor->setValue('persona.exp_persona', $incorporaci_personaon->persona->exp_persona);
        $templateProcessor->setValue('incorporaci_personaon.codigoRAP', $incorporaci_personaon->codigo_rap_incorporacion);
        $templateProcessor->setValue('puesto_nuevo.denominacion_puesto', $incorporaci_personaon->puesto_nuevo->denominacion_puesto);

        $nombreDepartamento = $incorporaci_personaon->puesto_nuevo->departamento->nombre;
        $inici_personaalDepartamento = strtoupper(substr($nombreDepartamento, 0, 1));
        if (in_array($inici_personaalDepartamento, ['D'])) {
            $valorDepartamento = 'del ' . $nombreDepartamento;
        } elseif (in_array($inici_personaalDepartamento, ['G', 'A', 'U', 'P'])) {
            $valorDepartamento = 'de la ' . $nombreDepartamento;
        } else {
            $valorDepartamento = 'de ' . $nombreDepartamento;
        }
        $templateProcessor->setValue('puesto_nuevo.departamento', $valorDepartamento);

        $templateProcessor->setValue('puesto_nuevo.Gerencia', $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre);
        $templateProcessor->setValue('puesto_nuevo.item_puesto', $incorporaci_personaon->puesto_nuevo->item_puesto);

        if (isset($incorporaci_personaon->puesto_actual)) {
            $fileName = 'ActaDePosesionCambioDeitem_puesto_' . $incorporaci_personaon->persona->nombre_completo;
        } else {
            $fileName = 'ActaDePosesion_' . $incorporaci_personaon->persona->nombre_completo;
        }
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);

        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }

    //para acta de entrega
    public function genFormActaDeEntrega($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);

        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        $incorporaci_personaon->estado_incorporacion = 3;
        $incorporaci_personaon->save();

        $disk = Storage::disk('form_templates');
        if (isset($incorporaci_personaon->puesto_actual)) {
            $pathTemplate = $disk->path('ActaEntregaCambioDeitem_puesto.docx');
        } else {
            $pathTemplate = $disk->path('R-0243-01.docx');
        }
        $templateProcessor = new TemplateProcessor($pathTemplate);

        $nombreGerencia = $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre;
        switch ($nombreGerencia) {
            case 'El Alto':
                $ubicaci_personaon = 'El Alto';
                break;
            case 'Cochabamba':
            case 'GRACO Cochabamba':
                $ubicaci_personaon = 'Cochabamba';
                break;
            case 'Quillacollo':
                $ubicaci_personaon = 'Quillacollo';
                break;
            case 'Santa Cruz I':
            case 'Santa Cruz II':
            case 'GRACO Santa Cruz':
                $ubicaci_personaon = 'Santa Cruz';
                break;
            case 'Montero':
                $ubicaci_personaon = 'Montero';
                break;
            case 'Chuquisaca':
                $ubicaci_personaon = 'Chuquisaca';
                break;
            case 'Tarija':
                $ubicaci_personaon = 'Tarija';
                break;
            case 'Yacuiba':
                $ubicaci_personaon = 'Yacuiba';
                break;
            case 'Oruro':
                $ubicaci_personaon = 'Oruro';
                break;
            case 'Potosí':
                $ubicaci_personaon = 'Potosí';
                break;
            case 'Beni':
                $ubicaci_personaon = 'Beni';
                break;
            case 'Pando':
                $ubicaci_personaon = 'Pando';
                break;
            default:
                $ubicaci_personaon = 'La Paz';
                break;
        }
        $templateProcessor->setValue('ubicaci_personaon', $ubicaci_personaon);

        $carbonFechaIncorporaci_personaon = Carbon::parse($incorporaci_personaon->fch_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaIncorporaci_personaon->locale('es_UY');
        $fechaIncorporaci_personaonFormateada = $carbonFechaIncorporaci_personaon->isoFormat('LL');
        $templateProcessor->setValue('fechaIncorporaci_personaon', $fechaIncorporaci_personaonFormateada);

        $templateProcessor->setValue('persona.nombreCompleto', $incorporaci_personaon->persona->nombre_completo);
        $templateProcessor->setValue('puesto_nuevo.denominacion_puesto', $incorporaci_personaon->puesto_nuevo->denominacion_puesto);
        $templateProcessor->setValue('puesto_nuevo.departamento', $incorporaci_personaon->puesto_nuevo->departamento->nombre);
        $templateProcessor->setValue('puesto_nuevo.Gerencia', $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre);

        if (isset($incorporaci_personaon->puesto_actual)) {
            $fileName = 'ActaEntregaCambioDeitem_puesto_' . $incorporaci_personaon->persona->nombre_completo;
        } else {
            $fileName = 'ActaEntrega_' . $incorporaci_personaon->persona->nombre_completo;
        }
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);

        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }
    //para informe con nota
    public function genFormInformeNota($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);

        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        $incorporaci_personaon->estado_incorporacion = 3;
        $incorporaci_personaon->save();

        $disk = Storage::disk('form_templates');
        if (isset($incorporaci_personaon->puesto_actual)) {
            $pathTemplate = $disk->path('InfNotaCambioitem_puesto.docx'); // ruta de plantilla
        } else {
            $pathTemplate = $disk->path('informenota.docx');
        }
        $templateProcessor = new TemplateProcessor($pathTemplate);

        $templateProcessor->setValue('incorporaci_personaon.ci_personateInforme', $incorporaci_personaon->cite_informe_incorporacion);

        $nombreCompleto = $incorporaci_personaon->persona->nombre_completo;
        $genero_persona = $incorporaci_personaon->persona->genero_persona;
        if ($genero_persona === 'F') {
            $templateProcessor->setValue('persona.referenci_personaaMayuscula', 'DE LA SERVIDORA PÚBLICA INTERINA ' . mb_strtoupper($nombreCompleto, 'UTF-8'));
            $templateProcessor->setValue('persona.referenci_personaaMayuscula1', 'SERVIDORA PÚBLICA INTERINA DE LA SEÑORA ' . mb_strtoupper($nombreCompleto, 'UTF-8'));
            $templateProcessor->setValue('persona.referenci_personaa', 'de la servidora publica interina ' . $nombreCompleto);
            $templateProcessor->setValue('persona.referenci_personaa1', ' servidora publica interina de la señora ' . $nombreCompleto);
            $templateProcessor->setValue('persona.referenci_personaaAlPrinci_personapio', 'La servidora publica interina ' . $nombreCompleto);
            $templateProcessor->setValue('persona.referenci_personaaAlPrinci_personapio1', 'La señora ' . $nombreCompleto);
        } else {
            $templateProcessor->setValue('persona.referenci_personaaMayuscula', 'DEL SERVIDOR PÚBLICO INTERINO ' . mb_strtoupper($nombreCompleto, 'UTF-8'));
            $templateProcessor->setValue('persona.referenci_personaaMayuscula1', 'SERVIDOR PÚBLICO INTERINO DEL SEÑOR ' . mb_strtoupper($nombreCompleto, 'UTF-8'));
            $templateProcessor->setValue('persona.referenci_personaa', 'del servidor publico interino ' . $nombreCompleto);
            $templateProcessor->setValue('persona.referenci_personaa1', 'servidor publico interino del señor ' . $nombreCompleto);
            $templateProcessor->setValue('persona.referenci_personaaAlPrinci_personapio', 'El servidor publico interino ' . $nombreCompleto);
            $templateProcessor->setValue('persona.referenci_personaaAlPrinci_personapio1', 'El señor ' . $nombreCompleto);
        }

        if ($incorporaci_personaon->puesto_actual) {
            $templateProcessor->setValue('puesto_actual.item_puesto', $incorporaci_personaon->puesto_actual->item_puesto);

            $denominacion_puesto = isset($incorporaci_personaon->puesto_actual->denominacion_puesto) ? $incorporaci_personaon->puesto_actual->denominacion_puesto : 'Valor predeterminado o mensaje de error';

            $templateProcessor->setValue('puesto_actual.denominacion_puestoMayuscula', mb_strtoupper($denominacion_puesto, 'UTF-8'));

            $nombreDepartamento = mb_strtoupper($incorporaci_personaon->puesto_actual->departamento->nombre, 'UTF-8');
            $inici_personaalDepartamento = mb_strtoupper(substr($nombreDepartamento, 0, 1), 'UTF-8');

            if (in_array($inici_personaalDepartamento, ['D'])) {
                $valorDepartamento = 'DEL ' . $nombreDepartamento;
            } elseif (in_array($inici_personaalDepartamento, ['G', 'A', 'U', 'P'])) {
                $valorDepartamento = 'DE LA ' . $nombreDepartamento;
            } else {
                $valorDepartamento = 'DE ' . $nombreDepartamento;
            }

            $templateProcessor->setValue('puesto_actual.departamentoMayuscula', $valorDepartamento);

            $templateProcessor->setValue('puesto_actual.GerenciaMayuscula', mb_strtoupper($incorporaci_personaon->puesto_actual->departamento->Gerencia->nombre, 'UTF-8'));
        } else {
            $templateProcessor->setValue('puesto_actual.item_puesto', 'Valor predeterminado o mensaje de error');
            $templateProcessor->setValue('puesto_actual.denominacion_puestoMayuscula', 'Valor predeterminado o mensaje de error');
            $templateProcessor->setValue('puesto_actual.departamentoMayuscula', 'Valor predeterminado o mensaje de error');
            $templateProcessor->setValue('puesto_actual.GerenciaMayuscula', 'Valor predeterminado o mensaje de error');
        }

        $templateProcessor->setValue('puesto_nuevo.item_puesto', $incorporaci_personaon->puesto_nuevo->item_puesto);
        $templateProcessor->setValue('puesto_nuevo.denominacion_puestoMayuscula', mb_strtoupper($incorporaci_personaon->puesto_nuevo->denominacion_puesto, 'UTF-8'));

        $nombreDepartamento = mb_strtoupper($incorporaci_personaon->puesto_nuevo->departamento->nombre, 'UTF-8');
        $inici_personaalDepartamento = mb_strtoupper(substr($nombreDepartamento, 0, 1), 'UTF-8');
        if (in_array($inici_personaalDepartamento, ['D'])) {
            $valorDepartamento = 'DEL ' . $nombreDepartamento;
        } elseif (in_array($inici_personaalDepartamento, ['G', 'A', 'U', 'P'])) {
            $valorDepartamento = 'DE LA ' . $nombreDepartamento;
        } else {
            $valorDepartamento = 'DE ' . $nombreDepartamento;
        }
        $templateProcessor->setValue('puesto_nuevo.departamentoMayuscula', $valorDepartamento);

        $templateProcessor->setValue('puesto_nuevo.GerenciaMayuscula', mb_strtoupper($incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre, 'UTF-8'));

        $carbonFechaInfo = Carbon::parse($incorporaci_personaon->fch_informe_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaInfo->locale('es_UY');
        $fechaInfoFormateada = $carbonFechaInfo->isoFormat('LL');
        $templateProcessor->setValue('incorporaci_personaon.fechaInfo', $fechaInfoFormateada);

        $templateProcessor->setValue('incorporaci_personaon.hp', $incorporaci_personaon->hp);
        $templateProcessor->setValue('incorporaci_personaon.ci_personateInfNotaMinuta', $incorporaci_personaon->cite_nota_minuta_incorporacion);

        $carbonFechaNotaMinuta = Carbon::parse($incorporaci_personaon->fch_nota_minuta_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaNotaMinuta->locale('es_UY');
        $fechaNotaMinutaFormateada = $carbonFechaNotaMinuta->isoFormat('LL');
        $templateProcessor->setValue('incorporaci_personaon.fechaNotaMinuta', $fechaNotaMinutaFormateada);

        $carbonFechaRecepci_personaon = Carbon::parse($incorporaci_personaon->fch_recepcion_nota_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaRecepci_personaon->locale('es_UY');
        $fechaRecepci_personaonFormateada = $carbonFechaRecepci_personaon->isoFormat('LL');
        $templateProcessor->setValue('incorporaci_personaon.fechaRecepci_personaon', $fechaRecepci_personaonFormateada);

        $templateProcessor->setValue('persona.nombreCompleto', $incorporaci_personaon->persona->nombre_completo);
        if ($incorporaci_personaon->puesto_actual) {
            $denominacion_puesto = isset($incorporaci_personaon->puesto_actual->denominacion_puesto) ? $incorporaci_personaon->puesto_actual->denominacion_puesto : 'Valor predeterminado o mensaje de error';
            $templateProcessor->setValue('puesto_actual.denominacion_puesto', $denominacion_puesto);

            if ($incorporaci_personaon->puesto_actual->departamento && $incorporaci_personaon->puesto_actual->departamento->Gerencia) {
                $templateProcessor->setValue('puesto_actual.Gerencia', $incorporaci_personaon->puesto_actual->departamento->Gerencia->nombre);
                $templateProcessor->setValue('puesto_actual.departamento', $incorporaci_personaon->puesto_actual->departamento->nombre);
            } else {
                $templateProcessor->setValue('puesto_actual.Gerencia', 'Valor predeterminado o mensaje de error');
                $templateProcessor->setValue('puesto_actual.departamento', 'Valor predeterminado o mensaje de error');
            }

            $salario_puesto = isset($incorporaci_personaon->puesto_actual->salario_puesto) ? $incorporaci_personaon->puesto_actual->salario_puesto : 'Valor predeterminado o mensaje de error';
            $templateProcessor->setValue('puesto_actual.salario_puesto', $salario_puesto);
        } else {
            $templateProcessor->setValue('puesto_actual.denominacion_puesto', 'Valor predeterminado o mensaje de error');
            $templateProcessor->setValue('puesto_actual.Gerencia', 'Valor predeterminado o mensaje de error');
            $templateProcessor->setValue('puesto_actual.departamento', 'Valor predeterminado o mensaje de error');
            $templateProcessor->setValue('puesto_actual.salario_puesto', 'Valor predeterminado o mensaje de error');
        }

        $templateProcessor->setValue('puesto_nuevo.denominacion_puesto', $incorporaci_personaon->puesto_nuevo->denominacion_puesto);

        $templateProcessor->setValue('puesto_nuevo.Gerencia', $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre);
        $templateProcessor->setValue('puesto_nuevo.departamento', $incorporaci_personaon->puesto_nuevo->departamento->nombre);
        $templateProcessor->setValue('puesto_nuevo.salario_puesto', $incorporaci_personaon->puesto_nuevo->salario_puesto);
        $templateProcessor->setValue('puesto_nuevo.salario_puestoLiteral', $incorporaci_personaon->puesto_nuevo->salario_literal_puesto);
        $templateProcessor->setValue('puesto_nuevo.estado', $incorporaci_personaon->puesto_nuevo->estado);
        $templateProcessor->setValue('persona.formaci_personaon', $incorporaci_personaon->persona->formaci_personaon);
        $templateProcessor->setValue('persona.grado', $incorporaci_personaon->persona->grado_academico->nombre ?? 'Valor predeterminado');
        $templateProcessor->setValue('persona.areaformaci_personaon', $incorporaci_personaon->persona->area_formaci_personaon->nombre ?? 'Valor predeterminado');
       $templateProcessor->setValue('persona.instituci_personaon', $incorporaci_personaon->persona->instituci_personaon->nombre ?? 'Valor predeterminado');

        $carbonFechaConclusion = Carbon::parse($incorporaci_personaon->persona->anio_conclusion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaConclusion->locale('es_UY');
        $fechaConclusionFormateada = $carbonFechaConclusion->isoFormat('LL');
        $templateProcessor->setValue('persona.conclusion', $fechaConclusionFormateada);

        $templateProcessor->setValue('incorporaci_personaon.respaldoFormaci_personaon', $this->obtenerTextoSegunValorDeFormaci_personaon($incorporaci_personaon->respaldo_formaci_personaon));

        if ($incorporaci_personaon) {
            $puestoNuevo = $incorporaci_personaon->puesto_nuevo;
            if ($puestoNuevo) {
                $requisitosPuestoNuevo = $puestoNuevo->requisitos;
                if ($requisitosPuestoNuevo->isNotEmpty()) {
                    $primerRequisitoPuestoNuevo = $requisitosPuestoNuevo->first();
                    if ($primerRequisitoPuestoNuevo) {
                        $formaci_personaonRequerida = $primerRequisitoPuestoNuevo->formacion_requisito;
                        $exp_personaProfesionalSegunCargo = $primerRequisitoPuestoNuevo->exp_cargo_requisito;
                        $exp_personaRelaci_personaonadoAlArea = $primerRequisitoPuestoNuevo->exp_area_requisito;
                        $exp_personaEnFunci_personaonesDeMando = $primerRequisitoPuestoNuevo->exp_mando_requisito;

                        $templateProcessor->setValue('puesto_nuevo.formaci_personaon', $formaci_personaonRequerida);
                        $templateProcessor->setValue('puesto_nuevo.exp_personaSegunCargo', $exp_personaProfesionalSegunCargo);
                        $templateProcessor->setValue('puesto_nuevo.exp_personaSegunArea', $exp_personaRelaci_personaonadoAlArea);
                        $templateProcessor->setValue('puesto_nuevo.exp_personaEnMando', $exp_personaEnFunci_personaonesDeMando);
                    }
                }
            }
        }

        $templateProcessor->setValue('puesto_nuevo.cumpleexp_personaSegunCargo', $this->obtenerTextoSegunValor($incorporaci_personaon->cumple_exp_profesional_incorporacion));
        $templateProcessor->setValue('puesto_nuevo.cumpleexp_personaSegunArea', $this->obtenerTextoSegunValor($incorporaci_personaon->cumple_exp_especifica_incorporacion));
        $templateProcessor->setValue('puesto_nuevo.cumpleexp_personaEnMando', $this->obtenerTextoSegunValor($incorporaci_personaon->cumple_exp_mando_incorporacion));
        $templateProcessor->setValue('puesto_nuevo.cumpleFormaci_personaon', $this->obtenerTextoSegunValorDeFormaci_personaon($incorporaci_personaon->cumple_formacion_incorporacion));
        $templateProcessor->setValue('puesto_nuevo.salario_literal_puesto', $incorporaci_personaon->puesto_nuevo->salario_literal_puesto);

        $nombreDepartamento = $incorporaci_personaon->puesto_nuevo->departamento->nombre;
        $inici_personaalDepartamento = substr($nombreDepartamento, 0, 1);
        if (in_array($inici_personaalDepartamento, ['D'])) {
            $valorDepartamento = 'del ' . $nombreDepartamento;
        } elseif (in_array($inici_personaalDepartamento, ['G', 'A', 'U', 'P'])) {
            $valorDepartamento = 'de la ' . $nombreDepartamento;
        } else {
            $valorDepartamento = 'de ' . $nombreDepartamento;
        }
        $templateProcessor->setValue('puesto_nuevo.departamentoRef', $valorDepartamento);

        if (isset($incorporaci_personaon->puesto_actual)) {
            $fileName = 'InfNotaCambioitem_puesto_' . $incorporaci_personaon->persona->nombre_completo;
        } else {
            $fileName = 'InfNota_' . $incorporaci_personaon->persona->nombre_completo;
        }
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);

        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }

    //para informe con minuta
    public function genFormInformeMinuta($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);

        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        $incorporaci_personaon->estado_incorporacion = 3;
        $incorporaci_personaon->save();

        $disk = Storage::disk('form_templates');
        if (isset($incorporaci_personaon->puesto_actual)) {
            $pathTemplate = $disk->path('InfMinutaCambioitem_puesto.docx'); // ruta de plantilla
        } else {
            $pathTemplate = $disk->path('informeminuta.docx');
        }
        $templateProcessor = new TemplateProcessor($pathTemplate);

        $templateProcessor->setValue('incorporaci_personaon.ci_personateInforme', $incorporaci_personaon->cite_informe_incorporacion);
        $templateProcessor->setValue('incorporaci_personaon.codigoNotaMinuta', $incorporaci_personaon->codigo_nota_minuta_incorporacion);

        //falta el responsable y su profesion

        $nombreCompleto = $incorporaci_personaon->persona->nombre_completo;
        $genero_persona = $incorporaci_personaon->persona->genero_persona;
        if ($genero_persona === 'F') {
            $templateProcessor->setValue('persona.referenci_personaaMayuscula', 'DE LA SERVIDORA PÚBLICA INTERINA ' . mb_strtoupper($nombreCompleto, 'UTF-8'));
            $templateProcessor->setValue('persona.referenci_personaaMayuscula1', 'SERVIDORA PÚBLICA INTERINA DE LA SEÑORA ' . mb_strtoupper($nombreCompleto, 'UTF-8'));
            $templateProcessor->setValue('persona.referenci_personaa', 'de la servidora publica interina ' . $nombreCompleto);
            $templateProcessor->setValue('persona.referenci_personaa1', ' servidora publica interina de la señora ' . $nombreCompleto);
            $templateProcessor->setValue('persona.referenci_personaaAlPrinci_personapio', 'La servidora publica interina ' . $nombreCompleto);
            $templateProcessor->setValue('persona.referenci_personaaAlPrinci_personapio1', 'La señora ' . $nombreCompleto);
        } else {
            $templateProcessor->setValue('persona.referenci_personaaMayuscula', 'DEL SERVIDOR PÚBLICO INTERINO ' . mb_strtoupper($nombreCompleto, 'UTF-8'));
            $templateProcessor->setValue('persona.referenci_personaaMayuscula1', 'SERVIDOR PÚBLICO INTERINO DEL SEÑOR ' . mb_strtoupper($nombreCompleto, 'UTF-8'));
            $templateProcessor->setValue('persona.referenci_personaa', 'del servidor publico interino ' . $nombreCompleto);
            $templateProcessor->setValue('persona.referenci_personaa1', 'servidor publico interino del señor ' . $nombreCompleto);
            $templateProcessor->setValue('persona.referenci_personaaAlPrinci_personapio', 'El servidor publico interino ' . $nombreCompleto);
            $templateProcessor->setValue('persona.referenci_personaaAlPrinci_personapio1', 'El señor ' . $nombreCompleto);
        }

        if ($incorporaci_personaon && $incorporaci_personaon->puesto_actual) {
            $templateProcessor->setValue('puesto_actual.item_puesto', optional($incorporaci_personaon->puesto_actual)->item_puesto);
            $templateProcessor->setValue('puesto_actual.denominacion_puestoMayuscula', mb_strtoupper(optional($incorporaci_personaon->puesto_actual)->denominacion_puesto, 'UTF-8'));
        }


        if ($incorporaci_personaon && $incorporaci_personaon->puesto_actual && $incorporaci_personaon->puesto_actual->departamento) {
            $nombreDepartamento = mb_strtoupper(optional($incorporaci_personaon->puesto_actual->departamento)->nombre, 'UTF-8');
        } else {
            $nombreDepartamento = null;
        }

        $inici_personaalDepartamento = mb_strtoupper(substr($nombreDepartamento, 0, 1), 'UTF-8');
        if (in_array($inici_personaalDepartamento, ['D'])) {
            $valorDepartamento = 'DEL ' . $nombreDepartamento;
        } elseif (in_array($inici_personaalDepartamento, ['G', 'A', 'U', 'P'])) {
            $valorDepartamento = 'DE LA ' . $nombreDepartamento;
        } else {
            $valorDepartamento = 'DE ' . $nombreDepartamento;
        }
        $templateProcessor->setValue('puesto_actual.departamentoMayuscula', $valorDepartamento);

        if ($incorporaci_personaon && $incorporaci_personaon->puesto_actual && $incorporaci_personaon->puesto_actual->departamento && $incorporaci_personaon->puesto_actual->departamento->Gerencia) {
            $nombreGerencia = mb_strtoupper(optional($incorporaci_personaon->puesto_actual->departamento->Gerencia)->nombre, 'UTF-8');
        } else {
            $nombreGerencia = null;
        }

        $templateProcessor->setValue('puesto_actual.GerenciaMayuscula', $nombreGerencia);

        $templateProcessor->setValue('puesto_nuevo.item_puesto', $incorporaci_personaon->puesto_nuevo->item_puesto);
        $templateProcessor->setValue('puesto_nuevo.denominacion_puestoMayuscula', mb_strtoupper($incorporaci_personaon->puesto_nuevo->denominacion_puesto, 'UTF-8'));

        $nombreDepartamento = mb_strtoupper($incorporaci_personaon->puesto_nuevo->departamento->nombre, 'UTF-8');
        $inici_personaalDepartamento = mb_strtoupper(substr($nombreDepartamento, 0, 1), 'UTF-8');
        if (in_array($inici_personaalDepartamento, ['D'])) {
            $valorDepartamento = 'DEL ' . $nombreDepartamento;
        } elseif (in_array($inici_personaalDepartamento, ['G', 'A', 'U', 'P'])) {
            $valorDepartamento = 'DE LA ' . $nombreDepartamento;
        } else {
            $valorDepartamento = 'DE ' . $nombreDepartamento;
        }
        $templateProcessor->setValue('puesto_nuevo.departamentoMayuscula', $valorDepartamento);

        $templateProcessor->setValue('puesto_nuevo.GerenciaMayuscula', mb_strtoupper($incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre, 'UTF-8'));

        $carbonFechaInfo = Carbon::parse($incorporaci_personaon->fch_informe_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaInfo->locale('es_UY');
        $fechaInfoFormateada = $carbonFechaInfo->isoFormat('LL');
        $templateProcessor->setValue('incorporaci_personaon.fechaInfo', $fechaInfoFormateada);

        $templateProcessor->setValue('incorporaci_personaon.hp', $incorporaci_personaon->hp);
        $templateProcessor->setValue('incorporaci_personaon.codigoNotaMinuta', $incorporaci_personaon->codigo_nota_minuta_incorporacion);
        $templateProcessor->setValue('incorporaci_personaon.ci_personateInfNotaMinuta', $incorporaci_personaon->cite_nota_minuta_incorporacion);

        $carbonFechaNotaMinuta = Carbon::parse($incorporaci_personaon->fch_nota_minuta_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaNotaMinuta->locale('es_UY');
        $fechaNotaMinutaFormateada = $carbonFechaNotaMinuta->isoFormat('LL');
        $templateProcessor->setValue('incorporaci_personaon.fechaNotaMinuta', $fechaNotaMinutaFormateada);

        $carbonFechaRecepci_personaon = Carbon::parse($incorporaci_personaon->fch_recepcion_nota_incorporacion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaRecepci_personaon->locale('es_UY');
        $fechaRecepci_personaonFormateada = $carbonFechaRecepci_personaon->isoFormat('LL');
        $templateProcessor->setValue('incorporaci_personaon.fechaRecepci_personaon', $fechaRecepci_personaonFormateada);

        $templateProcessor->setValue('persona.nombreCompleto', $incorporaci_personaon->persona->nombre_completo);

        $templateProcessor->setValue('puesto_actual.denominacion_puesto', optional($incorporaci_personaon->puesto_actual)->denominacion_puesto);
        if ($incorporaci_personaon && $incorporaci_personaon->puesto_actual) {
            $puestoActual = $incorporaci_personaon->puesto_actual;

            if ($puestoActual->departamento) {
                $departamento = $puestoActual->departamento;

                if ($departamento->Gerencia) {
                    $GerenciaNombre = $departamento->Gerencia->nombre;
                    $templateProcessor->setValue('puesto_actual.Gerencia', $GerenciaNombre);
                }
            }
            $templateProcessor->setValue('puesto_actual.departamento', optional($incorporaci_personaon->puesto_nuevo->departamento)->nombre);
            $templateProcessor->setValue('puesto_actual.salario_puesto', optional($puestoActual)->salario_puesto);
        }

        $templateProcessor->setValue('puesto_nuevo.departamento', $incorporaci_personaon->puesto_nuevo->departamento->nombre);
        $templateProcessor->setValue('puesto_nuevo.Gerencia', $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre);
        $templateProcessor->setValue('puesto_nuevo.salario_puesto', $incorporaci_personaon->puesto_nuevo->salario_puesto);
        $templateProcessor->setValue('puesto_nuevo.salario_puestoLiteral', $incorporaci_personaon->puesto_nuevo->salario_literal_puesto);
        $templateProcessor->setValue('puesto_nuevo.estado', $incorporaci_personaon->puesto_nuevo->estado);
        $templateProcessor->setValue('persona.formaci_personaon', $incorporaci_personaon->persona->formaci_personaon);
        $templateProcessor->setValue('persona.grado', $incorporaci_personaon->persona->grado_academico->nombre ?? 'Valor predeterminado');
        $templateProcessor->setValue('persona.areaformaci_personaon', $incorporaci_personaon->persona->area_formaci_personaon->nombre ?? 'Valor predeterminado');
        $templateProcessor->setValue('persona.instituci_personaon', $incorporaci_personaon->persona->instituci_personaon->nombre ?? 'Valor predeterminado');

        $carbonFechaConclusion = Carbon::parse($incorporaci_personaon->persona->anio_conclusion);
        setlocale(LC_TIME, 'es_UY');
        $carbonFechaConclusion->locale('es_UY');
        $fechaConclusionFormateada = $carbonFechaConclusion->isoFormat('LL');
        $templateProcessor->setValue('persona.conclusion', $fechaConclusionFormateada);

        $templateProcessor->setValue('incorporaci_personaon.respaldoFormaci_personaon', $this->obtenerTextoSegunValorDeFormaci_personaon($incorporaci_personaon->respaldo_formaci_personaon));

        if ($incorporaci_personaon) {
            $puestoNuevo = $incorporaci_personaon->puesto_nuevo;
            if ($puestoNuevo) {
                $requisitosPuestoNuevo = $puestoNuevo->requisitos;
                if ($requisitosPuestoNuevo->isNotEmpty()) {
                    $primerRequisitoPuestoNuevo = $requisitosPuestoNuevo->first();
                    if ($primerRequisitoPuestoNuevo) {
                        $formaci_personaonRequerida = $primerRequisitoPuestoNuevo->formacion_requisito;
                        $exp_personaProfesionalSegunCargo = $primerRequisitoPuestoNuevo->exp_cargo_requisito;
                        $exp_personaRelaci_personaonadoAlArea = $primerRequisitoPuestoNuevo->exp_area_requisito;
                        $exp_personaEnFunci_personaonesDeMando = $primerRequisitoPuestoNuevo->exp_mando_requisito;

                        $templateProcessor->setValue('puesto_nuevo.formaci_personaon', $formaci_personaonRequerida);
                        $templateProcessor->setValue('puesto_nuevo.exp_personaSegunCargo', $exp_personaProfesionalSegunCargo);
                        $templateProcessor->setValue('puesto_nuevo.exp_personaSegunArea', $exp_personaRelaci_personaonadoAlArea);
                        $templateProcessor->setValue('puesto_nuevo.exp_personaEnMando', $exp_personaEnFunci_personaonesDeMando);
                    }
                }
            }
        }

        $templateProcessor->setValue('puesto_nuevo.cumpleexp_personaSegunCargo', $this->obtenerTextoSegunValor($incorporaci_personaon->cumple_exp_profesional_incorporacion));
        $templateProcessor->setValue('puesto_nuevo.cumpleexp_personaSegunArea', $this->obtenerTextoSegunValor($incorporaci_personaon->cumple_exp_especifica_incorporacion));
        $templateProcessor->setValue('puesto_nuevo.cumpleexp_personaEnMando', $this->obtenerTextoSegunValor($incorporaci_personaon->cumple_exp_mando_incorporacion));
        $templateProcessor->setValue('puesto_nuevo.cumpleFormaci_personaon', $this->obtenerTextoSegunValorDeFormaci_personaon($incorporaci_personaon->cumple_formacion_incorporacion));
        $templateProcessor->setValue('persona.profesion', $incorporaci_personaon->persona->profesion);
        $templateProcessor->setValue('puesto_nuevo.salario_literal_puesto', $incorporaci_personaon->puesto_nuevo->salario_literal_puesto);

        $templateProcessor->setValue('puesto_nuevo.denominacion_puesto', $incorporaci_personaon->puesto_nuevo->denominacion_puesto);

        $nombreDepartamento = $incorporaci_personaon->puesto_nuevo->departamento->nombre;
        $inici_personaalDepartamento = substr($nombreDepartamento, 0, 1);
        if (in_array($inici_personaalDepartamento, ['D'])) {
            $valorDepartamento = 'del ' . $nombreDepartamento;
        } elseif (in_array($inici_personaalDepartamento, ['G', 'A', 'U', 'P'])) {
            $valorDepartamento = 'de la ' . $nombreDepartamento;
        } else {
            $valorDepartamento = 'de ' . $nombreDepartamento;
        }
        $templateProcessor->setValue('puesto_nuevo.departamentoRef', $valorDepartamento);

        if (isset($incorporaci_personaon->puesto_actual)) {
            $fileName = 'InfMinutaCambioitem_puesto_' . $incorporaci_personaon->persona->nombre_completo;
        } else {
            $fileName = 'informeminuta.docx_' . $incorporaci_personaon->persona->nombre_completo;
        }
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);

        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }

    //para informe R-0976 compromiso
    public function genFormCompromiso($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);
        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        $disk = Storage::disk('form_templates');
        $pathTemplate = $disk->path('R-0976-01.docx'); // ruta de plantilla
        $templateProcessor = new TemplateProcessor($pathTemplate);

        $templateProcessor->setValue('persona.nombreCompleto', $incorporaci_personaon->persona->nombre_completo);
        $templateProcessor->setValue('persona.ci_persona', $incorporaci_personaon->persona->ci_persona);
        $templateProcessor->setValue('persona.exp_persona', $incorporaci_personaon->persona->exp_persona);

        $nombreGerencia = $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre;
        switch ($nombreGerencia) {
            case 'El Alto':
                $ubicaci_personaon = 'El Alto';
                break;
            case 'Cochabamba':
            case 'GRACO Cochabamba':
                $ubicaci_personaon = 'Cochabamba';
                break;
            case 'Quillacollo':
                $ubicaci_personaon = 'Quillacollo';
                break;
            case 'Santa Cruz I':
            case 'Santa Cruz II':
            case 'GRACO Santa Cruz':
                $ubicaci_personaon = 'Santa Cruz';
                break;
            case 'Montero':
                $ubicaci_personaon = 'Montero';
                break;
            case 'Chuquisaca':
                $ubicaci_personaon = 'Chuquisaca';
                break;
            case 'Tarija':
                $ubicaci_personaon = 'Tarija';
                break;
            case 'Yacuiba':
                $ubicaci_personaon = 'Yacuiba';
                break;
            case 'Oruro':
                $ubicaci_personaon = 'Oruro';
                break;
            case 'Potosí':
                $ubicaci_personaon = 'Potosí';
                break;
            case 'Beni':
                $ubicaci_personaon = 'Beni';
                break;
            case 'Pando':
                $ubicaci_personaon = 'Pando';
                break;
            default:
                $ubicaci_personaon = 'La Paz';
                break;
        }
        $templateProcessor->setValue('ubicaci_personaon', $ubicaci_personaon);

        Carbon::setLocale('es');
        $fechaHoy = Carbon::now();
        $fechaFormateada = $fechaHoy->isoFormat('LL');
        $templateProcessor->setValue('fecha', $fechaFormateada);

        $fileName = 'R-0976-01_' . $incorporaci_personaon->persona->nombre_completo;
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);
        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }

    //para informe R-0921 incompatibilidad
    public function genFormDeclaraci_personaonIncompatibilidad($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);

        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        $incorporaci_personaon->estado_incorporacion = 3;
        $incorporaci_personaon->save();

        $disk = Storage::disk('form_templates');
        $pathTemplate = $disk->path('R-0921-01.docx'); // ruta de plantilla
        $templateProcessor = new TemplateProcessor($pathTemplate);
        $templateProcessor->setValue('persona.nombreCompleto', $incorporaci_personaon->persona->nombre_completo);
        $templateProcessor->setValue('persona.ci_persona', $incorporaci_personaon->persona->ci_persona);
        $templateProcessor->setValue('persona.exp_persona', $incorporaci_personaon->persona->exp_persona);
        $templateProcessor->setValue('puesto_nuevo.item_puesto', $incorporaci_personaon->puesto_nuevo->item_puesto);
        $templateProcessor->setValue('puesto_nuevo.denominacion_puesto', $incorporaci_personaon->puesto_nuevo->denominacion_puesto);
        $nombreDepartamento = $incorporaci_personaon->puesto_nuevo->departamento->nombre;
        $inici_personaalDepartamento = substr($nombreDepartamento, 0, 1);

        if (in_array($inici_personaalDepartamento, ['D'])) {
            $valorDepartamento = 'del ' . $nombreDepartamento;
        } elseif (in_array($inici_personaalDepartamento, ['G', 'A', 'U', 'P'])) {
            $valorDepartamento = 'de la ' . $nombreDepartamento;
        } else {
            $valorDepartamento = 'de ' . $nombreDepartamento;
        }

        $templateProcessor->setValue('puesto_nuevo.departamento', $valorDepartamento);

        $templateProcessor->setValue('puesto_nuevo.Gerencia', $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre);
        $nombreGerencia = $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre;
        switch ($nombreGerencia) {
            case 'El Alto':
                $ubicaci_personaon = 'El Alto';
                break;
            case 'Cochabamba':
            case 'GRACO Cochabamba':
                $ubicaci_personaon = 'Cochabamba';
                break;
            case 'Quillacollo':
                $ubicaci_personaon = 'Quillacollo';
                break;
            case 'Santa Cruz I':
            case 'Santa Cruz II':
            case 'GRACO Santa Cruz':
                $ubicaci_personaon = 'Santa Cruz';
                break;
            case 'Montero':
                $ubicaci_personaon = 'Montero';
                break;
            case 'Chuquisaca':
                $ubicaci_personaon = 'Chuquisaca';
                break;
            case 'Tarija':
                $ubicaci_personaon = 'Tarija';
                break;
            case 'Yacuiba':
                $ubicaci_personaon = 'Yacuiba';
                break;
            case 'Oruro':
                $ubicaci_personaon = 'Oruro';
                break;
            case 'Potosí':
                $ubicaci_personaon = 'Potosí';
                break;
            case 'Beni':
                $ubicaci_personaon = 'Beni';
                break;
            case 'Pando':
                $ubicaci_personaon = 'Pando';
                break;
            default:
                $ubicaci_personaon = 'La Paz';
                break;
        }
        $templateProcessor->setValue('ubicaci_personaon', $ubicaci_personaon);

        Carbon::setLocale('es');
        $fechaHoy = Carbon::now();
        $fechaFormateada = $fechaHoy->isoFormat('LL');
        $templateProcessor->setValue('fecha', $fechaFormateada);

        $fileName = 'R-0921-01_' . $incorporaci_personaon->persona->nombre_completo;
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);

        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }

    //para informe R-0716 etica
    public function genFormEtica($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);

        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        $incorporaci_personaon->estado_incorporacion = 3;
        $incorporaci_personaon->save();

        $disk = Storage::disk('form_templates');
        $pathTemplate = $disk->path('R-0716-01.docx'); // ruta de plantilla

        $templateProcessor = new TemplateProcessor($pathTemplate);
        $templateProcessor->setValue('persona.nombreCompleto', $incorporaci_personaon->persona->nombre_completo);
        $templateProcessor->setValue('persona.ci_persona', $incorporaci_personaon->persona->ci_persona);
        $templateProcessor->setValue('persona.exp_persona', $incorporaci_personaon->persona->exp_persona);
        $templateProcessor->setValue('puesto_nuevo.item_puesto', $incorporaci_personaon->puesto_nuevo->item_puesto);
        $templateProcessor->setValue('puesto_nuevo.denominacion_puesto', $incorporaci_personaon->puesto_nuevo->denominacion_puesto);

        $nombreDepartamento = $incorporaci_personaon->puesto_nuevo->departamento->nombre;
        $inici_personaalDepartamento = substr($nombreDepartamento, 0, 1);

        if (in_array($inici_personaalDepartamento, ['D'])) {
            $valorDepartamento = 'del ' . $nombreDepartamento;
        } elseif (in_array($inici_personaalDepartamento, ['G', 'A', 'U', 'P'])) {
            $valorDepartamento = 'de la ' . $nombreDepartamento;
        } else {
            $valorDepartamento = 'de ' . $nombreDepartamento;
        }

        $templateProcessor->setValue('puesto_nuevo.departamento', $valorDepartamento);

        $templateProcessor->setValue('puesto_nuevo.Gerencia', $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre);
        $nombreGerencia = $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre;
        switch ($nombreGerencia) {
            case 'El Alto':
                $ubicaci_personaon = 'El Alto';
                break;
            case 'Cochabamba':
            case 'GRACO Cochabamba':
                $ubicaci_personaon = 'Cochabamba';
                break;
            case 'Quillacollo':
                $ubicaci_personaon = 'Quillacollo';
                break;
            case 'Santa Cruz I':
            case 'Santa Cruz II':
            case 'GRACO Santa Cruz':
                $ubicaci_personaon = 'Santa Cruz';
                break;
            case 'Montero':
                $ubicaci_personaon = 'Montero';
                break;
            case 'Chuquisaca':
                $ubicaci_personaon = 'Chuquisaca';
                break;
            case 'Tarija':
                $ubicaci_personaon = 'Tarija';
                break;
            case 'Yacuiba':
                $ubicaci_personaon = 'Yacuiba';
                break;
            case 'Oruro':
                $ubicaci_personaon = 'Oruro';
                break;
            case 'Potosí':
                $ubicaci_personaon = 'Potosí';
                break;
            case 'Beni':
                $ubicaci_personaon = 'Beni';
                break;
            case 'Pando':
                $ubicaci_personaon = 'Pando';
                break;
            default:
                $ubicaci_personaon = 'La Paz';
                break;
        }
        $templateProcessor->setValue('ubicaci_personaon', $ubicaci_personaon);

        Carbon::setLocale('es');
        $fechaHoy = Carbon::now();
        $fechaFormateada = $fechaHoy->isoFormat('LL');
        $templateProcessor->setValue('fecha', $fechaFormateada);

        $fileName = 'R-0716-01_' . $incorporaci_personaon->persona->nombre_completo;
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);

        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }

    //para informe R-SGC-0033 confidenci_personaalidad
    public function genFormConfidenci_personaalidad($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);

        if (!isset($incorporaci_personaon)) {
            return response('', 404);
        }

        $incorporaci_personaon->estado_incorporacion = 3;
        $incorporaci_personaon->save();

        $disk = Storage::disk('form_templates');
        $pathTemplate = $disk->path('R-SGC-0033-01.docx'); // ruta de plantilla

        $templateProcessor = new TemplateProcessor($pathTemplate);
        $templateProcessor->setValue('persona.nombreCompleto', $incorporaci_personaon->persona->nombre_completo);
        $templateProcessor->setValue('persona.ci_persona', $incorporaci_personaon->persona->ci_persona);
        $templateProcessor->setValue('persona.exp_persona', $incorporaci_personaon->persona->exp_persona);
        $templateProcessor->setValue('puesto_nuevo.denominacion_puesto', $incorporaci_personaon->puesto_nuevo->denominacion_puesto);

        $nombreDepartamento = $incorporaci_personaon->puesto_nuevo->departamento->nombre;
        $inici_personaalDepartamento = substr($nombreDepartamento, 0, 1);

        if (in_array($inici_personaalDepartamento, ['D'])) {
            $valorDepartamento = 'del ' . $nombreDepartamento;
        } elseif (in_array($inici_personaalDepartamento, ['G', 'A', 'U', 'P'])) {
            $valorDepartamento = 'de la ' . $nombreDepartamento;
        } else {
            $valorDepartamento = 'de ' . $nombreDepartamento;
        }

        $templateProcessor->setValue('puesto_nuevo.departamento', $valorDepartamento);

        $templateProcessor->setValue('puesto_nuevo.Gerencia', $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre);
        $nombreGerencia = $incorporaci_personaon->puesto_nuevo->departamento->Gerencia->nombre;
        switch ($nombreGerencia) {
            case 'El Alto':
                $ubicaci_personaon = 'El Alto';
                break;
            case 'Cochabamba':
            case 'GRACO Cochabamba':
                $ubicaci_personaon = 'Cochabamba';
                break;
            case 'Quillacollo':
                $ubicaci_personaon = 'Quillacollo';
                break;
            case 'Santa Cruz I':
            case 'Santa Cruz II':
            case 'GRACO Santa Cruz':
                $ubicaci_personaon = 'Santa Cruz';
                break;
            case 'Montero':
                $ubicaci_personaon = 'Montero';
                break;
            case 'Chuquisaca':
                $ubicaci_personaon = 'Chuquisaca';
                break;
            case 'Tarija':
                $ubicaci_personaon = 'Tarija';
                break;
            case 'Yacuiba':
                $ubicaci_personaon = 'Yacuiba';
                break;
            case 'Oruro':
                $ubicaci_personaon = 'Oruro';
                break;
            case 'Potosí':
                $ubicaci_personaon = 'Potosí';
                break;
            case 'Beni':
                $ubicaci_personaon = 'Beni';
                break;
            case 'Pando':
                $ubicaci_personaon = 'Pando';
                break;
            default:
                $ubicaci_personaon = 'La Paz';
                break;
        }
        $templateProcessor->setValue('ubicaci_personaon', $ubicaci_personaon);

        Carbon::setLocale('es');
        $fechaHoy = Carbon::now();
        $fechaFormateada = $fechaHoy->isoFormat('LL');
        $templateProcessor->setValue('fecha', $fechaFormateada);

        $fileName = 'R-SGC-0033-01_' . $incorporaci_personaon->persona->nombre_completo;
        $savedPath = $disk->path('generados/') . $fileName . '.docx';
        $templateProcessor->saveAs($savedPath);

        return response()->json(['incorporaci_personaon' => $incorporaci_personaon, 'filePath' => $fileName . '.docx']);
    }

    public function downloadEvalForm($fileName)
    {
        $disk = Storage::disk('form_templates');
        return response()->download($disk->path('generados/') . $fileName)->deleteFileAfterSend(true);
    }

    public function observacion_incorporacion($incorporaci_personaonId, $calificaci_personaon)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);
        $incorporaci_personaon->evaluacion_estado_incorporacion = $calificaci_personaon == 1 ? 3 : 4;
        $incorporaci_personaon->save();
        return response()->json($incorporaci_personaon);
    }

    public function evaluaci_personaonFinalizar($incorporaci_personaonId)
    {
        $incorporaci_personaon = Incorporaci_personaon::find($incorporaci_personaonId);
        $incorporaci_personaon->paso_incorporacion = $incorporaci_personaon->evaluacion_estado_incorporacion != 4 ? 2 : 1;
        $incorporaci_personaon->evaluacion_estado_incorporacion = 5;
        $incorporaci_personaon->save();
        return response()->json($incorporaci_personaon);
    }

    // Actualizar cambio de item_puesto
    public function incActualizar(Request $request, $incorporaci_personaonId)
    {
        $incorporaci_personaonForm = Incorporaci_personaon::find($incorporaci_personaonId);

        if (!$incorporaci_personaonForm) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $dataPersona = $request->input('persona');
        $persona = Persona::find($dataPersona['id']);
        if ($persona) {
            if ($dataPersona['anio_conclusion']) {
                $anioConclusion = Carbon::parse($dataPersona['anio_conclusion'])->setTimezone('UTC')->format('Y-m-d');
                $persona->anio_conclusion = $anioConclusion;
            }
            if ($dataPersona['fch_nacimiento_persona']) {
                $fechaNacFormated = Carbon::parse($dataPersona['fch_nacimiento_persona'])->setTimezone('UTC')->format('Y-m-d');
                $persona->fch_nacimiento_persona = $fechaNacFormated;
            }
            $persona->grado_academico_id = $dataPersona['grado_academico_id'] ?? null;
            $persona->area_formacion_id = $dataPersona['area_formacion_id'] ?? null;
            $persona->institucion_id = $dataPersona['institucion_id'] ?? null;
            $persona->con_respaldo = $dataPersona['con_respaldo'] ?? null;
            $persona->nombre_persona = $dataPersona['nombre_persona'] ?? null;
            $persona->primer_apellido_persona = $dataPersona['primer_apellido_persona'] ?? null;
            $persona->segundo_apellido_persona = $dataPersona['segundo_apellido_persona'];
            $persona->nombre_completo = $dataPersona['nombre_persona'] . " " .
                $dataPersona['primer_apellido_persona'] . " " .
                $dataPersona['segundo_apellido_persona'];
            $persona->ci_persona = $dataPersona['ci_persona'] ?? null;
            $persona->genero_persona = $dataPersona['genero_persona'] ?? null;
            $persona->save();
        }

        $incorporaci_personaonForm->cumple_exp_profesional_incorporacion = $request->input('cumple_exp_profesional_incorporacion');
        $incorporaci_personaonForm->cumple_exp_especifica_incorporacion = $request->input('cumple_exp_especifica_incorporacion');
        $incorporaci_personaonForm->cumple_exp_mando_incorporacion = $request->input('cumple_exp_mando_incorporacion');
        $incorporaci_personaonForm->cumple_formacion_incorporacion = $request->input('cumple_formacion_incorporacion');

        if ($request->input('fch_incorporacion')) {
            $fechaIncFormated = Carbon::parse($request->input('fch_incorporacion'))->setTimezone('UTC')->format('Y-m-d');
            $incorporaci_personaonForm->fch_incorporacion = $fechaIncFormated;
        }
        $incorporaci_personaonForm->hp = $request->input('hp');
        $incorporaci_personaonForm->cite_nota_minuta_incorporacion = $request->input('cite_nota_minuta_incorporacion');
        $incorporaci_personaonForm->codigo_nota_minuta_incorporacion = $request->input('codigo_nota_minuta_incorporacion');
        if ($request->input('fch_nota_minuta_incorporacion')) {
            $fch_nota_minuta_incorporacion = Carbon::parse($request->input('fch_nota_minuta_incorporacion'))->setTimezone('UTC')->format('Y-m-d');
            $incorporaci_personaonForm->fch_nota_minuta_incorporacion = $fch_nota_minuta_incorporacion;
        }
        if ($request->input('fch_recepcion_nota_incorporacion')) {
            $fch_recepcion_nota_incorporacion = Carbon::parse($request->input('fch_recepcion_nota_incorporacion'))->setTimezone('UTC')->format('Y-m-d');
            $incorporaci_personaonForm->fch_recepcion_nota_incorporacion = $fch_recepcion_nota_incorporacion;
        }
        $incorporaci_personaonForm->cite_informe_incorporacion = $request->input('cite_informe_incorporacion');
        if ($request->input('fch_informe_incorporacion')) {
            $fch_informe_incorporacion = Carbon::parse($request->input('fch_informe_incorporacion'))->setTimezone('UTC')->format('Y-m-d');
            $incorporaci_personaonForm->fch_informe_incorporacion = $fch_informe_incorporacion;
        }
        $incorporaci_personaonForm->cite_memorandum_incorporacion = $request->input('cite_memorandum_incorporacion');
        $incorporaci_personaonForm->codigo_memorandum_incorporacion = $request->input('codigo_memorandum_incorporacion');
        if ($request->input('fch_memorandum_incorporacion')) {
            $fch_memorandum_incorporacion = Carbon::parse($request->input('fch_memorandum_incorporacion'))->setTimezone('UTC')->format('Y-m-d');
            $incorporaci_personaonForm->fch_memorandum_incorporacion = $fch_memorandum_incorporacion;
        }
        $incorporaci_personaonForm->cite_rap_incorporacion = $request->input('cite_rap_incorporacion');
        $incorporaci_personaonForm->codigo_rap_incorporacion = $request->input('codigo_rap_incorporacion');
        if ($request->input('fch_rap_incorporacion')) {
            $fch_rap_incorporacion = Carbon::parse($request->input('fch_rap_incorporacion'))->setTimezone('UTC')->format('Y-m-d');
            $incorporaci_personaonForm->fch_rap_incorporacion = $fch_rap_incorporacion;
        }
        $incorporaci_personaonForm->responsable = $request->input('responsable');

        if (
            $request->input('cite_informe_incorporacion') &&
            $request->input('fch_informe_incorporacion') &&
            $request->input('cite_memorandum_incorporacion') &&
            $request->input('codigo_memorandum_incorporacion') &&
            $request->input('fch_memorandum_incorporacion') &&
            $request->input('cite_rap_incorporacion') &&
            $request->input('codigo_rap_incorporacion') &&
            $request->input('fch_rap_incorporacion')
        ) {
            $incorporaci_personaonForm->estado_incorporacion = 2;
        }
        $incorporaci_personaonForm->save();
        $incorporaci_personaonForm->persona;
        $incorporaci_personaonForm->puesto_actual;
        $incorporaci_personaonForm->puesto_nuevo;
        return $this->sendSuccess($incorporaci_personaonForm);
    }

    //funci_personaones de ayuda para ver si cumple o no cumple los requisit
    public function obtenerTextoSegunValor($valor)
    {
        switch ($valor) {
            case 0:
                return 'No';
            case 1:
                return 'Si';
            case 2:
                return 'No corresponde';
            default:
                return 'Valor no reconoci_personado';
        }
    }

    //funci_personaones de ayuda para ver si cumple o no cumple la formaci_personaon
    public function obtenerTextoSegunValorDeFormaci_personaon($valor)
    {
        switch ($valor) {
            case 0:
                return 'No Cumple';
            case 1:
                return 'Cumple';
            default:
                return 'Valor no reconoci_personado';
        }
    }
}
