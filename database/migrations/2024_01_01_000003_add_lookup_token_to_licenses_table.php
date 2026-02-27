<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the lookup_token column to the licenses table.
 *
 * This migration is only needed for installations that created the licenses
 * table using a version of the package that did not include lookup_token.
 * If you are doing a fresh install, this column is already present in the
 * create_licenses_table migration and you do not need to run this one.
 *
 * lookup_token is a SHA-256 hex digest of the raw license key. It allows
 * LicenseManager::findByKey() to perform an O(log n) index lookup before
 * doing the expensive bcrypt comparison, replacing the previous O(n)
 * full-table cursor scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('licenses', 'lookup_token')) {
            return;
        }

        Schema::table('licenses', function (Blueprint $table) {
            $table->string('lookup_token', 64)->nullable()->index()->after('key');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn('lookup_token');
        });
    }
};
