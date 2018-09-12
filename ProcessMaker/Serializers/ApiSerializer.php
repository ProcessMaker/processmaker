<?php
namespace ProcessMaker\Serializers;

use League\Fractal\Serializer\JsonApiSerializer;

/**
 * Serializer for ProcessMaker API.
 *
 */
class ApiSerializer extends JsonApiSerializer
{

    /**
     * @param array $data
     *
     * @return integer
     */
    protected function getIdFromData(array $data)
    {
        if (!array_key_exists('uuid', $data)) {
            throw new InvalidArgumentException(
            'JSON API resource objects MUST have a valid id'
            );
        }
        return $data['uuid'];
    }

    /**
     * Serialize the meta.
     *
     * @param array $meta
     *
     * @return array
     */
    public function meta(array $meta): array
    {
        $metadata = !empty($meta) && !empty($meta['pagination']) ? $meta['pagination'] : [];
        unset($metadata['links']);
        $request = request();
        $metadata['filter'] = $request->input('filter', '');
        $metadata['sort_by'] = $request->input('order_by', '');
        $metadata['sort_order'] = $request->input('order_direction', '');
        return ['meta' => $metadata];
    }
}
