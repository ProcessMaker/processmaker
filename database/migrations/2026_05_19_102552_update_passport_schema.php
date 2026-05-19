<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Copied from https://github.com/jaapredeker/passport/blob/021ab9adafbbaed1d600869ca891586ca99b2f49/UPGRADE.md#oauth-client-table-changes

        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->nullableMorphs('owner', after: 'user_id');

            $table->after('provider', function (Blueprint $table) {
                $table->text('redirect_uris')->nullable();
                $table->text('grant_types')->nullable();
            });
        });

        foreach (Passport::client()->cursor() as $client) {
            if ($client->personal_access_client === true && $client->password_client === false) {
                $grantTypes = ['personal_access'];
            } else {
                $grantTypes = $client->grant_types;
            }
            Model::withoutTimestamps(fn () => $client->forceFill([
                'owner_id' => $client->user_id,
                'owner_type' => $client->user_id
                    ? config('auth.providers.' . ($client->provider ?: config('auth.guards.api.provider')) . '.model')
                    : null,
                'redirect_uris' => $client->redirect_uris,
                'grant_types' => $grantTypes,
            ])->save());
        }

        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->dropColumn(['user_id', 'redirect', 'personal_access_client', 'password_client']);

            $table->text('redirect_uris')->nullable(false)->change();
            $table->text('grant_types')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
