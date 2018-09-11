<?php
namespace ProcessMaker\Transformers;
use League\Fractal\TransformerAbstract;
use ProcessMaker\Models\Form;
/**
 * Form transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class FormTransformer extends TransformerAbstract
{
    /**
     * Transform the Form.
     *
     * @param Form $item
     *
     * @return array
     */
    public function transform(Form $item)
    {
        $data = $item->toArray();
        return $data;
    }
}