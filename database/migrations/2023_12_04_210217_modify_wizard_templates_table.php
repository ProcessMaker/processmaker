<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyWizardTemplatesTable extends Migration
{
    public function up()
    {
        // Change the process_id column to helper_process_id
        Schema::table('wizard_templates', function (Blueprint $table) {
            $table->renameColumn('process_id', 'helper_process_id');
            $table->string('name')->after('uuid');
            $table->string('description')->after('name');
            $table->json('template_details')->after('description');
            $table->unsignedInteger('config_collection_id')->nullable()->after('template_details');
        });

        // Change the foreign key reference to helper_process_id
        Schema::table('wizard_templates', function (Blueprint $table) {
            $table->foreign('helper_process_id')
                ->references('id')
                ->on('processes')
                ->onDelete('cascade')
                ->constrained('processes');
        });

        // Add the foreign key reference to process_template_id
        Schema::table('wizard_templates', function (Blueprint $table) {
            $table->foreign('process_template_id')
                ->references('id')
                ->on('process_templates')
                ->onDelete('cascade')
                ->constrained('process_templates');
        });
    }

    public function down()
    {
        // Reverse the changes in the down method if needed
        Schema::table('wizard_templates', function (Blueprint $table) {
            $table->renameColumn('helper_process_id', 'process_id');

            // Reverse the foreign key changes
            $table->dropForeign(['helper_process_id']);
            $table->dropForeign(['process_template_id']);

            $table->dropColumn('name');
            $table->dropColumn('description');
            $table->dropColumn('template_details');
            $table->dropColumn('config_collection_id');
        });
    }
}
