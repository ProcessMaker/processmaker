<?php

namespace ProcessMaker\ImportExport\Exporters;

use ProcessMaker\ImportExport\DependentType;
use ProcessMaker\Models\User;

class UserExporter extends ExporterBase
{
    public function export() : void
    {
        foreach ($this->model->groups as $group) {
            $this->addDependent(DependentType::GROUPS, $group, GroupExporter::class);
        }
    }

    public function import() : bool
    {
        $user = $this->model;
        $user->saveOrFail();

        foreach ($this->getDependents(DependentType::GROUPS) as $dependent) {
            $group = $dependent->model;
            $user->groups()->attach($group->id);
        }

        return true;
    }
}
