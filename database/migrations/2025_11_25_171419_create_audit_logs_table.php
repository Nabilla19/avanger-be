<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();   // siapa yang melakukan
            $table->string('action');                            // nama aksi, misal: loan_created
            $table->string('entity_type')->nullable();           // model: App\Models\Peminjaman
            $table->unsignedBigInteger('entity_id')->nullable(); // id dari entity
            $table->json('old_values')->nullable();              // nilai sebelum (optional)
            $table->json('new_values')->nullable();              // nilai sesudah (optional)
            $table->string('ip_address', 45)->nullable();        // IPv4/IPv6
            $table->text('user_agent')->nullable();              // browser / device
            $table->timestamps();

            $table->index('user_id');
            $table->index('action');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
