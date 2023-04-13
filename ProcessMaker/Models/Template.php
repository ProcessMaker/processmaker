<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    protected $guarded = [
        'id',
        'uuid',
        'created_at',
        'updated_at',
    ];

    private $templateType;

    protected array $types = [
        'process' => [Process::class, ProcessTemplate::class, ProcessCategory::class, 'process_category_id'],
    ];

    public function index(String $type, Request $request)
    {
        return (new $this->types[$type][1])->index($request);
    }

    public function show(String $type, Request $request)
    {
        $templates = (new $this->types[$type][1])->show($request);

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

        return (new $this->types[$type][1])->save($request);
    }

    public function updateTemplateManifest(string $type, int $processId, $request)
    {
        $response = (new $this->types[$type][1])->updateTemplateManifest($processId, $request);

        return $response;
    }

    public function updateTemplate(string $type, Request $request)
    {
        return (new $this->types[$type][1])->updateTemplate($request);
    }

    public function updateTemplateConfigs(string $type, Request $request)
    {
        $existingTemplate = $this->checkForExistingTemplates($type, $request);
        if (!is_null($existingTemplate)) {
            return response()->json([
                'name' => ['The template name must be unique.'],
                'id' => $existingTemplate['id'],
                'templateName' => $existingTemplate['name'],
            ], 409);
        }

        return (new $this->types[$type][1])->updateTemplateConfigs($request);
    }

    public function create(string $type, Request $request)
    {
        return (new $this->types[$type][1])->create($request);
    }

    public function deleteTemplate(string $type, Request $request)
    {
        $id = $request->id;

        return (new $this->types[$type][1])->destroy($id);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categories()
    {
        $categoryClass = $this->types[$type][2];
        $categoryColumn = $this->types[$type][3];

        return $this->belongsTo($categoryClass, $categoryColumn)->withDefault();
    }

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
        $result = (new $this->types[$type][1])->existingTemplate($request);
        if (!is_null($result)) {
            return [$result['id'], $result['name']];
        }

        return null;
    }
}
