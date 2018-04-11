<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePLUGINSREGISTRYTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('PLUGINS_REGISTRY', function(Blueprint $table)
		{
			$table->string('PR_UID', 32)->default('')->primary();
			$table->string('PLUGIN_NAMESPACE', 100);
			$table->text('PLUGIN_DESCRIPTION', 16777215)->nullable();
			$table->string('PLUGIN_CLASS_NAME', 100);
			$table->string('PLUGIN_FRIENDLY_NAME', 150)->nullable()->default('');
			$table->string('PLUGIN_FILE', 250);
			$table->string('PLUGIN_FOLDER', 100);
			$table->string('PLUGIN_SETUP_PAGE', 100)->nullable()->default('');
			$table->string('PLUGIN_COMPANY_LOGO', 100)->nullable()->default('');
			$table->string('PLUGIN_WORKSPACES', 100)->nullable()->default('');
			$table->string('PLUGIN_VERSION', 50)->nullable()->default('');
			$table->boolean('PLUGIN_ENABLE')->nullable()->default(0);
			$table->boolean('PLUGIN_PRIVATE')->nullable()->default(0);
			$table->text('PLUGIN_MENUS', 16777215)->nullable();
			$table->text('PLUGIN_FOLDERS', 16777215)->nullable();
			$table->text('PLUGIN_TRIGGERS', 16777215)->nullable();
			$table->text('PLUGIN_PM_FUNCTIONS', 16777215)->nullable();
			$table->text('PLUGIN_REDIRECT_LOGIN', 16777215)->nullable();
			$table->text('PLUGIN_STEPS', 16777215)->nullable();
			$table->text('PLUGIN_CSS', 16777215)->nullable();
			$table->text('PLUGIN_JS', 16777215)->nullable();
			$table->text('PLUGIN_REST_SERVICE', 16777215)->nullable();
			$table->text('PLUGIN_TASK_EXTENDED_PROPERTIES', 16777215)->nullable();
			$table->text('PLUGIN_ATTRIBUTES', 16777215)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('PLUGINS_REGISTRY');
	}

}
