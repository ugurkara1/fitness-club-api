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
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_uuid')->nullable(); // batch_uuid sütununu ekleyin
            $table->unsignedBigInteger('subject_id')->nullable(); // veya uygun tip
            $table->string('subject_type')->nullable(); // ya da ihtiyacınıza göre başka bir tip
            $table->morphs('causer'); //kullanıcı veya işlem yapan entity
            $table->string('description'); //işlem açıklaması
            $table->json('properties')->nullable();//ek özellikler
            $table->string('log_name')->nullable();//log adı
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};