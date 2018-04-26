<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to update the process design tables to version 4.0.0.
 *
 */
class UpdateProcessTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('BPMN_DIAGRAM', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_diagram_project');
            $table->dropColumn('PRJ_UID');
        });
        Schema::table('BPMN_BOUND', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_bound_project');
            $table->dropColumn('PRJ_UID');
        });
        Schema::table('BPMN_ACTIVITY', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_activity_project');
            $table->dropColumn('PRJ_UID');
            $table->dropForeign('fk_bpmn_activity_process');
            $table->dropColumn('PRO_UID');
        });
        Schema::table('BPMN_EVENT', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_event_project');
            $table->dropColumn('PRJ_UID');
            $table->dropForeign('fk_bpmn_event_process');
            $table->dropColumn('PRO_UID');
        });
        Schema::table('BPMN_GATEWAY', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_gateway_project');
            $table->dropColumn('PRJ_UID');
            $table->dropForeign('fk_bpmn_gateway_process');
            $table->dropColumn('PRO_UID');
        });
        Schema::table('BPMN_FLOW', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_flow_project');
            $table->dropColumn('PRJ_UID');
            $table->dropForeign('fk_bpmn_flow_diagram');
        });
        Schema::table('BPMN_ARTIFACT', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_artifact_project');
            $table->dropColumn('PRJ_UID');
            $table->dropForeign('fk_bpmn_artifact_process');
            $table->dropColumn('PRO_UID');
        });
        Schema::table('EMAIL_EVENT', function(Blueprint $table) {
            $table->dropColumn('PRJ_UID');
        });
        Schema::table('PROCESS', function(Blueprint $table) {
            //From the merge of PROJECT and PROCESS, PRO_NAME was chosen
            $table->renameColumn('PRO_TITLE', 'PRO_NAME');
            //PRO_TYPE_PROCESS defines if the process is PRIVATE o PUBLIC
            //  then PRO_VISIBILITY describes better this behavior.
            $table->renameColumn('PRO_TYPE_PROCESS', 'PRO_VISIBILITY');
            //Not used, this property is defined at the TASK.
            $table->dropColumn('PRO_TYPE_DAY');
            //Industry and sub category is not being used.
            $table->dropColumn('PRO_INDUSTRY');
            $table->dropColumn('PRO_SUB_CATEGORY');
            //Not used, this property is defined at the TASK.
            $table->dropColumn('PRO_ASSIGNMENT');

            //Columns merged from BPMN_PROCESS
            $table->string('DIA_UID', 32)->nullable()->default(null);
            $table->tinyInteger('PRO_IS_EXECUTABLE')->default('0');
            $table->tinyInteger('PRO_IS_CLOSED')->default('0');
            $table->tinyInteger('PRO_IS_SUBPROCESS')->default('0');

            //Columns merged from PROJECT
            $table->mediumText('PRO_TARGET_NAMESPACE')->nullable()->default(null);
            $table->mediumText('PRO_EXPRESSION_LANGUAGE')->nullable()->default(null);
            $table->mediumText('PRO_TYPE_LANGUAGE')->nullable()->default(null);
            $table->mediumText('PRO_EXPORTER')->nullable()->default(null);
            $table->mediumText('PRO_EXPORTER_VERSION')->nullable()->default(null);
            $table->mediumText('PRO_AUTHOR')->nullable()->default(null);
            $table->mediumText('PRO_AUTHOR_VERSION')->nullable()->default(null);
            $table->mediumText('PRO_ORIGINAL_SOURCE')->nullable()->default(null);
        });

        //DELETE THE PROCESS DEFINITION WHEN THE PROCESS IS DELETED

        //BPMN ACTIVITIES
        Schema::table('BPMN_ACTIVITY', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //BPMN ARTIFACTS
        Schema::table('BPMN_ARTIFACT', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //BPMN BOUNDS
        Schema::table('BPMN_BOUND', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //BPMN DATA SHAPES
        Schema::table('BPMN_DATA', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //BPMN DIAGRAM(S)
        Schema::table('BPMN_DIAGRAM', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //BPMN DOCUMENTATIONS
        Schema::table('BPMN_DOCUMENTATION', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //BPMN EVENTS
        Schema::table('BPMN_EVENT', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //BPMN EXTENSIONS
        Schema::table('BPMN_EXTENSION', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //BPMN FLOWS
        Schema::table('BPMN_FLOW', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //BPMN GATEWAYS
        Schema::table('BPMN_GATEWAY', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //BPMN LANES
        Schema::table('BPMN_LANE', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_lane_project');
            $table->dropColumn('PRJ_UID');
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //BPMN LANESETS
        Schema::table('BPMN_LANESET', function(Blueprint $table) {
            $table->dropForeign('fk_bpmn_laneset_project');
            $table->dropColumn('PRJ_UID');
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
            $table->dropColumn('LNS_STATE');
        });

        //BPMN PARTICIPANTS
        Schema::table('BPMN_PARTICIPANT', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //PROCESS SCHEDULED START CASES (CLASSIC)
        Schema::table('CASE_SCHEDULER', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //LOG OF PROCESS CASES SCHEDULER
        Schema::table('LOG_CASES_SCHEDULER', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //PROCESS CASE TRACKER GENERAL CONFIGURATION
        Schema::table('CASE_TRACKER', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //PROCESS CASE TRACKER OBJECTS CONFIGURATION (CLASSIC)
        Schema::table('CASE_TRACKER_OBJECT', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //DB SOURCE CONNECTIONS
        Schema::table('DB_SOURCE', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //DYNAFORMS
        Schema::table('DYNAFORM', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //ELEMENT(GATEWAY, EVENT) TO DUMMY TASK RELATIONS
        Schema::table('ELEMENT_TASK_RELATION', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //EMAIL EVENTS (CLASSIC)
        Schema::table('EMAIL_EVENT', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //EVENTS
        Schema::table('EVENT', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //GATEWAYS
        Schema::table('GATEWAY', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //INPUT DOCUMENTS
        Schema::table('INPUT_DOCUMENT', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //MESSAGE EVENT DEFINITIONS
        Schema::table('MESSAGE_EVENT_DEFINITION', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //MESSAGE EVENT RELATIONS
        Schema::table('MESSAGE_EVENT_RELATION', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //MESSAGE TYPES
        Schema::table('MESSAGE_TYPE', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //OBJECT PERMISSIONS
        Schema::table('OBJECT_PERMISSION', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //OUTPUT DOCUMENTS
        Schema::table('OUTPUT_DOCUMENT', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //PROCESS FILES
        Schema::table('PROCESS_FILES', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //PROCESS USERS (like SUPERVISORS)
        Schema::table('PROCESS_USER', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //STATISTICAL INFORMATION AND KPIs OF THE PROCESS
        Schema::table('PRO_REPORTING', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //REPORT TABLES (TABLE DEFINITION MAINTAINED FROM v1.x)
        Schema::table('REPORT_TABLE', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //REPORT VARIABLE DEFINITION (TABLE DEFINITION MAINTAINED FROM v1.x)
        Schema::table('REPORT_VAR', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //ROUTES
        Schema::table('ROUTE', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //SCRIPT TASKS
        Schema::table('SCRIPT_TASK', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //STAGES (CLASSIC)
        Schema::table('STAGE', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //STEPS
        Schema::table('STEP', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //STEP SUPERVISORS
        Schema::table('STEP_SUPERVISOR', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //SUB PROCESSES
        Schema::table('SUB_PROCESS', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //SWIMLANES ELEMENTS
        Schema::table('SWIMLANES_ELEMENTS', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //TASKS
        Schema::table('TASK', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //TIMER EVENTS
        Schema::table('TIMER_EVENT', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //TRIGGERS
        Schema::table('TRIGGERS', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //USR REPORTING
        Schema::table('USR_REPORTING', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //WEB ENTRIES
        Schema::table('WEB_ENTRY', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //WEB ENTRY EVENTS
        Schema::table('WEB_ENTRY_EVENT', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('CASCADE');
        });

        //RESTRICT THE PROCESS DELETION IF IT IS USED IN CASES EXECUTION
        //CASES
        Schema::table('APPLICATION', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });

        //CASES ASSIGNMENT, SELF SERVICE VALUES
        Schema::table('APP_ASSIGN_SELF_SERVICE_VALUE', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });

        //CASES LIST CACHE
        Schema::table('APP_CACHE_VIEW', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });

        //CASES DELAY FROM PAUSE/UNPAUSE/CANCEL
        Schema::table('APP_DELAY', function(Blueprint $table) {
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });

        //CASES DELEGATIONS
        Schema::table('APP_DELEGATION', function(Blueprint $table) {
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });

        //CASES HISTORY
        Schema::table('APP_HISTORY', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });

        //LIST OF CANCELED CASES
        Schema::table('LIST_CANCELED', function(Blueprint $table) {
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });

        //LIST OF COMPLETED CASES
        Schema::table('LIST_COMPLETED', function(Blueprint $table) {
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });

        //LIST INBOX
        Schema::table('LIST_INBOX', function(Blueprint $table) {
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });

        //HISTORIC OF PARTICIPATION
        Schema::table('LIST_PARTICIPATED_HISTORY', function(Blueprint $table) {
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });

        //LIST OF THE LAST PARTICIPATED IN EACH CASE
        Schema::table('LIST_PARTICIPATED_LAST', function(Blueprint $table) {
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });

        //LIST OF PAUSED CASES
        Schema::table('LIST_PAUSED', function(Blueprint $table) {
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });

        //LIST OF UNASSIGNED CASES
        Schema::table('LIST_UNASSIGNED', function(Blueprint $table) {
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });

        //MESSAGE EVENTS THROUGH THE CASES
        Schema::table('MESSAGE_APPLICATION', function(Blueprint $table) {
            $table->integer('PRO_ID')->nullable();
            $table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });
    }
}
