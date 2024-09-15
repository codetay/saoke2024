<?php

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
        Schema::create('transactions', static function (Blueprint $table) {
            $table->id();
            $table->date('donated_at');
            $table->string('code');
            $table->bigInteger('amount');
            $table->string('description');
            $table->timestamps();

            $table->index('donated_at');
            $table->index('code');
            $table->index('amount');
            $table->index('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
