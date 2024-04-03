<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('dde_grado_academicos', function (Blueprint $table) {
            $table->integer('id_grado_academico')->unsigned()->autoIncrement();
            $table->string('nombre_grado_academico', 60);
            $table->timestamps();
            $table->timestamp('fecha_inicio')->nullable()->default(null);
            $table->timestamp('fecha_fin')->nullable()->default(null);
        });
/*
        DB::table('dde_grado_academicos')->insert([
            ['nombre_grado_academico' => 'Bachiller'],
            ['nombre_grado_academico' => 'Egresado'],
            ['nombre_grado_academico' => 'Estudiante Universitario'],
            ['nombre_grado_academico' => 'Licenci_personaatura'],
            ['nombre_grado_academico' => 'Tecnico Medio'],
            ['nombre_grado_academico' => 'Tecnico Superior'],
        ]);*/
    }

    public function down(): void
    {
        Schema::dropIfExists('dde_grado_academicos');
    }
};
