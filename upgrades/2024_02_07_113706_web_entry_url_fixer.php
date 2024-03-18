<?php

use ProcessMaker\ImportExport\Utils;
use ProcessMaker\Models\Process;
use ProcessMaker\Upgrades\UpgradeMigration as Upgrade;

class WebEntryUrlFixer extends Upgrade
{
    /**
     * Run any validations/pre-run checks to ensure the environment, settings,
     * packages installed, etc. are right correct to run this upgrade.
     *
     * Throw a \RuntimeException if the conditions are *NOT* correct for this
     * upgrade migration to run. If this is not a required upgrade, then it
     * will be skipped. Otherwise the exception thrown will be caught, noted,
     * and will prevent the remaining migrations from continuing to run.
     *
     * Returning void or null denotes the checks were successful.
     *
     * @throws RuntimeException
     */
    public function preflightChecks(): void
    {
        //
    }

    /**
     * Run the upgrade migration.
     */
    public function up(): void
    {
        $appUrl = config('app.url');
        $chunkSize = 50;
        Process::chunk($chunkSize, function ($processes) use ($appUrl) {
            foreach ($processes as $process) {
                $this->updateWebEntryUrl($process, $appUrl);
            }
        });
    }

    /**
     * Reverse the upgrade migration.
     */
    public function down(): void
    {
        // No down migration needed.
    }

    /**
     * Updates the web entry URL for a given process.
     */
    private function updateWebEntryUrl(Process $process, string $appUrl): void
    {
        $definitions = $process->getDefinitions(true);
        $elements = Utils::getElementByMultipleTags($definitions, [
            'bpmn:task',
            'bpmn:startEvent',
        ]);
        foreach ($elements as $element) {
            $config = $element->getAttribute('pm:config') ?? '[]';
            $decodedConfig = json_decode($config, true);
            $entryUrl = Arr::get($decodedConfig, 'web_entry.webentryRouteConfig.entryUrl');

            // If the entry URL is not a valid URL, then update it.
            if ($entryUrl && !filter_var($entryUrl, FILTER_VALIDATE_URL)) {
                $newEntryUrl = rtrim($appUrl, '/') . '/' . ltrim($entryUrl, '/');
                Arr::set($decodedConfig, 'web_entry.webentryRouteConfig.entryUrl', $newEntryUrl);
                $element->setAttribute('pm:config', json_encode($decodedConfig));
            }
        }

        $process->bpmn = $definitions->saveXml();
        // Save process without firing events, as the webentryRouteConfig.entryUrl
        // does not needed any additional update.
        $process->saveQuietly();
    }
}
