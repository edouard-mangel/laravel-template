<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes');
            $table->string('genre');
            $table->string('owner_id');
            $table->timestamps();

            $table->index('owner_id');
            $table->index('genre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
