<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePROCESSTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('processes', function(Blueprint $table)
		{
			$table->increments('id');
			$table->uuid('uid')->unique();
			$table->string('name');
			$table->text('description', 16777215)->nullable();
			// Null value means no parent
			$table->unsignedInteger('parent_process_id')->nullable();
			$table->float('time', 10, 0)->default(1);
			$table->enum('timeunits', ['DAYS', 'HOURS', 'MINUTES'])->default('DAYS');
			$table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
			$table->enum('type', ['NORMAL', 'SIMPLIFIED'])->default('NORMAL');
			$table->boolean('show_map')->default(true);
			$table->boolean('show_message')->default(true);
			$table->integer('create_trigger_id')->nullable();
			$table->integer('open_trigger_id')->nullable();
			$table->integer('deleted_trigger_id')->nullable();
			$table->integer('canceled_trigger_id')->nullable();
			$table->integer('paused_trigger_id')->nullable();
			$table->integer('reassigned_trigger_id')->nullable();
			$table->integer('unpaused_trigger_id')->nullable();
			$table->enum('visibility', ['PUBLIC', 'PRIVATE'])->default('PUBLIC');
			$table->boolean('show_delegate')->default(true);
			$table->boolean('show_dynaform')->default(0);
			$table->integer('category_id')->nullable();
			$table->timestamps();
			$table->unsignedInteger('creator_user_id');
			$table->integer('height')->default(5000);
			$table->integer('width')->default(10000);
			$table->integer('title_x')->default(0);
			$table->integer('title_y')->default(6);
			$table->boolean('debug')->default(false);
			$table->text('dynaforms')->nullable();
			$table->string('derivation_screen_template', 128)->nullable()->default('');
			$table->decimal('cost', 7)->nullable()->default(0.00);
			$table->string('unit_cost', 50)->nullable()->default('');
			$table->integer('itee')->default(0);
			$table->text('action_done')->nullable();

            //Columns merged from BPMN_PROCESS
            $table->boolean('executable')->default(false);
            $table->boolean('closed')->default(false);

            //Columns merged from PROJECT
            $table->mediumText('target_namespace')->nullable()->default(null);
            $table->mediumText('expression_language')->nullable()->default(null);
            $table->mediumText('type_language')->nullable()->default(null);
            $table->mediumText('exporter')->nullable()->default(null);
            $table->mediumText('exporter_version')->nullable()->default(null);
            $table->mediumText('author')->nullable()->default(null);
            $table->mediumText('author_version')->nullable()->default(null);
			$table->mediumText('original_source')->nullable()->default(null);
			
			// Represents the BPMN file that represents this process, if one is available
			$table->longText('bpmn')->nullable();
 
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('processes');
	}

}
