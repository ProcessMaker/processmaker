<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWizardTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('wizard_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('process_template_id')->nullable();
            $table->unsignedInteger('process_id');
            $table->string('media_collection')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('process_id')
                ->references('id')
                ->on('processes')
                ->onDelete('cascade')
                ->constrained('processes');
        });
    }

    public function down()
    {
        Schema::dropIfExists('wizard_templates');
    }
}
