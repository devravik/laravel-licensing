<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();

            // Hashed license key — bcrypt/argon2 hash of the raw key.
            // Length 255 accommodates bcrypt hashes (60 chars) and future algorithms.
            // Indexed to support fast equality lookups when hash_keys = false
            // (plaintext keys can be found via WHERE key = ?).
            $table->string('key', 255)->index();

            // SHA-256 hex digest of the raw key used as a fast lookup token.
            // This column is always indexed and allows O(log n) pre-filtering
            // before the expensive bcrypt comparison in findByKey().
            // Set to NULL when hash_keys = false (plaintext key stored directly).
            $table->string('lookup_token', 64)->nullable()->index();

            // The product tier or SKU this license is associated with.
            $table->string('product', 100)->index();

            // Polymorphic ownership — allows any Eloquent model to own a license.
            $table->morphs('owner'); // creates owner_id (bigint, index) + owner_type (string)

            // Maximum number of concurrent activation bindings.
            $table->unsignedInteger('seats')->default(1);

            // Nullable: when null, the license never expires.
            $table->timestamp('expires_at')->nullable()->index();

            // Nullable: when null, the license is active. Set on revocation.
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
