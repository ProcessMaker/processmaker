<?php

namespace ProcessMaker\Managers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Exception\DoesNotBelongToProcessException;
use ProcessMaker\Exception\ValidationException;
use ProcessMaker\Models\Form;

/**
 * TODO: Move functionlaity to controller and delete this file
 */
class FormsManager
{

    /**
     * Get a list of All Forms.
     *
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function index(array $options): LengthAwarePaginator
    {
        $query = Form::query();
        $filter = $options['filter'];
        if (!empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(function ($query) use ($filter) {
                $query->Where('title', 'like', $filter)
                    ->orWhere('description', 'like', $filter)
                    ->orWhere('type', 'like', $filter)
                    ->orWhere('config', 'like', $filter);
            });
        }
        return $query->orderBy($options['sort_by'], $options['sort_order'])
            ->paginate($options['per_page'])
            ->appends($options);
    }

    /**
     * Create a new Form.
     *
     * @param array $data
     *
     * @return Form
     * @throws \Throwable
     */
    public function save($data): Form
    {
        $form = new Form();
        $form->fill($data);
        $form->saveOrFail();

        return $form->refresh();
    }

    /**
     * Update Form.
     *
     * @param Form $form
     * @param array $data
     *
     * @return Form
     * @throws \Throwable
     */
    public function update(Form $form, $data): Form
    {
        $form->fill($data);
        $form->saveOrFail();
        return $form->refresh();
    }

    /**
     * Remove Form in a process.
     *
     * @param Form $form
     *
     * @return bool|null
     * @throws \Exception
     */
    public function remove(Form $form): ?bool
    {
        return $form->delete();
    }
}
