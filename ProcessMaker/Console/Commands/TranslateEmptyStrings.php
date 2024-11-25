<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use OpenAI\Laravel\Facades\OpenAI;
use ProcessMaker\Managers\PackageManager;

class TranslateEmptyStrings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:translate-empty-strings 
                            {--lang= : Optional language code to translate only a specific language}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translate empty strings in core and packages for all languages except en.json';

    private $files = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (env('OPENAI_API_KEY') === null) {
            $this->error('OPENAI_API_KEY is not set');

            return;
        }

        $langCode = $this->option('lang');

        $this->info('-----------------------------------');
        $this->info('Translating core empty strings');
        $this->info('-----------------------------------');
        $this->translateCoreEmptyStrings($langCode);

        $this->info('-----------------------------------');
        $this->info('Translating packages empty strings');
        $this->info('-----------------------------------');
        $this->translatePackagesEmptyStrings($langCode);
    }

    private function translateCoreEmptyStrings($langCode)
    {
        $this->files = [];
        $translationsCore = app()->basePath() . '/resources/lang';
        $this->listFiles($translationsCore);
        $filesIgnore = ['/en/', '.gitignore', '/en.json', '.php', '.DS_Store'];

        foreach ($this->files as $pathFile) {
            // Ignore en language
            foreach ($filesIgnore as $value) {
                if (str_contains($pathFile, $value)) {
                    continue 2;
                }
            }

            // Skip if language code is specified and doesn't match
            if ($langCode && !str_contains($pathFile, "/{$langCode}.json") && !str_contains($pathFile, "/{$langCode}/")) {
                continue;
            }

            $this->translateEmptyStringsInFile($pathFile);
        }
    }

    private function translatePackagesEmptyStrings($langCode)
    {
        // Get current packages
        $packages = App::make(PackageManager::class)->getJsonTranslationsRegistered();

        foreach ($packages as $packagePath) {
            $this->getPackageLanguageFiles($packagePath);

            // Filter files by language code if specified
            if ($langCode) {
                $this->files = array_filter($this->files, function ($file) use ($langCode) {
                    return str_contains($file, "/{$langCode}.json") || str_contains($file, "/{$langCode}/");
                });
            }

            foreach ($this->files as $file) {
                $this->translateEmptyStringsInFile($file);
            }
        }
    }

    private function getPackageLanguageFiles($packagePath)
    {
        // Get languages files ignoring en files for a given package path
        $filesIgnore = ['/en/', '.gitignore', '/en.json', '.php'];

        $this->files = [];
        $this->listFiles($packagePath);

        // Ignore filesIgnore from files
        $this->files = array_filter($this->files, function ($file) use ($filesIgnore) {
            foreach ($filesIgnore as $ignore) {
                if (str_contains($file, $ignore)) {
                    return false;
                }
            }

            return true;
        });
    }

    private function translateEmptyStringsInFile($file)
    {
        // Check if file exists
        if (!file_exists($file)) {
            throw new \Exception("Language file not found at path: $file");
        }

        $jsonContent = file_get_contents($file);
        $langArray = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo $langArray;
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }

        // Filter for empty values
        $emptyValues = array_filter($langArray, function ($value) {
            return $value === '';
        });

        // Translate keys with empty values
        if (!empty($emptyValues)) {
            // Split empty values into chunks of 100
            $chunks = array_chunk($emptyValues, 100, true);
            $totalChunks = count($chunks);

            $this->info('Translating: ' . $file);
            foreach ($chunks as $index => $chunk) {
                $this->info(sprintf('Processing chunk %d/%d', $index + 1, $totalChunks));
                $translatedChunk = $this->callOpenAI($chunk, $file);

                // Update only existing keys in langArray
                foreach ($translatedChunk as $key => $value) {
                    if (array_key_exists($key, $langArray)) {
                        $langArray[$key] = $value;
                    } else {
                        $this->info('Key not found: ' . $key);
                    }
                }

                // Save after each chunk is translated
                file_put_contents($file, json_encode($langArray, JSON_PRETTY_PRINT));
            }
        } elseif (!$this->option('verbose')) {
            $this->line('No empty values found.');
        }
    }

    private function callOpenAI($emptyValues, $file)
    {
        $stream = OpenAI::chat()->createStreamed([
            'model' => 'gpt-4o',
            'max_tokens' => 16380,
            'temperature' => 0.0,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'Act as an expert i18n assistant. Identify the language based in this path' . $file . ' After that translate the values of the given array.'],
                ['role' => 'user', 'content' => '
After identifying the language, translate the following strings to the identified language.
Do not translate the variables.
Do not add any markdown like ```json just return the translated json.
Do not translate the keys.
Only translate the values.
Do not add additional keys or elements to the json.
Return a JSON and do not add any explanation.
Always return the original key with the translated value.
Never modify the keys.
Never scape the keys. For example if you have a key that contains backslashes like "This is my example <strong>text<\/strong>", do not add any additional backslashes. return the key as is.
Do not translate the tags.
Return the keys exactly as they are in the original json.
###
' . json_encode($emptyValues)],
            ],
        ]);

        $fullResponse = '';

        $totalKeys = count($emptyValues);

        if ($this->option('verbose')) {
            $this->info(json_encode($emptyValues));
        }

        $bar = $this->output->createProgressBar($totalKeys);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $bar->start();

        $jsonStarted = false;
        $openBraces = 0;
        $translatedPairs = 0;

        foreach ($stream as $response) {
            $content = $response->choices[0]->delta->content;
            // Remove extra backslashes before appending
            $content = str_replace('\\\\', '\\', $content);
            $fullResponse .= $content;

            // Track JSON structure
            for ($i = 0; $i < strlen($content); $i++) {
                $char = $content[$i];
                if ($char === '{') {
                    $jsonStarted = true;
                    $openBraces++;
                } elseif ($char === '}') {
                    $openBraces--;
                }

                // Count completed key-value pairs by detecting commas when we're inside valid JSON
                if ($jsonStarted && $openBraces > 0 && $char === ',') {
                    $translatedPairs++;
                    $bar->advance();
                }
            }

            // Handle the last pair which won't have a comma
            if ($jsonStarted && $openBraces === 0 && $translatedPairs < $totalKeys) {
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();

        if ($this->option('verbose')) {
            echo $fullResponse;
        }

        $decoded = json_decode($fullResponse, true);
        if ($decoded === null) {
            // Print JSON error if decoding failed
            $this->error('JSON decode error: ' . json_last_error_msg());
            // Try cleaning the response before decoding
            $cleanResponse = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $fullResponse);
            $decoded = json_decode($cleanResponse, true);
            if ($decoded === null) {
                throw new \RuntimeException('Failed to decode JSON response after cleaning');
            }
        }

        return $decoded;
    }

    private function listFiles($dir)
    {
        $files = scandir($dir);

        foreach ($files as $value) {
            $path = $dir . '/' . $value;
            if (!is_dir($path)) {
                $this->files[] = $path;
            } elseif ($value != '.' && $value != '..') {
                $this->listFiles($path);
            }
        }
    }
}
