<?php

namespace ProcessMaker\Transformers;

use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use ProcessMaker\Model\PmTable;

/**
 * PM Table transformer, used to prepare the JSON response returned in the
 * designer's endpoints.
 *
 * @package ProcessMaker\Transformer
 */
class PmTableTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'fields',
    ];

    /**
     * Transform the PM Table.
     *
     * @param PmTable $table
     *
     * @return array
     */
    public function transform(PmTable $table): array
    {
        return [
            'uid' => $table->uid,
            'name' => $table->name,
            'description' => $table->description,
            'type' => $table->type,
            'grid' => $table->grid,
            'tags' => $table->tags
        ];
    }

    /**
     * Fractal Include to add the fields of the users table in the transformation
     *
     * @param PmTable $item
     *
     * @return Item
     */
    public function includeFields(PmTable $item): Item
    {
        return $this->item($item->fields, new PmTableColumnTransformer());
    }
}
