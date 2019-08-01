<?php

namespace Tests\Feature\Api;

use ProcessMaker\Models\User;
use Tests\TestCase;
use Faker\Factory as Faker;
use ProcessMaker\Models\Script;
use Tests\Feature\Shared\RequestHelper;

class SanitizationTest extends TestCase
{
    use RequestHelper;

    /**
     * Test our sanitization middleware using a script as our guinea pig.
     * This allows us to test fields that should be sanitized and
     * those that should not, all within one model.
     */
    public function testSanitizationMiddleware()
    {
        // Create our fake data
        $title = "Best Script Ever";
        $description = "This is the <b>best</b> script ever!";
        $code = "<?php echo 'Hello world.';";
        
        // Create the process
        $faker = Faker::create();

        $user = factory(User::class)->create(['is_administrator' => true]);
        $script = factory(Script::class)->make([
            'title' => $title,
            'description' => $description,
            'code' => $code,
            'run_as_user_id' => $user->id,
        ]);

        // Post the process to the API
        $response = $this->apiCall('POST', '/scripts', $script->toArray());
        
        // Get the new process ID
        $scriptId = $response->getData()->id;
        
        // Reload the script
        $get = $this->apiCall('GET', "/scripts/{$scriptId}");
        $data = $get->getData();
        
        // Title should match since we did not use any restricted characters
        $this->assertEquals($title, $data->title);

        // Description should not match since we used restricted characters
        $this->assertEquals("This is the best script ever!", $data->description);
        
        // Code should match despite using restricted characters since it is
        // on the sanitization blacklist within the Script API controller
        $this->assertEquals($code, $data->code);
    }
}