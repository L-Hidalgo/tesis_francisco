<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dde_incorporaciones', function (Blueprint $table) {
            $table->integer('id_incorporacion')->unsigned()->autoIncrement();
            $table->integer('paso_incorporacion')->default(1); // 1: evaluaci_personaon, 2: incorporaci_personaon, 3:seguimiento
            // SECTION: EVALUAci_personaON
            $table->integer('persona_id')->nullable()->unsigned();
            $table->integer('puesto_actual_id')->nullable()->unsigned(); //item_puesto nuevo
            $table->integer('puesto_nuevo_id')->nullable()->unsigned(); //item_puesto nuevo
            $table->integer('evaluacion_estado_incorporacion')->default(1)->unsigned(); // 1:inici_personao, 2: con_formulario, 3: cumple, 4: no_cumple, finalizado
            // !SECTION
            // SECTION: INCORPORAci_personaON
            $table->integer('estado_incorporacion')->default(1); // 1:sin_registro , 2: con_registro, 3: finalizado
            $table->string('gerente_acta_posicion_incorporacion', 20)->default(2);
            $table->integer('cumple_exp_profesional_incorporacion')->default(2);
            $table->integer('cumple_exp_especifica_incorporacion' )->default(2);
            $table->integer('cumple_exp_mando_incorporacion')->default(2);
            $table->integer('cumple_formacion_incorporacion')->default(0);
           // $table->integer('respaldo_documentos_incorporaci_personaones')->default(0);
            $table->date('fch_incorporacion')->nullable();
            $table->string('hp_incorporacion', 10)->nullable();
            $table->string('cite_nota_minuta_incorporacion', 10)->nullable();
            $table->string('codigo_nota_minuta_incorporacion', 10)->nullable();
            $table->date('fch_nota_minuta_incorporacion')->nullable();
            $table->date('fch_recepcion_nota_incorporacion')->nullable();
            $table->string('cite_informe_incorporacion', 10)->nullable();
            $table->date('fch_informe_incorporacion')->nullable();
            $table->string('cite_memorandum_incorporacion', 10)->nullable();
            $table->string('codigo_memorandum_incorporacion', 10)->nullable();
            $table->date('fch_memorandum_incorporacion')->nullable();
            $table->string('cite_rap_incorporacion', 10)->nullable();
            $table->string('codigo_rap_incorporacion', 10)->nullable();
            $table->date('fch_rap_incorporacion')->nullable();
            $table->string('observacion_incorporacion', 10)->nullable();
          //  $table->integer('usuario_id_incorporaci_personaones')->nullable();
            // !SECTION
            $table->foreign('persona_id')->references('id_persona')->on('dde_personas');
            $table->foreign('puesto_actual_id')->references('id_puesto')->on('dde_puestos');
            $table->foreign('puesto_nuevo_id')->references('id_puesto')->on('dde_puestos');
            //$table->foreignId('usuario_id')->nullable()->constrained('users');
            $table->timestamps();
            $table->timestamp('fecha_inicio')->nullable()->default(null);
            $table->timestamp('fecha_fin')->nullable()->default(null);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dde_incorporaciones');
    }
};
