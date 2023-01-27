<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;

class OpenAiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:openai {code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(\OpenAI\Client $client)
    {
        $code = $this->argument('code');
        $model = 'code-davinci-002';

        $result = $client->completions()->create([
            'prompt' => $code,
            'model' => $model,
            'max_tokens' => 250,
        ]);

        $this->line(ltrim($result->choices[0]->text));

        return Command::SUCCESS;
    }
}
