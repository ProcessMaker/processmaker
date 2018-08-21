<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Model\Form;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

class FormsManager
{

    /**
     * Get a list of All Forms in a process.
     *
     * @param Process $process
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function index(Process $process, array $options): LengthAwarePaginator
    {
        $query = Form::where('process_id', $process->id);
        $filter = $options['filter'];
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('title', 'like', $filter)
                    ->orWhere('description', 'like', $filter)
                    ->orWhere('label', 'like', $filter)
                    ->orWhere('type', 'like', $filter)
                    ->orWhere('content', 'like', $filter);
            });
        }
        return $query->orderBy($options['sort_by'], $options['sort_order'])
            ->paginate($options['per_page'])
            ->appends($options);
    }

    /**
     * Create a new Form in a process.
     *
     * @param Process $process
     * @param array $data
     *
     * @return Form
     * @throws \Throwable
     */
    public function save(Process $process, $data): Form
    {
        $data['process_id'] = $process->id;
        $this->validate($data);

        $data['uid'] = Uuid::uuid4();

        if (!isset($data['content']) || empty($data['content'])) {
            $data['content'] = null;
        }

        $form = new Form();
        $form->fill($data);
        $form->saveOrFail();

        return $form->refresh();
    }

    /**
     * Create a Form using Copy/Import
     *
     * @param Process $process
     * @param array $data
     *
     * @return Form
     * @throws \Throwable
     */
    public function copyImport(Process $process, $data): Form
    {
        $this->validate($data);
        $oldProcess = Process::where('uid', $data['copy_import']['process_uid'])->first();

        $copyDynaform = Form::where('uid', $data['copy_import']['form_uid'])->first();

        if ($oldProcess->id !== $copyDynaform->process_id) {
            Throw new DoesNotBelongToProcessException(__('The Form does not belong to this process.'));
        }

        if (!isset($data['content'])) {
            $data['content'] = $copyDynaform->content;
        }

        unset($data['copy_import']);

        return $this->save($process, $data);
    }

    /**
     * Update Form in a process.
     *
     * @param Process $process
     * @param Form $form
     * @param array $data
     *
     * @return Form
     * @throws \Throwable
     */
    public function update(Process $process, Form $form, $data): Form
    {
        $data['process_id'] = $process->id;
        $this->validate($data);
        $form->fill($data);
        if (empty($form->content)) {
            $form->content = null;
        }
        $form->saveOrFail();
        return $form->refresh();
    }

    /**
     * Remove Form in a process.
     *
     * @param Form $form
     *
     * @return bool|null
     * @throws \Exception
     */
    public function remove(Form $form): ?bool
    {
        return $form->delete();
    }

    /**
     * Validate extra rules
     *
     * @param array $data
     *
     * @throws ValidationException
     */
    private function validate($data)
    {
        /**
         * @var $validator \Illuminate\Validation\Validator
         */

        if (!isset($data['copy_import'])) {
            return;
        }


        $validator = Validator::make(
            $data,
            [
                'copy_import' => 'required|array',
                'copy_import.process_uid' => 'required|string|max:36|exists:processes,uid',
                'copy_import.form_uid' => 'required|string|max:36|exists:forms,uid'
            ]
        );
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
