<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Models\TemplateCategory;
use ProcessMaker\Templates\ProcessTemplate;
use ProcessMaker\Traits\Exportable;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HasUuids;
use ProcessMaker\Traits\HideSystemResources;

class Template extends ProcessMakerModel
{
    use HasFactory;
    use HasUuids;
    use HideSystemResources;
    use HasCategories;
    use Exportable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'uuid',
        'created_at',
        'updated_at',
    ];

    /**
     * Table columns.
     *
     * @var array
     */
    protected $columns = [
        'id',
        'name',
        'description',
        'user_id',
        'manifest',
        'svg',
        'is_system',
        'created_at',
        'updated_at',
    ];

    private $templateType;

    private $request;

    protected array $types = [
        'process' => [Process::class, ProcessTemplate::class, ProcessCategory::class, 'process_category_id'],
    ];

    /**
     * Get a list of templates
     *
     * @param string $type
     * @param  \Illuminate\Http\Request $request
     */
    public function index(String $type, Request $request)
    {
        $templates = (new $this->types[$type][1])->index($request);

        return $templates;
    }

    /**
     * Store a newly created template
     *
     * @param string $type
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(string $type, Request $request)
    {
        [$id, $name] = $this->checkForExistingTemplates($type, $request);

        if ($id) {
            return response()->json([
                'name' => ['The template name must be unique.'],
                'id' => $id,
                'templateName' => $name,
            ], 409);
        }

        $response = (new $this->types[$type][1])->save($request);

        return $response;
    }

    /**
     * Update an existing template
     *
     * @param string $type
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTemplate(string $type, Request $request)
    {
        if (!isset($request->process_id)) {
            // This is an update from the template configs page. We need to check if the template name was updated and already exists
            [$id, $name] = $this->checkForExistingTemplates($type, $request);

            if ($id) {
                return response()->json([
                    'name' => ['The template name must be unique.'],
                    'id' => $id,
                    'templateName' => $name,
                ], 409);
            }
        }
        // This is an update from the process designer page. This will overwrite the template with new data. We do not need to check for existing templates
        $response = (new $this->types[$type][1])->update($request);

        return $response;
    }

    /**
     * Get the creator/author of this template.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the creator/author of this template.
     */
    public function categories()
    {
        $categoryClass = $this->types[$type][2];
        $categoryColumn = $this->types[$type][3];

        return $this->belongsTo($categoryClass, $categoryColumn)->withDefault();
    }

    /**
     * Validation rules.
     *
     * @param null $existing
     *
     * @return array
     */
    public static function rules($existing = null)
    {
        $unique = Rule::unique('templates')->ignore($existing);

        return [
            'name' => ['required', $unique, 'alpha_spaces'],
            'description' => 'required',
            'process_id' => 'required',
            'manifest' => 'required',
            'svg' => 'nullable',
        ];
    }

    private function checkForExistingTemplates($type, $request)
    {
        [$id, $name] = (new $this->types[$type][1])->existingTemplate($request);

        return [$id, $name];
    }
}
