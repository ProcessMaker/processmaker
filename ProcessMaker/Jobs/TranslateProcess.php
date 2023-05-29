<?php

namespace ProcessMaker\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Ai\Handlers\LanguageTranslationHandler;
use ProcessMaker\Models\ProcessTranslationToken;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\ProcessTranslationReady;

class TranslateProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $code;

    private $process;

    private $screens;

    private $targetLanguage;

    private $user;

    private $option;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($process, $screens, $targetLanguage, $code, $user, $option)
    {
        $this->process = $process;
        $this->screens = $screens;
        $this->targetLanguage = $targetLanguage;
        $this->code = $code;
        $this->user = $user;
        $this->option = $option;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $languageTranslationHandler = new LanguageTranslationHandler();
        $languageTranslationHandler->setTargetLanguage($this->targetLanguage['humanLanguage']);
        [$screensWithChunks, $chunksCount] = $this->prepareData($this->screens, $languageTranslationHandler);

        // Execute requests for each regular chunk
        $executingChunk = 0;
        foreach ($screensWithChunks as $screenId => $screenWithChunks) {
            foreach ($screenWithChunks as $chunk) {
                $executingChunk++;
                \Log::info('Translating to ' . $this->targetLanguage['humanLanguage'] . '. Executing chunk ' . $executingChunk . ' of ' . $chunksCount);
                $responses[$screenId][] = $this->executeRequest($languageTranslationHandler, 'html', $chunk);
            }
        }

        // Save translations in the database
        foreach ($responses as $screenId => $response) {
            foreach ($response as $responseChunk) {
                $saved = $this->saveTranslationsInScreen($screenId, json_decode($responseChunk[0]));
            }
        }

        // Remove job token from database
        $delete = ProcessTranslationToken::where('token', $this->code)->delete();

        // Broadcast response
        $this->broadcastResponse();
    }

    public function prepareData($screens, $handler)
    {
        // Chunk max size should be near 1600.
        // In handler max_token for response is 2400.
        // Total sum is 4000. Just under 4096 allowed for text-davinci-003
        $maxChunkSize = 1600;
        $handler->generatePrompt('html', '');
        $config = $handler->getConfig();

        // Get empty prompt tokens usage
        $emptyPromptTokens = intval($this->calcTokens($config['model'], $config['prompt']));

        $chunksCount = 0;
        $chunks = [];

        // foreach screen iterate over each available string
        foreach ($screens as $screen) {
            // create a chunk empty
            $chunk = [];
            $chunkTokens = 0;
            foreach ($screen['availableStrings'] as $string) {
                // calculate tokens with that string
                $stringTokens = intval($this->calcTokens($config['model'], '{"' . $string . '"},'));
                $chunkTokens = intval($this->calcTokens($config['model'], json_encode($chunk)));
                if ($emptyPromptTokens + $chunkTokens + $stringTokens <= $maxChunkSize) {
                    // if tokens are less than the allowed tokens add to current chunk
                } else {
                    $chunksCount++;
                    // if tokens are greater than the allowed add previous chunk to chunks and empty chunk and add the current string
                    $chunks[$screen['id']][] = $chunk;
                    $chunk = [];
                    $chunkTokens = 0;
                }
                $chunk[] = $string;
            }

            // Add the last chunk to chunks array
            $chunks[$screen['id']][] = $chunk;
            $chunksCount++;
        }

        return [$chunks, $chunksCount];
    }

    private function isEmpty($string, $screen)
    {
        $isEmpty = true;

        if (!$screen['translations'] || !array_key_exists($this->targetLanguage['language'], $screen['translations'])) {
            $screen['translations'][$this->targetLanguage['language']] = ['strings' => []];
        }

        $strings = $screen['translations'][$this->targetLanguage['language']]['strings'];

        foreach ($strings as $stringItem) {
            if ($stringItem['key'] === $string && ($stringItem['string'] !== '' && $stringItem['string'] !== null)) {
                $isEmpty = false;
            }
        }

        return $isEmpty;
    }

    private function executeRequest($languageTranslationHandler, $type, $chunk)
    {
        $languageTranslationHandler->generatePrompt($type, json_encode($chunk));

        try {
            [$response, $usage, $targetLanguage] = $languageTranslationHandler->generatePrompt(
                $type,
                json_encode($chunk)
            )->execute();
        } catch (\Throwable $e) {
            \Log::error('An error occurred while executing the request. Trying again');
            \Log::error($e->getMessage());
            $this->executeRequest($languageTranslationHandler, $type, $chunk);
        }

        return [$response, $usage, $targetLanguage];
    }

    public function isHTML($string) : bool
    {
        if ($string != strip_tags($string)) {
            // is HTML
            return true;
        } else {
            // not HTML
            return false;
        }
    }

    public function calcTokens($model, $string)
    {
        /**
         *    # The following code is in case we need in the future to use gpt-4 or gpt-3.5.-turbo
         *    # The script to calculate for those models it's available for python: ProcessMaker/Ai/Scripts/token_counter.py
         *
         *    $cmd = "python3";
         *    $args = [
         *        "ProcessMaker/Ai/Scripts/token_counter.py",
         *        $model,
         *        $string
         *    ];
         *
         *    $escaped_args = implode(" ", array_map("escapeshellarg", $args));
         *    $command = "$cmd $escaped_args 2>&1";
         *    $tokensUsage = trim(shell_exec($command));
         *
         *    return $tokensUsage;
         */

        return $tokensUsage = count(gpt_encode($string));
    }

    private function saveTranslationsInScreen($screenId, $chunkResponse)
    {
        // For each the screens
        $screen = Screen::findOrFail($screenId);
        $screenTranslations = $screen->translations;

        if (!$screenTranslations || !array_key_exists($this->targetLanguage['language'], $screenTranslations)) {
            $screenTranslations[$this->targetLanguage['language']]['strings'] = [];
        }

        $strings = $screenTranslations[$this->targetLanguage['language']]['strings'];
        // For each of the result chunkResponse translations of the screen
        foreach ($chunkResponse as $item) {
            $stringFound = false;

            foreach ($strings as &$stringItem) {
                if ($stringItem['key'] === $item->key) {
                    $stringItem['string'] = $item->value;
                    $stringFound = true;
                }
            }

            if (!$stringFound) {
                $newItem = ['key' => $item->key, 'string' => $item->value];
                $strings[] = $newItem;
            }
        }

        $screenTranslations[$this->targetLanguage['language']]['strings'] = $strings;
        $screenTranslations[$this->targetLanguage['language']]['created_at'] = Carbon::now();
        $screenTranslations[$this->targetLanguage['language']]['updated_at'] = Carbon::now();

        $screen->translations = $screenTranslations;
        $screen->save();
    }

    private function broadcastResponse()
    {
        \Log::info('Notify process translation to ' . $this->targetLanguage['humanLanguage'] . ' completed for process: ' . $this->process->name);
        if ($this->user) {
            User::find($this->user)->notify(new ProcessTranslationReady(
                $this->code,
                $this->process,
                $this->targetLanguage,
            ));
        }
    }
}
