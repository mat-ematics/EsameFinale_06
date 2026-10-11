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
        if (!Schema::hasTable('files')) {
            Schema::create('files', function (Blueprint $table) {
                $table->id();
                $table->string('filename');                         //Nome del File
                $table->string('label')->nullable();                //Label opzionale
                $table->string('visibility');                       //Enum Visibilita'
                $table->string('path', 255);                        //Percorso del file
                $table->unsignedBigInteger('size')->nullable();     //Dimensione (opzionale/se determinabile)
                $table->string('mime_type');                        //Mime Type
                $table->string('extension', 10);                    //Estensione File
                $table->string('hash')->nullable();                 //Hash per controllo duplicazione file
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
