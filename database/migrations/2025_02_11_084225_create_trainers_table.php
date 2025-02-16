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
        Schema::create('trainers', function (Blueprint $table) {
            $table->id(); // Eğitmenin benzersiz ID'si.
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('email')->unique();

            // Sport ID'nin foreign key olarak tanımlanması
            $table->foreignId('sport_id')
                ->constrained('sports')  // 'sports' tablosuna referans veriyoruz.
                ->onDelete('cascade');  // Spor silindiğinde ona bağlı eğitmenler de silinsin.

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainers');
    }
};