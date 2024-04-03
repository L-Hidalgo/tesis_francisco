<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dde_dde_instituciones', function (Blueprint $table) {
            $table->integer('id_institucion')->unsigned()->autoIncrement();
            $table->string('nombre_institucion', 60);
            $table->timestamps();
            $table->timestamp('fecha_inicio')->nullable()->default(null);
            $table->timestamp('fecha_fin')->nullable()->default(null);
        });

        /*DB::table('dde_dde_instituci_personaones')->insert([
            ['nombre' => 'Instituto Técnico Boliviano Japonés (INBOLJAP)'],
            ['nombre' => 'Instituto Técnico Boliviano Suizo (TBS)'],
            ['nombre' => 'Instituto Técnico Naci_personaonal de Comerci_personao (INCOS)'],
            ['nombre' => 'Instituto Técnologico INFOCAL'],
            ['nombre' => 'Universidad Andina Simón Bolívar'],
            ['nombre' => 'Universidad Autónoma del Beni José Ballivián'],
            ['nombre' => 'Univ. Autónoma Gabriel Rene Moreno (UAGRM)'],
            ['nombre' => 'Universidad Autónoma Juan Misael Saracho (UAJMS)'],
            ['nombre' => 'Universidad Autónoma Tomás Frías (UATF)'],
            ['nombre' => 'Universidad de Aquino Bolivia '],
            ['nombre' => 'Universidad Bolivia de Informática'],
            ['nombre' => 'Universidad Católica Boliviana San Pablo'],
            ['nombre' => 'Universidad Cristiana de Bolivia (UCEBOL)'],
            ['nombre' => 'Universidad de la Amazonía Boliviana'],
            ['nombre' => 'Universidad de los Andes (UDELOSANDES)'],
            ['nombre' => 'Universidad Indígena Boliviana Aymara Túpac Katari'],
            ['nombre' => 'Universidad La Salle (ULS) '],
            ['nombre' => 'Universidad Loyola '],
            ['nombre' => 'Universidad Mayor de San Andrés (UMSA) '],
            ['nombre' => 'Universidad Mayor de San Simón'],
            ['nombre' => 'Universidad Mayor Real y Pontifici_personaa San Franci_personasco Xavier de Chuquisaca'],
            ['nombre' => 'Universidad Naci_personaonal del Oriente (UNO) '],
            ['nombre' => 'Universidad Naci_personaonal Siglo XX (UNSXX)'],
            ['nombre' => 'Universidad Nuestra Señora de La Paz (UNSLP)'],
            ['nombre' => 'Universidad Simón I. Patiño'],
            ['nombre' => 'Universidad Pedagógica'],
            ['nombre' => 'Universidad Privada Boliviana (UPB)'],
            ['nombre' => 'Universidad Privada del Valle (UNIVALLE)'],
            ['nombre' => 'Universidad Privada De Oruro (UNIOR)'],
            ['nombre' => 'Universidad Privada Domingo Savio'],
            ['nombre' => 'Universidad Privada Franz Tamayo (UNIFRANZ)'],
            ['nombre' => 'Univ. Priv. de Sta. Cruz de la Sierra (UPSA)'],
            ['nombre' => 'Universidad Privada San Franci_personasco de Asis (USFA)'],
            ['nombre' => 'Universidad Pública de El Alto (UPEA)'],
            ['nombre' => 'Univ. Salesiana de Bolivia (USALESIANA) '],
            ['nombre' => 'Universidad Técnica de Oruro (UTO)'],
            ['nombre' => 'Universidad Tecnológica Boliviana (UTB)'],
        ]);*/
    }

    public function down(): void
    {
        Schema::dropIfExists('dde_dde_instituci_personaones');
    }
};
