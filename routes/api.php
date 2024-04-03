<?php

use App\Http\Controllers\Api\AreaFormaci_personaonController;
use App\Http\Controllers\Api\GradoAcademicoController;
use App\Http\Controllers\Api\Instituci_personaonDeEstudioController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PuestoController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ImportarExcelController;
use App\Http\Controllers\ImportarImagesController;
use App\Http\Controllers\Incorporaci_personaonesController;
use App\Http\Controllers\FuncionarioController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::apiResource('/user', UserController::class);
    Route::apiResource('/role', RoleController::class);
    Route::apiResource('/permissions', PermissionController::class);
    // routes puesto
    Route::get('/puestos/select', [PuestoController::class, 'getList'])->name('puesto.list');
    Route::get('/puestos/{puestoId}', [PuestoController::class, 'getById'])->name('puesto.by.item_puesto');
    // importar por api

    Route::post('/planilla', [ImportarExcelController::class, 'importExcel'])->name('planilla');
    Route::post('/importar-imagenes', [ImportarImagesController::class, 'importImagenes'])->name('importar.imagenes');
    /* -------------------------------------- Area de formaci_personaon ------------------------------------- */
    Route::post('/area-formaci_personaon', [AreaFormaci_personaonController::class, 'crearAreaFormaci_personaon'])->name('areaformaci_personaon.crear');
    Route::get('/area-formaci_personaon', [AreaFormaci_personaonController::class, 'listar'])->name('areaformaci_personaon.listar');
    /* --------------------------------------- Grado academico -------------------------------------- */
    Route::post('/grado-academico', [GradoAcademicoController::class, 'crear'])->name('gradoacademico.crear');
    Route::get('/grado-academico', [GradoAcademicoController::class, 'listar'])->name('gradoacademico.listar');
    /* ----------------------------------- Instituci_personaon de estudio ----------------------------------- */
    Route::post('/instituci_personaon-estudio', [Instituci_personaonDeEstudioController::class, 'crear'])->name('dde_instituci_personaonestudio.crear');
    Route::get('/instituci_personaon-estudio', [Instituci_personaonDeEstudioController::class, 'listar'])->name('dde_instituci_personaonestudio.listar');
});



Route::post('/persona-puesto/listar', [FuncionarioController::class, 'listarPuesto'])->name('importaci_personaones.buscar');
Route::post('/persona-puesto/filtrar', [FuncionarioController::class, 'filtrarAutoComplete'])->name('importaci_personaones.filtrar-autocomplete');
Route::get('/persona-puesto/{FuncionarioId}', [FuncionarioController::class, 'obtenerInfoDeFuncionario'])->name('persona.puest.byid');

// Incorporaci_personaones
Route::post('/incorporaci_personaones/listar', [Incorporaci_personaonesController::class, 'listarIncorporaci_personaones'])->name('incorporaci_personaones-listar');
Route::post('/incorporaci_personaones/filtrar', [Incorporaci_personaonesController::class, 'filtrarAutoComplete'])->name('incorporaci_personaones-filtrar');
Route::get('/incorporaci_personaones/{item_puesto}/buscar-item_puesto', [Incorporaci_personaonesController::class, 'buscaritem_puestoApi'])->name('buscar-item_puesto');
Route::post('/incorporaci_personaones/buscar-persona', [Incorporaci_personaonesController::class, 'buscarPersona'])->name('buscar-persona');
Route::post('/incorporaci_personaones/create-evaluaci_personaon', [Incorporaci_personaonesController::class, 'crearEvaluaci_personaon'])->name('eval.crear');
Route::post('/incorporaci_personaones/create-incorporaci_personaon', [Incorporaci_personaonesController::class, 'crearIncorporaci_personaon'])->name('incorp.crear');
Route::post('/incorporaci_personaones/create-gradoAcademico', [Incorporaci_personaonesController::class, 'crearGradoAcademico'])->name('eval.crearGradoAcademico');
Route::post('/incorporaci_personaones/create-instituci_personaon', [Incorporaci_personaonesController::class, 'crearInstituci_personaon'])->name('eval.crearInstituci_personaon');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/observacion_incorporacion/{calificaci_personaon}', [Incorporaci_personaonesController::class, 'observacion_incorporacion'])->name('eval.observacion_incorporacion');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/eval-finalizar', [Incorporaci_personaonesController::class, 'evaluaci_personaonFinalizar'])->name('eval.finalizar');
Route::patch('/incorporaci_personaones/{incorporaci_personaonId}/inc-actualizar', [Incorporaci_personaonesController::class, 'incActualizar'])->name('inc.actualizar');
//links de formularios para Cambio de item_puesto
//durante la evaluaci_personaon
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-evaluaci_personaon', [Incorporaci_personaonesController::class, 'generarFormularioEvalucaion'])->name('eval.gen-form-evaluaci_personaon');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-cambio-item_puesto', [Incorporaci_personaonesController::class, 'generarFormularioCambioitem_puesto'])->name('eval.gen-form-cambio-item_puesto');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-documentos-cambio-item_puesto', [Incorporaci_personaonesController::class, 'generarFormularioDocumentosCambioitem_puesto'])->name('eval.gen-form-documentos-cambio-item_puesto');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-evalR0078', [Incorporaci_personaonesController::class, 'generarFormularioEvalR0078'])->name('eval.gen-form-evalR0078');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-evalR1401', [Incorporaci_personaonesController::class, 'genFormEvalR1401'])->name('eval.gen-form-evalR1401');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-RemisionDeDocumentos', [Incorporaci_personaonesController::class, 'genFormRemisionDeDocumentos'])->name('inc.genFormRemisionDeDocumentos');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-RAP', [Incorporaci_personaonesController::class, 'genFormRAP'])->name('inc.genFormRAP');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-memo', [Incorporaci_personaonesController::class, 'genFormMemo'])->name('inc.genFormMemo');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-acta-de-posesion', [Incorporaci_personaonesController::class, 'genFormActaDePosesion'])->name('inc.genFormActaDePosesion');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-acta-de-entrega', [Incorporaci_personaonesController::class, 'genFormActaDeEntrega'])->name('inc.genFormActaDeEntrega');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-informe-con-nota', [Incorporaci_personaonesController::class, 'genFormInformeNota'])->name('inc.genFormInformeNota');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-informe-con-minuta', [Incorporaci_personaonesController::class, 'genFormInformeMinuta'])->name('inc.genFormInformeMinuta');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-compromiso', [Incorporaci_personaonesController::class, 'genFormCompromiso'])->name('inc.genFormCompromiso');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-declaraci_personaon-incompatibilidad', [Incorporaci_personaonesController::class, 'genFormDeclaraci_personaonIncompatibilidad'])->name('inc.genFormDeclaraci_personaonIncompatibilidad');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-etica', [Incorporaci_personaonesController::class, 'genFormEtica'])->name('inc.genFormEtica');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-confidenci_personaalidad', [Incorporaci_personaonesController::class, 'genFormConfidenci_personaalidad'])->name('inc.genFormConfidenci_personaalidad');




//durante la incorporaci_personaon
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-multiple', [Incorporaci_personaonesController::class, 'genFormMultiple'])->name('inc.genFormMultiple');

Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-acta-compromiso-confidenci_personaalidad', [Incorporaci_personaonesController::class, 'genFormActaCompromisoConfidenci_personaalidad'])->name('inc.genFormActaCompromisoConfidenci_personaalidad');
Route::post('/incorporaci_personaones/{incorporaci_personaonId}/gen-form-idioma', [Incorporaci_personaonesController::class, 'genFormActaIdioma'])->name('inc.genFormActaIdioma');
