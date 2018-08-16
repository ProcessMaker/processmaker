<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Model\InputDocument;
use ProcessMaker\Model\Process;

class InputDocumentManager
{


    /**
     * Get a list of All Input Documents in a process.
     *
     * @param Process $process
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function index(Process $process, array $options): LengthAwarePaginator
    {
        $query = InputDocument::where('process_id', $process->id);
        $filter = $options['filter'];
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('title', 'like', $filter)
                    ->orWhere('description', 'like', $filter)
                    ->orWhere('versioning', 'like', $filter);
            });
        }
        return $query->orderBy($options['sort_by'], $options['sort_order'])
            ->paginate($options['per_page'])
            ->appends($options);
    }

    /**
     * Create a new Input Document in a process.
     *
     * @param Process $process
     * @param array $data
     *
     * @return InputDocument
     * @throws \Throwable
     */
    public function save(Process $process, $data): InputDocument
    {
        $data['process_id'] = $process->id;
        $this->validate($data);

        $inputDocument = new InputDocument();
        $inputDocument->fill($data);
        $inputDocument->saveOrFail();

        return $inputDocument;
    }

    /**
     * Update Input Document in a process.
     *
     * @param Process $process
     * @param InputDocument $inputDocument
     * @param array $data
     *
     * @return InputDocument
     * @throws \Throwable
     */
    public function update(Process $process, InputDocument $inputDocument, $data): InputDocument
    {
        $data['process_id'] = $process->id;
        $this->validate($data);
        $inputDocument->fill($data);
        $this->validate($inputDocument->toArray(), true);
        $inputDocument->saveOrFail();
        return $inputDocument;
    }


    /**
     * Remove Input Document in a process.
     *
     * @param InputDocument $inputDocument
     *
     * @return bool|null
     * @throws \Exception
     */
    public function remove(InputDocument $inputDocument): ?bool
    {
        return $inputDocument->delete();
    }

    /**
     * Validate extra rules
     *
     * @param array $data
     * @param boolean $update
     *
     * @throws ValidationException
     */
    private function validate($data, $update=false)
    {
        $type = $update ? InputDocument::FORM_NEEDED_TYPE : array_keys(InputDocument::FORM_NEEDED_TYPE);
        /* @var $validator \Illuminate\Validation\Validator */
        $validator = Validator::make(
            $data,
            [
                'title' => ['required', Rule::unique('input_documents')->where(function ($query) use ($data){
                    $query->where('process_id', $data['process_id']);
                })],
                'form_needed' => 'required|in:' . implode(',', $type),
                'original' => 'required|in:' . implode(',', InputDocument::DOC_ORIGINAL_TYPE),
                'published' => 'required|in:' . implode(',', InputDocument::DOC_PUBLISHED_TYPE),
                'tags' => 'required|in:' . implode(',', InputDocument::DOC_TAGS_TYPE)
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

}
