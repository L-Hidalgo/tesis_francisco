<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dde_personas', function (Blueprint $table) {
            $table->integer('id_persona')->unsigned()->autoIncrement();
            $table->string('ci_persona', 15)->unique();
            $table->string('exp_persona', 3)->unique();
            $table->string('primer_apellido_persona', 60)->nullable();
            $table->string('segundo_apellido_persona', 60)->nullable();
            $table->string('nombre_persona', 60)->nullable();
            $table->string('profesion_persona', 50)->nullable();
            $table->string('genero_persona', 1);
            $table->date('fch_nacimiento_persona')->nullable();
            $table->string('telefono_persona', 15)->nullable();
            $table->timestamps();
            $table->timestamp('fecha_inicio')->nullable()->default(null);
            $table->timestamp('fecha_fin')->nullable()->default(null);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dde_personas');
    }
};
