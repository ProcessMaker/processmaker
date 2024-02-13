<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('screen_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->string('name');
            $table->text('description');
            $table->text('version')->nullable();
            $table->unsignedInteger('user_id')->unsigned()->nullable();
            $table->unsignedInteger('editing_screen_uuid')->nullable()->unique();
            $table->unsignedInteger('screen_category_id');
            $table->string('screen_type');
            $table->json('manifest');
            $table->boolean('is_system')->default(false);
            $table->string('asset_type')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('screen_category_id');

            // Foreign keys
            $table->foreign('screen_category_id')->references('id')->on('screen_categories');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screen_templates');
    }
};
