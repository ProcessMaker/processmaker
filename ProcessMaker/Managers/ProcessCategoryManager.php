<?php

namespace ProcessMaker\Managers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorImplementation;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Model\ProcessCategory;
use Ramsey\Uuid\Uuid;

class ProcessCategoryManager
{

    /**
     * Provides a list of the process categories.
     *
     * @param array $options
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index(array $options)
    {
        $this->validate(
            [
                'start' => $options['start'],
                'limit' => $options['limit'],
            ],
            [
                'start' => 'nullable|numeric|min:0',
                'limit' => 'nullable|numeric|min:0',
            ]
        );
        $query = ProcessCategory::select([
                'uid',
                'name',
            ])->where('uid', '!=', '')
            ->withCount('processes');

        $options['filter'] === null ? : $query->where(
            'name', 'like', '%' . $options['filter'] . '%'
        );

        $options['sort_by'] === null ? : $query->orderBy(
            $options['sort_by'], $options['sort_order']
        );

        $options['start'] === null ? : $query->offset($options['start']);
        $options['limit'] === null ? : $query->limit($options['limit']);
        return $query->get();
    }

    /**
     * Stores a new process category.
     *
     * @param array $data
     *
     * @return \ProcessMaker\Model\ProcessCategory
     */
    public function store(array $data)
    {
        $this->validate(
            $data,
            [
                'cat_name' => 'required|string|max:100|unique:process_categories,name',
            ]
        );
        return ProcessCategory::create([
            'uid' => str_replace('-', '', Uuid::uuid4()),
            'name' => $data['cat_name'],
        ]);
    }

    /**
     * Update a process category.
     *
     * @param ProcessCategory $processCategory
     * @param array $data
     *
     * @return \ProcessMaker\Model\ProcessCategory
     */
    public function update(ProcessCategory $processCategory, array $data)
    {
        $this->validate(
            $data,
            [
                'cat_name' => 'required|string|max:100|unique:process_categories,name',
            ]
        );
        $processCategory->update([
            'name' => $data['cat_name'],
        ]);
        $processCategory->save();
        return $processCategory;
    }

    /**
     * Remove a process category.
     *
     * @param ProcessCategory $processCategory
     *
     * @return bool
     */
    public function remove(ProcessCategory $processCategory)
    {
        $this->validate(
            [
                'processCategory' => $processCategory,
            ],
            [
                'processCategory' => 'process_category_manager.category_does_not_have_processes',
            ]
        );
        return $processCategory->delete();
    }

    /**
     * Validate the given data with the given rules.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     *
     * @throws ValidationException
     */
    private function validate(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ) {
        $validator = Validator::make($data, $rules, $messages, $customAttributes);

        /**
         * Validate that the category does not have processes.
         */
        $validator->addExtension(
            'process_category_manager.category_does_not_have_processes',
            function ($attribute, $processCategory, $parameters, ValidatorImplementation $validator) {
                return $processCategory->processes()->count() === 0;
            }
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
