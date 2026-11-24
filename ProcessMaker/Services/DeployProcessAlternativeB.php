<?php

declare(strict_types=1);

namespace ProcessMaker\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Events\ProcessPublished;
use ProcessMaker\Exception\TaskDoesNotHaveUsersException;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessVersion;

/**
 * Writes the BPMN of a deploy request to Alternative B of an existing process.
 *
 * The live `processes` row (Alternative A) is never saved: only B draft or
 * published versions are written.
 */
class DeployProcessAlternativeB
{
    private const ALTERNATIVE_MODEL = 'ProcessMaker\Package\PackageABTesting\Models\Alternative';

    /**
     * @return array{version_id: int, published: bool}
     *
     * @throws ValidationException
     */
    public function deploy(?Process $process, string $bpmn, bool $publish): array
    {
        $process = $this->assertDeployable($process);
        $this->ensureAlternativeBEnabled($process);

        return DB::transaction(function () use ($process, $bpmn, $publish): array {
            $this->hydrateFromLatestB($process);
            $process->bpmn = $bpmn;
            $process->alternative = 'B';

            try {
                $version = $publish ? $process->saveVersion() : $process->saveDraft('B');
            } catch (TaskDoesNotHaveUsersException $exception) {
                throw ValidationException::withMessages(['bpmn' => $exception->getMessage()]);
            }

            if ($publish) {
                // The A/B package listens to this event to set process_version_b_id.
                ProcessPublished::dispatch($process->refresh(), [], []);
            }

            return ['version_id' => (int) $version->getKey(), 'published' => $publish];
        });
    }

    private function assertDeployable(?Process $process): Process
    {
        if (!class_exists(self::ALTERNATIVE_MODEL)) {
            throw ValidationException::withMessages([
                'alternative' => 'Alternative B requires package-ab-testing.',
            ]);
        }
        if ($process === null) {
            throw ValidationException::withMessages([
                'alternative' => 'Alternative B needs an existing process. Deploy it as Alternative A first.',
            ]);
        }

        return $process;
    }

    private function ensureAlternativeBEnabled(Process $process): void
    {
        $model = self::ALTERNATIVE_MODEL;
        $alternative = $model::findOrCreateForProcess($process);
        if (!$alternative->version_b_enabled) {
            $alternative->enableAlternativeB();
            $alternative->save();
        }
    }

    private function hydrateFromLatestB(Process $process): void
    {
        $version = $process->getDraftVersion('B') ?: $process->getDraftOrPublishedLatestVersion('B');
        if ($version instanceof ProcessVersion) {
            $process->stages = $version->stages;
        }
    }
}
