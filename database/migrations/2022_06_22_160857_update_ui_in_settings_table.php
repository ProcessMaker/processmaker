<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ProcessMaker\Models\Setting;

class UpdateUiInSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $this->updateEmailServerUi();
        $this->updateAbeUi();
        $this->updateDocusignUi();
        $this->updateLdapUi();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        $this->revertEmailServerUi();
        $this->revertAbeUi();
        $this->revertDocusignUi();
        $this->revertLdapUi();
    }

    private function updateEmailServerUi() {
        // Update Email Server buttons UI
        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_ADD_MAIL_SERVER%')->update([
            'name' => 'Mail Server', 
            'ui' => '{"props":{"variant":"primary", "position": "top", "order": "100", "icon": "fas fa-plus"},"handler":"addMailServer"}',
        ]);

        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_REMOVE_MAIL_SERVER%')->update([
            'name' => 'Remove Server', 
            'ui' => '{"props":{"variant":"outline-danger", "position": "bottom", "order":"100", "icon":"fas fa-trash-alt"},"handler":"removeMailServer"}',
        ]);

        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_SEND_TEST_EMAIL%')->update([
            'ui' => '{"props":{"variant":"outline-secondary", "position": "top", "order":"300"},"handler":"mailTest"}',
        ]);

        // Update order of fields
        Setting::where('key', 'LIKE', 'EMAIL_CONNECTOR_MAIL_USERNAME%')->update([
            'ui' => [
                'order' => 1000,
            ],
        ]);

        Setting::where('key', 'LIKE', 'EMAIL_CONNECTOR_MAIL_PASSWORD%')->update([
            'ui' => [
                'sensitive' => true,
                'order' => 1000,
            ],
        ]);
    }

    protected function revertEmailServerUi() {
        // Revert buttons UI
         Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_ADD_MAIL_SERVER%')->update([
            'name' => '+ Mail Server',
        ]);

        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_REMOVE_MAIL_SERVER%')->update([
            'name' => '- Remove Server', 
            'ui' => '{"props":{"variant":"outline-secondary"},"handler":"removeMailServer"}'
        ]);

        Setting::where('key', 'LIKE', '%EMAIL_CONNECTOR_SEND_TEST_EMAIL%')->update([
            'ui' => '{"props":{"variant":"primary"},"handler":"mailTest"}'
        ]);
        
        // Revert order of fields
        Setting::where('key', 'LIKE', 'EMAIL_CONNECTOR_MAIL_USERNAME%')->update([
            'ui' => [],
        ]);

        Setting::where('key', 'LIKE', 'EMAIL_CONNECTOR_MAIL_PASSWORD%')->update([
            'ui' => [
                'sensitive' => true,
            ],
        ]);

    }

    protected function updateAbeUi() {
        // Update ABE Buttons
        Setting::where('key', 'abe_imap_test_connection')->update([
            'ui' => '{"props":{"variant":"primary", "position": "top", "order":"100"},"handler":"imapTest"}',
        ]);
    }

    protected function revertAbeUi() {
         // Revert ABE Buttons
         Setting::where('key', 'abe_imap_test_connection')->update([
            'ui' => '{"props":{"variant":"primary"},"handler":"imapTest"}'
        ]);
    }

    protected function updateDocusignUi() {
        // Update Docusign Buttons
        Setting::where('key', 'docusign_grant_access')->update([
            'ui' => '{"props":{"variant":"primary","href":"/docusign/grant", "position": "top", "order":"100"}}',
        ]);
    }

    protected function revertDocusignUi() {
        // Revert Docusign Buttons
        Setting::where('key', 'docusign_grant_access')->update([
            'ui' => '{"props":{"variant":"primary","href":"/docusign/grant"}}',
        ]);
    }

    protected function updateLdapUi() {
        // Update LDAP Buttons
        Setting::where('key', 'services.ldap.log')->update([
            'ui' => '{"props":{"variant":"primary","target":"_blank","href":"/admin/ldap-logs", "position": "top", "order":"100"}}',
        ]);
    }

    protected function revertLdapUi() {
        // Revert LDAP Buttons
        Setting::where('key', 'services.ldap.log')->update([
            'ui' => '{"props":{"variant":"primary","target":"_blank","href":"/admin/ldap-logs"}}',
        ]);
    }
}
