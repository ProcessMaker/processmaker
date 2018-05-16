<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\OutputDocument;

/**
 * OutputDocument transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class OutputDocumentTransformer extends TransformerAbstract
{

    /**
     * Transform the activity.
     *
     * @param OutputDocument $item
     *
     * @return array
     */
    public function transform(OutputDocument $item)
    {
        return [
            'uid' => $item->uid,
            'title' => $item->title,
            'description' => $item->description,
            'filename' => $item->filename,
            'template' => $item->template,
            'report_generator' => $item->report_generator,
            'landscape' => $item->landscape,
            'media' => $item->media,
            'left_margin' => $item->left_margin,
            'right_margin' => $item->right_margin,
            'top_margin' => $item->top_margin,
            'bottom_margin' => $item->bottom_margin,
            'generate' => $item->generate,
            'type' => $item->type,
            'current_revision' => $item->current_revision,
            'field_mapping' => $item->field_mapping,
            'versioning' => $item->versioning,
            'destination_path' => $item->destination_path,
            'tags' => $item->tags,
            'pdf_security_enabled' => $item->pdf_security_enabled,
            'pdf_security_open_password' => $item->pdf_security_open_password,
            'pdf_security_owner_password' => $item->pdf_security_owner_password,
            'pdf_security_permissions' => $item->pdf_security_permissions,
        ];
    }
}
