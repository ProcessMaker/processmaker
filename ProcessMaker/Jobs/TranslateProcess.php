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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($process, $screens, $targetLanguage, $code, $user)
    {
        $this->process = $process;
        $this->screens = $screens;
        $this->targetLanguage = $targetLanguage;
        $this->code = $code;
        $this->user = $user;
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
        [$notHtmlChunks, $htmlChunks] = $this->prepareData($this->screens, $languageTranslationHandler);
 
        // Execute requests for each regular chunk
        foreach ($notHtmlChunks as $chunk) {
           $responses[] = $this->executeRequest($languageTranslationHandler, 'regular', $chunk);
        }

        // Execute requests for each HTML chunk
        foreach ($htmlChunks as $chunk) {
            $responses[] = $this->executeRequest($languageTranslationHandler, 'html', $chunk);
         }
     
        // Save translations in the database
        foreach ($responses as $response) {
            $saved = $this->saveTranslationsInScreen(json_decode($response[0], true));
        }

        // Remove job token from database
        $delete = ProcessTranslationToken::where('token', $this->code)->delete();

        // Broadcast response
        $this->broadcastResponse();
    }

    public function prepareData($screens, $handler)
    {
        // !IMPORTANT:
        // Chunk max size should be near 1600. 
        // In handler max_token for response is 2400. 
        // Total sum is 4000. Just under 4096 allowed for text-davinci-003
        $maxChunkSize = 1600;
        $handler->generatePrompt('html', '');
        $config = $handler->getConfig();

        // Get empty prompt tokens usage
        $emptyPromptTokens = $this->calcTokens($config['model'], $config['prompt']);

        // For each screen calculate the tokens and the make a sum with the empty prompt tokens usage
        // If total tokens is less than 1500, continue adding screens to the array
        $notHtmlChunks = [];
        $htmlChunks = [];
        $notHtmlStrings = [];
        $htmlStrings = [];

        foreach ($screens as $screen) {
            $notHtmlStrings[$screen['id']] = [];
            $htmlStrings[$screen['id']] = [];
            foreach ($screen['availableStrings'] as $string) {
                if ($this->isHTML($string)) {
                    $htmlStrings[$screen['id']][] = $string;
                } else {
                    $notHtmlStrings[$screen['id']][] = $string;
                }
            }
            $chunkTokens = $this->calcTokens($config['model'], json_encode($notHtmlStrings));

            if (intval($chunkTokens) + intval($emptyPromptTokens) >= $maxChunkSize) {
                $notHtmlChunks[] = $notHtmlStrings;
                $notHtmlStrings = [];

                $htmlChunks[] = $htmlStrings;
                $htmlStrings = [];
            }
        }

        $notHtmlChunks[] = $notHtmlStrings;
        $notHtmlStrings = [];

        $htmlChunks[] = $htmlStrings;
        $htmlStrings = [];

        return [$notHtmlChunks, $htmlChunks];
    }

    private function executeRequest($languageTranslationHandler, $type, $chunk)
    {
        \Log::info('$chunk in executeRequest');
        \Log::info($chunk);
        [$response, $usage, $targetLanguage] = $languageTranslationHandler->generatePrompt(
            $type,
            json_encode($chunk)
        )->execute();

        \Log::info('response');
        \Log::info($response);
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

    public function calcTokens($model = 'text-davinci-003', $string)
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

    private function saveTranslationsInScreen($allTranslations)
    {
        // For each the screens
        foreach ($allTranslations as $screenId => $translations) {
            $screen = Screen::findOrFail($screenId);
            $screenTranslations = $screen->translations;

            if (!$screenTranslations || !array_key_exists($this->targetLanguage['language'], $screenTranslations)) {
                $screenTranslations[$this->targetLanguage['language']]['strings'] = [];
            }

            // For each of the result translations of the screen
            $strings = $screenTranslations[$this->targetLanguage['language']]['strings'];
            foreach ($translations as $item) {
                $stringFound = false;

                foreach ($screenTranslations[$this->targetLanguage['language']]['strings'] as $stringItem) {
                    if ($stringItem['key'] === $item['key']) {
                        $stringItem['string'] = $item['value'];
                        $strings[] = $stringItem;
                        $stringFound = true;
                    }
                }

                if (!$stringFound) {
                    $stringItem = ['key' => $item['key'], 'string' => $item['value']];
                    $strings[] = $stringItem;
                }
            }

            $screenTranslations[$this->targetLanguage['language']]['strings'] = $strings;
            $screenTranslations[$this->targetLanguage['language']]['created_at'] = Carbon::now();
            $screenTranslations[$this->targetLanguage['language']]['updated_at'] = Carbon::now();

            $screen->translations = $screenTranslations;
            $screen->save();
        }
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
