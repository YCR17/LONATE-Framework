<?php

use Lonate\Core\Database\Schema\Schema;
use Lonate\Core\Database\Schema\Blueprint;

class CreateLegitimaciesTable
{
    public function up(): void
    {
        Schema::create('legitimacies', function (Blueprint $table) {
            $table->id();
            $table->string('resolution_number');
            $table->integer('resolution_year');
            $table->string('recipient');
            $table->text('asset_details'); // JSON
            $table->string('proof_path');  // Screenshot
            $table->boolean('is_quorum')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legitimacies');
    }
}
