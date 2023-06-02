<?php

namespace ProcessMaker\ProcessTranslations;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use ProcessMaker\Ai\Handlers\LanguageTranslationHandler;
use ProcessMaker\Ai\Handlers\ScreenTitleLanguageTranslationHandler;
use ProcessMaker\Jobs\ExecuteScreenTitleTranslationRequest;
use ProcessMaker\Jobs\ExecuteTranslationRequest;
use ProcessMaker\Models\ProcessTranslationToken;
use ProcessMaker\Models\User;
use ProcessMaker\Notifications\ProcessTranslationReady;
use Throwable;

class BatchesJobHandler
{
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
        $screenTitleLanguageTranslationHandler = new ScreenTitleLanguageTranslationHandler();
        $screenTitleLanguageTranslationHandler->setTargetLanguage($this->targetLanguage['humanLanguage']);

        $languageTranslationHandler = new LanguageTranslationHandler();
        $languageTranslationHandler->setTargetLanguage($this->targetLanguage['humanLanguage']);
        [$screensWithChunks, $chunksCount] = $this->prepareData($this->screens, $languageTranslationHandler);

        // Execute requests for each regular chunk
        $batch = Bus::batch([])
            ->then(function (Batch $batch) {
                \Log::info('All jobs in batch completed');
                $delete = ProcessTranslationToken::where('token', $batch->id)->delete();

                // Broadcast response
                $this->broadcastResponse();
            })->catch(function (Batch $batch, Throwable $e) {
                // First batch job failure detected...
                \Log::info('Batch error');
                \Log::error($e->getMessage());
                $delete = ProcessTranslationToken::where('token', $batch->id)->delete();
                $this->broadcastResponse();
            })->finally(function (Batch $batch) {
                // The batch has finished executing...
                \Log::info('Batch finally');
                // Remove job token from database
                $delete = ProcessTranslationToken::where('token', $batch->id)->delete();
                $this->broadcastResponse();
            })
            ->allowFailures()
            ->dispatch();

        // Update with real batch token ...
        ProcessTranslationToken::where('token', $this->code)->update(['token' => $batch->id]);

        // Translate screen titles
        foreach ($this->screens as $screen) {
            $batch->add(
                new ExecuteScreenTitleTranslationRequest(
                    $screen,
                    $screenTitleLanguageTranslationHandler,
                    'screen_title',
                    $this->targetLanguage
                )
            );
        }

        // Translate screen strings chunks
        foreach ($screensWithChunks as $screenId => $screenWithChunks) {
            foreach ($screenWithChunks as $chunk) {
                $batch->add(
                    new ExecuteTranslationRequest(
                        $screenId,
                        $languageTranslationHandler,
                        'html',
                        $chunk,
                        $this->targetLanguage
                    )
                );
            }
        }
    }

    public function prepareData($screens, $handler)
    {
        // Chunk max size should be near 1800.
        // In handler max_token for response is 1200.
        // Total sum is 3000. Under 4096 allowed for text-davinci-003
        $maxChunkSize = 1500;
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

                $shouldTranslate = true;

                // If option selected is 'empty', then should retranslate only empties strings
                if ($this->option === 'empty') {
                    if (!$this->isEmpty($string, $screen)) {
                        $shouldTranslate = false;
                    }
                }

                if ($shouldTranslate) {
                    $chunk[] = $string;
                }
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
