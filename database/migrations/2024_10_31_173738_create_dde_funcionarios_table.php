<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('dde_funcionarios')) {
            Schema::create('dde_funcionarios', function (Blueprint $table) {
                $table->integer('id_funcionario')->unsigned()->autoIncrement();
                $table->string('codigo_file_funcionario', 10)->nullable();
                $table->date('fch_inicio_sin_funcionario')->nullable();
                $table->date('fch_fin_sin_funcionario')->nullable();
                $table->date('fch_inicio_puesto_funcionario')->nullable();
                $table->date('fch_fin_puesto_funcionario')->nullable();
                $table->string('motivo_baja_funcionario', 50)->nullable(); 
                $table->integer('puesto_id')->unsigned();
                $table->integer('persona_id')->unsigned();
                $table->foreign('puesto_id')->references('id_puesto')->on('dde_puestos');
                $table->foreign('persona_id')->references('id_persona')->on('dde_personas');
                $table->timestamps();
                $table->timestamp('fecha_inicio')->nullable()->default(null);
                $table->timestamp('fecha_fin')->nullable()->default(null);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dde_funcionarios');
    }
};
