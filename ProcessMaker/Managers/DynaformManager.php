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
        return Dynaform::where('process_id', $process->id)->simplePaginate(20);
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

        $data['uid'] = Uuid::uuid4();
//        $data['PRO_UID'] = $process->uid;
        $data['process_id'] = $process->id;

        if (!isset($data['content']) || empty($data['content'])) {
            $data['content'] = $this->generateContent($data['uid'], $data['title'], $data['description']);
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
        $oldProcess = Process::where('uid', $data['COPY_IMPORT']['pro_uid'])->get();
        if ($oldProcess->isEmpty()) {
            throw new ModelNotFoundException(__('The process not exists.'));
        }

        $copyDynaform = Dynaform::where('uid', $data['COPY_IMPORT']['dyn_uid'])->get();

        if ($copyDynaform->isEmpty()) {
            throw new ModelNotFoundException(__('The Dynaform not exists'));
        }

        if ($oldProcess->first()->id !== $copyDynaform->first()->process_id) {
            Throw new DoesNotBelongToProcessException(__('The Dynaform does not belong to this process.'));
        }

        if (!isset($data['content'])) {
            $data['content'] = $copyDynaform->first()->content;
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
        $data['process_id'] = $process->id;
        $dynaform->fill($data);
        $this->validate($dynaform->toArray());
        if (empty($dynaform->content)) {
            $dynaform->content = $this->generateContent($dynaform->uid, $dynaform->title, $dynaform->description);
        }
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

        if (!isset($data['COPY_IMPORT'])) {
            return;
        }

        /**
         * @var $validator \Illuminate\Validation\Validator
         */
        $validator = Validator::make(
            $data,
            [
                'COPY_IMPORT' => 'required|array',
                'COPY_IMPORT.pro_uid' => 'required|string|max:36',
                'COPY_IMPORT.dyn_uid' => 'required|string|max:36'
            ]
        );
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
