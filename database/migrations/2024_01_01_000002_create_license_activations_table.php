<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_activations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('license_id')
                  ->constrained('licenses')
                  ->cascadeOnDelete();

            // The binding identifier: domain, IP, machine ID, or custom string.
            $table->string('binding', 255);

            // Explicit activation timestamp, distinct from created_at, for business logic.
            $table->timestamp('activated_at')->useCurrent();

            $table->timestamps();

            // Prevent the same binding from being activated twice on the same license.
            $table->unique(['license_id', 'binding']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_activations');
    }
};
