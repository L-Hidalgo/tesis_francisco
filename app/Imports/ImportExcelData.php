<?php

namespace App\Imports;

use App\Models\Gerencia;
use App\Models\Departamento;
use App\Models\Puesto;
use App\Models\Persona;
use App\Models\Funcionario;
use App\Models\Requisito;
/*use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;*/
use Carbon\Carbon;

class ImportExcelData implements ToModel, WithStartRow
{

    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        $gerencia = $this->migrateGerencia($row[0], $row[2]);

        $departamento = $this->migrarDepartamento($row[3], $gerencia->id);

        $puesto = $this->migrarPuesto($row[1], $row[4], $row[5], $row[6], $row[42], $departamento->id);

        if (isset($row[7]) && isset($row[12])) {
            $persona = $this->migrarPersona(
                $row[7],
                $row[9],
                $row[10],
                $row[11],
                $row[12],
                $row[15],
                $row[16],
                $row[17],
                $row[19],
            );
            $puesto->persona_actual_id = $persona->id; //ver esto del estado
            $puesto->estado = 'OCUPADO'; //estado
            $puesto->save();
            $Funcionario = $this->migrarFuncionario(
                $row[18], //file
                $row[20], //fecha inicio en el sin
                $row[21], //fecha inicip en el cargo
                $puesto->id,
                $persona->id);
        }
        $requisitos = $this->migrarRequisito($puesto->id, $row[43], $row[44], $row[45], $row[46]);
    }

    public function migrateGerencia($abreviatura,$nombre): Gerencia
    {
        $gerencia = Gerencia::where('nombre_gerencia', $nombre)->first();
        if (!isset($gerencia)) {
            $gerencia = Gerencia::create([
                'nombre_gerencia' => $nombre,
                'abreviatura_gerencia' =>  $abreviatura
            ]);
        }
        return $gerencia;
    }

    public function migrarDepartamento($nombre, $gerenciaId): Departamento
    {
        $departamento = Departamento::where('nombre_departamento', $nombre)->where('gerencia_id', $gerenciaId)->first();
        if (!isset($departamento)) {
            $departamento = Departamento::create([
                'nombre_departamento' => $nombre,
                'gerencia_id' => $gerenciaId
            ]);
        }
        return $departamento;
    }

    public function migrarPuesto(
        $item,
        $denominacion,
        $salario,
        $salario_literal,
        $objetivo,$departamentoId): Puesto
    {
        $puesto = Puesto::where('item', $item)->first();
        if (!isset($puesto)) {
            $puesto = Puesto::create([
                'item_puesto' => $item,
                'denominacion_puesto' => $denominacion,
                'salario_puesto' => $salario,
                'salario_literal_puesto' => $salario_literal,
                'objetivo_puesto' => $objetivo,
                'departamento_id' => $departamentoId,
                'estado' => 'ACEFALIA', // esto ver del estado 
            ]);
        } else {
            $puesto->denominacion = $denominacion;
            $puesto->salario = $salario;
            $puesto->salario_literal = $salario_literal;
            $puesto->objetivo = $objetivo;
            $puesto->departamento_id = $departamentoId;
            $puesto->estado = 'ACEFALIA';//ver del estado
            $puesto->persona_actual_id = null;
            $puesto->save();
        }
        return $puesto;
    }

    public function migrarPersona(
        $ci,  // 7
        $exp, // 9
        $primerApellido, // 10
        $segundoApellido, // 11
        $nombres,         // 12
        $formacion,       // 15
        $sexo,            // 16
        $fechaNacimiento, // 17
        $telefono,        // 19
    ): Persona
    {
        $persona = Persona::where('ci', $ci)->first();
        if (!isset($persona)) {
            // formato fecha Nac
            $timestamp = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($fechaNacimiento);
            $fechaNacimiento = Carbon::createFromTimestamp($timestamp)->format('Y-m-d');
           
            $persona = Persona::create([
                'ci_persona' => $ci,
                'exp_persona' => $exp,
                'primer_apellido_persona' => $primerApellido,
                'segundo_apellido_persona' => $segundoApellido,
                'nombre_persona' => $nombres,
                'profesion_persona' => $formacion,
                'genero_persona' => $sexo,
                'fch_nacimiento_persona' => $fechaNacimiento,
                'telefono_persona' => $telefono,
            ]);
        }
        return $persona;
    }

    public function migrarFuncionario(
        $codigoFileFuncionario,
        $fchInicioSinFuncionario,
        $fchInicioPuestoFuncionario,
        $puestoId,
        $personaId,
    ): Funcionario
    {
        $persona = Persona::find($personaId);
        $puesto = Puesto::find($puestoId);

        if (!$persona || !$puesto) {
            return null;
        }

        $fileAc = $puesto->item . '-' . $persona->ci;

        $Funcionario = Funcionario::where('codigo_file_funcionario', $codigoFileFuncionario)
            ->where('puesto_id', $puestoId)
            ->where('persona_id', $personaId)
            ->first();

        if (!isset($Funcionario)) {

             // formato fecha inicio en SIN
             $timestampfsin = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($fchInicioSinFuncionario);
             $fchInicioSinFuncionario = Carbon::createFromTimestamp($timestampfsin)->format('Y-m-d');

            $timestampFechaInicio = $this->convertirFechaATimestamp($fchInicioPuestoFuncionario);
            $fchInicioPuestoFuncionario = Carbon::createFromTimestamp($timestampFechaInicio)->format('Y-m-d');

            $Funcionario = Funcionario::create([
                'codigo_file_funcionario' => $codigoFileFuncionario,
                'fch_inicio_sin_funcionario' => $fchInicioSinFuncionario,
                'fch_inicio_puesto_funcionario' => $fchInicioPuestoFuncionario,
                'puesto_id' => $puesto->id,
                'persona_id' => $persona->id,
            ]);
        }
        return $Funcionario;
    }

    public function migrarRequisito($puesto_id, $formacionRequerida, $experienciaProfesionalSegunCargo, $experienciaRelacionadoAlArea, $experienciaEnFuncionesDeMando): Requisito
    {
        $requisitos = Requisito::where('puesto_id', $puesto_id)->first();
        if (!isset($requisitos)) {
            $requisitos = Requisito::create([
                'puesto_id' => $puesto_id,
                'formacion_requisito' => $formacionRequerida,
                'exp_cargo_requisito' => $experienciaProfesionalSegunCargo,
                'exp_area_requisito' => $experienciaRelacionadoAlArea,
                'exp_mando_requisito' => $experienciaEnFuncionesDeMando
            ]);
        }
        return $requisitos;
    }

    private function convertirFechaATimestamp($fecha)
    {
        try {
            $carbonDate = Carbon::createFromFormat('d/m/Y', $fecha);

            if ($carbonDate instanceof Carbon) {
                return $carbonDate->getTimestamp();
            }
        } catch (\Exception $e) {
            error_log("Error al convertir fecha: " . $e->getMessage());
        }

        try {
            $excelDate = intval($fecha);
            $carbonDate = Carbon::createFromTimestamp(($excelDate - 25569) * 86400);

            if ($carbonDate instanceof Carbon) {
                return $carbonDate->getTimestamp();
            }
        } catch (\Exception $e) {
            error_log("Error al convertir número de serie de Excel: " . $e->getMessage());
        }

        error_log("No se pudo convertir la fecha: $fecha");
        return 0;
    }
}
