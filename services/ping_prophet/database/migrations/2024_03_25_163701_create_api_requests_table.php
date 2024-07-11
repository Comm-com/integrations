<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('team_id');
            $table->string('request_type');
            $table->string('status')->nullable();
            $table->jsonb('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_requests');
    }
};
