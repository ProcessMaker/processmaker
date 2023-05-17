<?php

namespace ProcessMaker\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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

    private $process;
    private $screens;
    private $targetLanguage;
    private $code;
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
        [$notHtmlStrings, $htmlStrings] = $languageTranslationHandler->prepareData($this->screens);
        
        // Translate regular strings
        [$resultNotHtmlStrings, $usage, $targetLanguage] = $languageTranslationHandler->generatePrompt(
            'regular',
            $notHtmlStrings
        )->execute();

        // Translate html
        // [$resultHtmlStrings, $usage, $targetLanguage] = $languageTranslationHandler->generatePrompt(
        //     'html',
        //     $htmlStrings
        // )->execute();
        
        \Log::info('$resultNotHtmlStrings');
        \Log::info($resultNotHtmlStrings);
        // \Log::info('$resultHtmlStrings');
        // \Log::info($resultHtmlStrings);
        \Log::info('================================================================');

        
        // Save translations in database
        $resultNotHtmlStringsDecoded = json_decode($resultNotHtmlStrings, true);
        $resultHtmlStringsDecoded = json_decode('[]', true);
        $allTranslations = $this->mergeResults($resultNotHtmlStringsDecoded, $resultHtmlStringsDecoded);
        $saved = $this->saveTranslationsInScreen($allTranslations);

        // Remove job token from database
        $delete = ProcessTranslationToken::where('token', $this->code)->delete();

        // Broadcast response
        $this->broadcastResponse();
    }

    function mergeResults($resultNotHtmlStrings = [], $resultHtmlStrings = []) 
    {
        $allTranslations = [];

        foreach($resultNotHtmlStrings as $key => $value) {
            if(array_key_exists($key, $resultHtmlStrings)) {
                $allTranslations[$key] = array_merge($resultNotHtmlStrings[$key], $resultHtmlStrings[$key]);
            } else {
                $allTranslations[$key] = $value;
            }
        }
        foreach($resultHtmlStrings as $key => $value) {
            if(!array_key_exists($key, $resultNotHtmlStrings)) {
                $allTranslations[$key] = $value;
            }
        }

        return $allTranslations;
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
            $strings = [];
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
