<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('dde_departamentos', function (Blueprint $table) {
            $table->integer('id_departamento')->unsigned()->autoIncrement();
            $table->string('nombre_departamento', 50)->nullable();
            $table->integer('gerencia_id')->unsigned();
            $table->foreign('gerencia_id')->references('id_Gerencia')->on('dde_gerencias');
            $table->timestamps();
            $table->timestamp('fecha_inicio')->nullable()->default(null);
            $table->timestamp('fecha_fin')->nullable()->default(null);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dde_departamentos');
    }
}
;
