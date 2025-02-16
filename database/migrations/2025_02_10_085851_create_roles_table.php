<?php

// 1. Role Migration (roles tablosu)
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 'roles' tablosunu oluşturuyoruz
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();  // Rol ismi, örneğin: admin, user
            $table->timestamps();
        });

        // Varsayılan rolleri ekliyoruz
        DB::table('roles')->insert([
            ['name' => 'admin'],  // Admin rolü
            ['name' => 'user'],   // User rolü
        ]);
    }

    public function down(): void
    {
        // Tabloyu geri alıyoruz
        Schema::dropIfExists('roles');
    }
};