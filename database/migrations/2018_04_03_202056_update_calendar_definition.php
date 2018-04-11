<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateCalendarDefinition extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('CALENDAR_DEFINITION', function (Blueprint $table) {
            $table->dropPrimary('CALENDAR_UID');
            $table->integer('CALENDAR_ID')->first();
            $table->primary('CALENDAR_ID');
        });
        DB::statement('ALTER TABLE CALENDAR_DEFINITION MODIFY CALENDAR_ID INTEGER NOT NULL AUTO_INCREMENT');

        Schema::table('CALENDAR_HOLIDAYS', function (Blueprint $table) {
            $table->dropPrimary('CALENDAR_UID', 'CALENDAR_HOLIDAY_NAME');
            $table->integer('CALENDAR_HOLIDAYS_ID')->first();
            $table->primary('CALENDAR_HOLIDAYS_ID');
            $table->integer('CALENDAR_ID')->after('CALENDAR_HOLIDAYS_ID');

            $table->foreign('CALENDAR_ID')
                ->references('CALENDAR_ID')
                ->on('CALENDAR_DEFINITION')
                ->onDelete('cascade');
        });
        DB::statement('ALTER TABLE CALENDAR_HOLIDAYS MODIFY CALENDAR_HOLIDAYS_ID INTEGER NOT NULL AUTO_INCREMENT');

        Schema::table('CALENDAR_BUSINESS_HOURS', function (Blueprint $table) {
            $table->dropPrimary('CALENDAR_UID', 'CALENDAR_BUSINESS_DAY', 'CALENDAR_BUSINESS_START', 'CALENDAR_BUSINESS_END');
            $table->integer('CALENDAR_BUSINESS_HOURS_ID')->first();
            $table->primary('CALENDAR_BUSINESS_HOURS_ID')->autoIncrement();
            $table->integer('CALENDAR_ID')->after('CALENDAR_BUSINESS_HOURS_ID');

            $table->foreign('CALENDAR_ID')
                ->references('CALENDAR_ID')
                ->on('CALENDAR_DEFINITION')
                ->onDelete('cascade');
        });
        DB::statement('ALTER TABLE CALENDAR_BUSINESS_HOURS MODIFY CALENDAR_BUSINESS_HOURS_ID INTEGER NOT NULL AUTO_INCREMENT');

        Schema::table('CALENDAR_ASSIGNMENTS', function (Blueprint $table) {
            $table->dropPrimary('OBJECT_UID');
            $table->integer('CALENDAR_ASSIGNMENTS_ID')->first();
            $table->primary('CALENDAR_ASSIGNMENTS_ID')->autoIncrement();
            $table->integer('CALENDAR_ID')->after('CALENDAR_ASSIGNMENTS_ID');

            $table->foreign('CALENDAR_ID')
                ->references('CALENDAR_ID')
                ->on('CALENDAR_DEFINITION')
                ->onDelete('cascade');
        });
        DB::statement('ALTER TABLE CALENDAR_ASSIGNMENTS MODIFY CALENDAR_ASSIGNMENTS_ID INTEGER NOT NULL AUTO_INCREMENT');

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('CALENDAR_ASSIGNMENTS', function (Blueprint $table) {
            $table->dropForeign('calendar_assignments_calendar_id_foreign');
            $table->dropColumn('CALENDAR_ASSIGNMENTS_ID');
            $table->dropColumn('CALENDAR_ID');
            $table->primary('OBJECT_UID');
        });

        Schema::table('CALENDAR_BUSINESS_HOURS', function (Blueprint $table) {
            $table->dropForeign('calendar_business_hours_calendar_id_foreign');
            $table->dropColumn('CALENDAR_BUSINESS_HOURS_ID');
            $table->dropColumn('CALENDAR_ID');
            $table->primary(['CALENDAR_UID', 'CALENDAR_BUSINESS_DAY', 'CALENDAR_BUSINESS_START', 'CALENDAR_BUSINESS_END'], 'calendar_business_hours_calendar_id_foreign');
            
        });

        Schema::table('CALENDAR_HOLIDAYS', function (Blueprint $table) {
            $table->dropForeign('calendar_holidays_calendar_id_foreign');
            $table->dropColumn('CALENDAR_HOLIDAYS_ID');
            $table->dropColumn('CALENDAR_ID');
            $table->primary(['CALENDAR_UID', 'CALENDAR_HOLIDAY_NAME']);
        });

        Schema::table('CALENDAR_DEFINITION', function (Blueprint $table) {
            $table->dropColumn('CALENDAR_ID');
            $table->primary('CALENDAR_UID');
        });

        Schema::enableForeignKeyConstraints();
    }
}
