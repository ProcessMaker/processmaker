<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Model\InputDocument;
use ProcessMaker\Model\Process;
use Ramsey\Uuid\Uuid;

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
        return InputDocument::where('PRO_UID', $process->PRO_UID)->simplePaginate(20);
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

        $data['INP_DOC_UID'] = str_replace('-', '', Uuid::uuid4());
        $data['PRO_UID'] = $process->PRO_UID;
        $data['PRO_ID'] = $process->PRO_ID;

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
        $data['PRO_UID'] = $process->PRO_UID;
        $data['PRO_ID'] = $process->PRO_ID;
        $inputDocument->fill($data);
        $this->validate($inputDocument->toArray());
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
     *
     * @throws ValidationException
     */
    private function validate($data): void
    {
        /* @var $validator \Illuminate\Validation\Validator */
        $validator = Validator::make(
            $data,
            [
                'INP_DOC_FORM_NEEDED' => 'required|in:' . implode(',', array_keys(InputDocument::FORM_NEEDED_TYPE)),
                'INP_DOC_ORIGINAL' => 'required|in:' . implode(',', InputDocument::DOC_ORIGINAL_TYPE),
                'INP_DOC_PUBLISHED' => 'required|in:' . implode(',', InputDocument::DOC_PUBLISHED_TYPE),
                'INP_DOC_TAGS' => 'required|in:' . implode(',', InputDocument::DOC_TAGS_TYPE)
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

}
