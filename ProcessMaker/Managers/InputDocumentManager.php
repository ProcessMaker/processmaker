<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Model\InputDocument;
use ProcessMaker\Model\Process;

class InputDocumentManager
{


    /**
     * Get a list of All Input Documents in a project.
     *
     * @param Process $process
     *
     * @return Paginator
     */
    public function index(Process $process): Paginator
    {
        return InputDocument::where('process_id', $process->id)->simplePaginate(20);
    }

    /**
     * Create a new Input Document in a project.
     *
     * @param Process $process
     * @param array $data
     *
     * @return InputDocument
     * @throws \Throwable
     */
    public function save(Process $process, $data): InputDocument
    {
        $this->validate($data);

        $data['process_id'] = $process->id;

        $inputDocument = new InputDocument();
        $inputDocument->fill($data);
        $inputDocument->saveOrFail();

        return $inputDocument;
    }

    /**
     * Update Input Document in a project.
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
        $inputDocument->fill($data);
        $this->validate($inputDocument->toArray(), true);
        $inputDocument->saveOrFail();
        return $inputDocument;
    }


    /**
     * Remove Input Document in a project.
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
    private function validate($data, $update=false): void
    {
        $type = $update ? InputDocument::FORM_NEEDED_TYPE : array_keys(InputDocument::FORM_NEEDED_TYPE);
        /* @var $validator \Illuminate\Validation\Validator */
        $validator = Validator::make(
            $data,
            [
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
