<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcessTemplateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('process_template_categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->string('name')->unique();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_template_categories');
    }
}
