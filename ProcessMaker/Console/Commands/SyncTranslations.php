<?php

namespace ProcessMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use ProcessMaker\Helpers\SyncJsonTranslations;
use ProcessMaker\Helpers\SyncPhpTranslations;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

class SyncTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processmaker:sync-translations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize translations when processmaker is updated.';

    private $files = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting translation synchronization...');
        $this->newLine();

        $jsonResults = (new SyncJsonTranslations())->sync();
        // dump("JSON Results: ", $jsonResults);
        $phpResults = (new SyncPhpTranslations())->sync();

        $this->displayJsonResults($jsonResults);
        $this->newLine();
        $this->displayPhpResults($phpResults);

        $this->newLine();
        $this->info('Translation synchronization completed!');

        return 0;
    }

    /**
     * Display JSON translation results in a table format
     *
     * @param array $results
     * @return void
     */
    private function displayJsonResults(array $results): void
    {
        $this->info('JSON Translation Files:');

        $table = new Table($this->output);
        $table->setHeaders(['Language', 'File', 'Action', 'New Keys', 'Total Keys', 'Backup', 'Status']);
        $additionalChanges = Arr::get($results, 'en.otherLanguageResults');

        foreach ($results as $language => $result) {
            if (isset($additionalChanges[$language])) {
                if ($result['action'] !== 'no_changes') {
                    $this->addRowToTable($table, $result, $language);
                }
                $this->addRowToTable($table, $additionalChanges[$language], $language);
            } else {
                $this->addRowToTable($table, $result, $language);
            }
        }

        $table->render();
    }

    private function addRowToTable(Table $table, array $result, string $language)
    {
        $status = $result['error'] ? '<error>Error</error>' : '<info>Success</info>';
        $action = $this->formatAction($result['action']);
        $backup = $result['backup_created'] ? '<comment>Yes</comment>' : 'No';

        $table->addRow([
            $language,
            $result['filename'],
            $action,
            $result['new_keys'],
            $result['total_keys'],
            $backup,
            $status,
        ]);

        if ($result['error']) {
            $table->addRow(new TableSeparator());
            $table->addRow(['', '', '', '', '', '', '<error>' . $result['error'] . '</error>']);
        }
    }

    /**
     * Display PHP translation results in a table format
     *
     * @param array $results
     * @return void
     */
    private function displayPhpResults(array $results): void
    {
        $this->info('PHP Translation Files:');

        $table = new Table($this->output);
        $table->setHeaders(['Language', 'Files Processed', 'Copied', 'Merged', 'No Changes', 'Errors', 'Status']);

        foreach ($results as $language => $result) {
            $errorCount = count($result['errors']);
            $status = $errorCount > 0 ? '<error>Errors</error>' : '<info>Success</info>';

            $table->addRow([
                $language,
                $result['files_processed'],
                $result['files_copied'],
                $result['files_merged'],
                $result['files_no_changes'],
                $errorCount,
                $status,
            ]);

            // Add error details if any
            if ($errorCount > 0) {
                $table->addRow(new TableSeparator());
                foreach ($result['errors'] as $error) {
                    $table->addRow(['', '', '', '', '', '', '<error>' . $error . '</error>']);
                }
            }
        }

        $table->render();
    }

    /**
     * Format action for display
     *
     * @param string $action
     * @return string
     */
    private function formatAction(string $action): string
    {
        switch ($action) {
            case 'copied':
                return '<comment>Copied</comment>';
            case 'merged':
                return '<info>Merged</info>';
            case 'updated':
                return '<fg=cyan>Updated</fg=cyan>';
            case 'no_changes':
                return '<fg=gray>No Changes</fg=gray>';
            case 'error':
                return '<error>Error</error>';
            default:
                return $action;
        }
    }
}
