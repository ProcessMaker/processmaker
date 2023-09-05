<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PhpParser\Node\Stmt\Foreach_;
use ProcessMaker\Models\Setting;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $elements = Setting::where('key', 'LIKE', 'services%')->where('group', 'LIKE', 'SSO')->get()->toArray();
        $options = [];

        foreach ($elements as $el) {
            $options[] = $el['name'];
        }

        Setting::factory()->create([
            'key' => 'sso.default.login',
            'name' => 'Default SSO Login',
            'helper' => 'Select the SSO Login you want as default for the users',
            'format' => 'choice',
            'config' => '',
            'group' => 'SSO',
            'ui' => [
                'order' => 251,
                'options' => $options,
                'elements' => $elements
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Setting::where('key', 'sso.default.login')->delete();
    }
};
