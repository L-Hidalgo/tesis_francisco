<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
 {

    public function up(): void
    {
        Schema::create('dde_requisitos', function (Blueprint $table) {
            $table->integer('id_requisito')->unsigned()->autoIncrement();
            $table->text('formacion_requisito')->nullable();
            $table->text('exp_cargo_requisito')->nullable();
            $table->text('exp_area_requisito')->nullable();
            $table->text('exp_mando_requisito')->nullable();
            $table->integer('puesto_id')->unsigned();
            $table->foreign('puesto_id')->references('id_puesto')->on('dde_puestos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dde_requisitos');
    }
};
