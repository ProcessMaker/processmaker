<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\InputDocument;

/**
 * Input Document transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class InputDocumentTransformer extends TransformerAbstract
{

    /**
     * Transform the activity.
     *
     * @param InputDocument $inputDocument
     *
     * @return array
     */
    public function transform(InputDocument $inputDocument): array
    {
        return [
            'uid' => $inputDocument->uid,
            'title' => $inputDocument->title,
            'description' => $inputDocument->description,
            'form_needed' => $inputDocument->form_needed,
            'original' => $inputDocument->original,
            'published' => $inputDocument->published,
            'versioning' => $inputDocument->versioning,
            'destination_path' => $inputDocument->destination_path,
            'tags' => $inputDocument->tags,
        ];
    }
}
