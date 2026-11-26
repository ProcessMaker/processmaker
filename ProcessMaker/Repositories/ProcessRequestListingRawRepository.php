<?php

declare(strict_types=1);

namespace ProcessMaker\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequestToken;

class ProcessRequestListingRawRepository
{
    public function hydrate(LengthAwarePaginator|Collection $requests, string $includes): void
    {
        $includeList = array_filter(explode(',', $includes));
        $collection = $requests instanceof LengthAwarePaginator
            ? $requests->getCollection()
            : $requests;

        if ($collection->isEmpty()) {
            return;
        }

        $requestIds = $collection->pluck('id')->all();

        if (in_array('activeTasks', $includeList, true)) {
            $this->hydrateActiveTasks($collection, $requestIds);
        }

        if (in_array('process', $includeList, true)) {
            $processIds = $collection->pluck('process_id')->unique()->filter()->values()->all();
            $this->hydrateProcesses($collection, $processIds);
        }
    }

    private function hydrateActiveTasks(Collection $requests, array $requestIds): void
    {
        $placeholders = implode(',', array_fill(0, count($requestIds), '?'));
        $rows = DB::select(
            "SELECT id, process_request_id, element_name, status, user_id
             FROM process_request_tokens
             WHERE process_request_id IN ({$placeholders})
               AND status = 'ACTIVE'
               AND element_type = 'task'",
            $requestIds
        );

        $grouped = collect($rows)->groupBy('process_request_id');

        foreach ($requests as $request) {
            $tasks = $grouped->get($request->id, collect())->map(function ($row) {
                $token = new ProcessRequestToken();
                $token->setRawAttributes([
                    'id' => $row->id,
                    'element_name' => $row->element_name,
                    'status' => $row->status,
                    'user_id' => $row->user_id,
                ], true);
                $token->exists = true;

                return $token;
            });

            $request->setRelation('activeTasks', $tasks);
        }
    }

    private function hydrateProcesses(Collection $requests, array $processIds): void
    {
        if ($processIds === []) {
            return;
        }

        $projectsByProcessId = $this->loadProjectsJsonByProcessIds($processIds);

        $processesById = Process::query()
            ->whereIn('id', $processIds)
            ->with('categories')
            ->get()
            ->keyBy('id');

        foreach ($processesById as $process) {
            if (isset($projectsByProcessId[$process->id])) {
                $attributes = $process->getAttributes();
                $attributes['projects'] = $projectsByProcessId[$process->id];
                $process->setRawAttributes($attributes, true);
            }
        }

        foreach ($requests as $request) {
            if ($processesById->has($request->process_id)) {
                $request->setRelation('process', $processesById->get($request->process_id));
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function loadProjectsJsonByProcessIds(array $processIds): array
    {
        $projectAssetClass = 'ProcessMaker\Package\Projects\Models\ProjectAsset';

        if (!class_exists($projectAssetClass)) {
            return array_fill_keys($processIds, json_encode([]));
        }

        $projectAssets = $projectAssetClass::query()
            ->whereIn('asset_id', $processIds)
            ->where('asset_type', Process::class)
            ->with('project')
            ->get()
            ->groupBy('asset_id');

        $result = [];

        foreach ($processIds as $processId) {
            $result[$processId] = json_encode(
                ($projectAssets->get($processId) ?? collect())->map(function ($projectAsset) {
                    return $projectAsset->project;
                })
            );
        }

        return $result;
    }
}
