<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcessTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('process_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable();
            $table->string('name');
            $table->text('description');
            $table->unsignedInteger('process_id')->unsigned()->nullable();
            $table->unsignedInteger('user_id')->unsigned()->nullable();
            $table->unsignedInteger('process_category_id');
            $table->json('manifest');
            $table->longText('svg')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('process_category_id');

            // Foreign keys
            $table->foreign('process_id')->references('id')->on('processes')->onDelete('set null');
            $table->foreign('process_category_id')->references('id')->on('process_categories');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_templates');
    }
}
