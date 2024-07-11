<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lookup_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('api_request_id')->nullable();
            $table->uuid('callback_id')->nullable();
            $table->string('callback_code')->nullable();
            $table->unsignedInteger('status')->nullable();
            $table->unsignedBigInteger('phone_normalized');
            $table->string('foreign_id')->nullable();
            $table->decimal('provider_price', 18, 15)->nullable();
            $table->decimal('admin_price', 18, 15)->nullable();
            $table->string('lookup_type')->nullable();
            $table->boolean('verified')->nullable();
            $table->unsignedInteger('network_id')->nullable();
            $table->jsonb('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookup_results');
    }
};
