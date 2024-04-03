<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dde_area_formaciones', function (Blueprint $table) {
            $table->integer('id_area_formacion')->unsigned()->autoIncrement();
            $table->string('nombre_area_formacion', 60);
            $table->timestamps();
            $table->timestamp('fecha_fin')->nullable()->default(null);
            $table->timestamp('fecha_fin')->nullable()->default(null);
        });
/*
        DB::table('dde_area_formaci_personaones')->insert([
            ['nombre_area_formaci_personaon' => 'Aministraci_personaon de Empresas'],
            ['nombre_area_formaci_personaon' => 'Administraci_personaon Publica'],
            ['nombre_area_formaci_personaon' => 'Auditoria Publica'],
            ['nombre_area_formaci_personaon' => 'ci_personaenci_personaas Juridicas'],
            ['nombre_area_formaci_personaon' => 'ci_personaenci_personaas Polici_personatas'],
            ['nombre_area_formaci_personaon' => 'Contaduria Publica'],
            ['nombre_area_formaci_personaon' => 'Derecho'],
            ['nombre_area_formaci_personaon' => 'Economia'],
            ['nombre_area_formaci_personaon' => 'Humanidades'],
            ['nombre_area_formaci_personaon' => 'Ingenieria Comerci_personaal'],
            ['nombre_area_formaci_personaon' => 'Ingeneria de Sistemas'],
            ['nombre_area_formaci_personaon' => 'Ingenieria Financi_personaera'],
            ['nombre_area_formaci_personaon' => 'Ingenieria Informatica'],
            ['nombre_area_formaci_personaon' => 'Ingenieria Industrial'],
            ['nombre_area_formaci_personaon' => 'Psicologia'],
            ['nombre_area_formaci_personaon' => 'Telecominaci_personaones'],
            ['nombre_area_formaci_personaon' => 'Trabajo Soci_personaal'],
            ['nombre_area_formaci_personaon' => 'Secretariado Ejecutivo'],


        ]);*/
    }

    public function down(): void
    {
        Schema::dropIfExists('dde_area_formaci_personaones');
    }
};
