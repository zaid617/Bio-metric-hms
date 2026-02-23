<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('denied_permissions', function (Blueprint $table) {
            $table->id();

            // Foreign key to permissions table
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');

            // Polymorphic relation: works for any model (Doctor, User, etc.)
            $table->morphs('model'); // creates model_id + model_type

            $table->timestamps();

            // Prevent duplicate entries for same model + permission
            $table->unique(['permission_id', 'model_id', 'model_type'], 'denied_permissions_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('denied_permissions');
    }
};
