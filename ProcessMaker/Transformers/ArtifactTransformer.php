<?php

namespace ProcessMaker\Transformers;

use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\Artifact;

/**
 * Artifact transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformers
 */
class ArtifactTransformer extends TransformerAbstract
{

    /**
     * Transform the artifact.
     *
     * @param Artifact $artifact
     *
     * @return array
     */
    public function transform(Artifact $artifact)
    {
        return [
            'art_uid'          => $artifact->ART_UID,
            'pro_id'           => $artifact->PRO_ID,
            'art_type'         => $artifact->ART_TYPE,
            'art_name'         => $artifact->ART_NAME,
            'art_category_ref' => $artifact->ART_CATEGORY_REF,
            "bou_x"            => $artifact->shape->BOU_X,
            "bou_y"            => $artifact->shape->BOU_Y,
            "bou_width"        => $artifact->shape->BOU_WIDTH,
            "bou_height"       => $artifact->shape->BOU_HEIGHT,
            "bou_container"    => $artifact->shape->BOU_CONTAINER,
            "bou_element"      => $artifact->shape->BOU_ELEMENT,
        ];
    }
}
