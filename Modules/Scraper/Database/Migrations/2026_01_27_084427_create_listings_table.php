<?php

declare(strict_types=1);

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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained('monitors')->onDelete('cascade');
            $table->string('external_id')->index();
            $table->string('url', 2048);
            $table->string('title');
            $table->decimal('price', 12, 2)->nullable();
            $table->string('image_url')->nullable();

            $table->json('raw_data')->nullable(); // parser data
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['monitor_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
