<?php

namespace ProcessMaker\Managers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Model\Dynaform;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

class DynaformManager
{

    /**
     * Get a list of All Dynaforms in a project.
     *
     * @param Process $process
     *
     * @return Paginator
     */
    public function index(Process $process): Paginator
    {
        return Dynaform::where('PRO_UID', $process->PRO_UID)->simplePaginate(20);
    }

    /**
     * Create a new Dynaform in a project.
     *
     * @param Process $process
     * @param array $data
     *
     * @return Dynaform
     * @throws \Throwable
     */
    public function save(Process $process, $data): Dynaform
    {
        $this->validate($data);

        $data['DYN_UID'] = str_replace('-', '', Uuid::uuid4());
        $data['PRO_UID'] = $process->PRO_UID;
        $data['PRO_ID'] = $process->PRO_ID;

        if (!isset($data['DYN_CONTENT']) || empty($data['DYN_CONTENT'])) {
            $data['DYN_CONTENT'] = $this->generateContent($data['DYN_UID'], $data['DYN_TITLE'], $data['DYN_DESCRIPTION']);
        }

        $dynaform = new Dynaform();
        $dynaform->fill($data);
        $dynaform->saveOrFail();

        return $dynaform;
    }

    /**
     * Create a Dynaform using Copy/Import
     *
     * @param Process $process
     * @param array $data
     *
     * @return Dynaform
     * @throws \Throwable
     */
    public function copyImport(Process $process, $data): Dynaform
    {
        $this->validate($data);
        $oldProcess = Process::where('PRO_UID', $data['COPY_IMPORT']['pro_uid'])->get();
        if ($oldProcess->isEmpty()) {
            throw new ModelNotFoundException(__('The process not exists.'));
        }

        $copyDynaform = Dynaform::where('DYN_UID', $data['COPY_IMPORT']['dyn_uid'])->get();

        if ($copyDynaform->isEmpty()) {
            throw new ModelNotFoundException(__('The Dynaform not exists'));
        }

        if ($oldProcess->first()->PRO_ID !== $copyDynaform->first()->PRO_ID) {
            Throw new DoesNotBelongToProcessException(__('The Dynaform does not belong to this process.'));
        }

        if (!isset($data['DYN_CONTENT'])) {
            $data['DYN_CONTENT'] = $copyDynaform->first()->DYN_CONTENT;
        }

        unset($data['COPY_IMPORT']);

        return $this->save($process, $data);
    }

    /**
     * Update Dynaform in a project.
     *
     * @param Process $process
     * @param Dynaform $dynaform
     * @param array $data
     *
     * @return Dynaform
     * @throws \Throwable
     */
    public function update(Process $process, Dynaform $dynaform, $data): Dynaform
    {
        $data['PRO_UID'] = $process->PRO_UID;
        $data['PRO_ID'] = $process->PRO_ID;
        $dynaform->fill($data);
        $this->validate($dynaform->toArray());
        $dynaform->saveOrFail();
        return $dynaform;
    }

    /**
     * Remove Dynaform in a project.
     *
     * @param Dynaform $dynaform
     *
     * @return bool|null
     * @throws \Exception
     */
    public function remove(Dynaform $dynaform): ?bool
    {
        return $dynaform->delete();
    }

    /**
     * Validate extra rules
     *
     * @param array $data
     *
     * @throws ValidationException
     */
    private function validate($data): void
    {
        /**
         * @var $validator \Illuminate\Validation\Validator
         */
        $validator = Validator::make(
            $data,
            [
                'DYN_TYPE' => 'required|in:' . implode(',', Dynaform::TYPE)
            ]
        );
        if (isset($data['COPY_IMPORT'])) {
            $validator = Validator::make(
                $data,
                [
                    'COPY_IMPORT' => 'required|array',
                    'COPY_IMPORT.pro_uid' => 'required|string|max:32',
                    'COPY_IMPORT.dyn_uid' => 'required|string|max:32'
                ]
            );

        }

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Generate structure content dynaform
     *
     * @param string $uid
     * @param string $title
     * @param string $description
     *
     * @return array
     */
    private function generateContent(string $uid, string $title, string $description): array
    {
        return [
            'name' => $title,
            'description' => $description,
            'items' => [
                [
                    'type' => 'form',
                    'variable' => '',
                    'var_uid' => '',
                    'dataType' => '',
                    'id' => $uid,
                    'name' => $title,
                    'description' => $description,
                    'mode' => 'edit',
                    'script' => '',
                    'language' => 'en',
                    'externalLibs' => '',
                    'printable' => false,
                    'items' => [],
                    'variables' => []
                ]
            ]
        ];
    }

}
