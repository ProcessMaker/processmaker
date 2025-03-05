<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Models\ScriptExecutor;

class InitializeScriptMicroservice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:initialize-script-microservice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run after enabling script microservices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('script-runner-microservice.enabled')) {
            // Start with an array of all microservice languages
            $requiredLanguages = array_flip(array_keys(ScriptExecutor::MICROSERVICE_LANGUAGES));

            foreach (ScriptExecutor::orderBy('id', 'asc')->get() as $executor) {
                // Keep only the first occurrence of each language
                if (isset($requiredLanguages[$executor->language])) {
                    $this->info('Keeping ' . $executor->language);
                } else {
                    // Hide non-microservice languages as well as any duplicate languages
                    $this->info('Hiding ' . $executor->language);
                    $executor->is_system = true;
                    $executor->type = ScriptExecutorType::Duplicate;
                    $executor->save();
                }
                // Remove it from the required languages since we checked it
                unset($requiredLanguages[$executor->language]);
            }

            // Create any that are missing
            foreach ($requiredLanguages as $language => $_) {
                $this->info('Creating ' . $language);
                ScriptExecutor::create([
                    'language' => $language,
                    'title' => ScriptExecutor::MICROSERVICE_LANGUAGES[$language],
                    'description' => 'Script Executor For ' . $language,
                    'config' => null,
                    'is_system' => false,
                    'type' => null,
                ]);
            }
        } else {
            // Unhide any we hid above
            $this->info('Unhiding all script executors');
            ScriptExecutor::where('type', ScriptExecutorType::Duplicate)->update([
                'type' => null,
                'is_system' => false,
            ]);
        }
    }
}
