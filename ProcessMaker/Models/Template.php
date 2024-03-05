<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessMakerModel;
use ProcessMaker\Models\Screen;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\TemplateCategory;
use ProcessMaker\Templates\ProcessTemplate;
use ProcessMaker\Templates\ScreenTemplate;
use ProcessMaker\Traits\Exportable;
use ProcessMaker\Traits\HasCategories;
use ProcessMaker\Traits\HasUuids;
use ProcessMaker\Traits\HideSystemResources;
use ProcessMaker\Traits\SerializeToIso8601;

class Template extends ProcessMakerModel
{
    use HasFactory;
    use HasUuids;
    use HideSystemResources;
    use HasCategories;
    use Exportable;
    use SerializeToIso8601;

    protected $guarded = [
        'id',
        'uuid',
        'created_at',
        'updated_at',
    ];

    private $templateType;

    protected array $types = [
        'process' => [Process::class, ProcessTemplate::class, ProcessCategory::class, 'process_category_id', 'process_templates'],
        'update-assets' => [Process::class, ProcessTemplate::class, ProcessCategory::class, 'process_category_id', 'process_templates'],
        'screen' => [Screen::class, ScreenTemplate::class, ScreenCategory::class, 'screen_category_id', 'screen_templates'],
    ];

    public function index(String $type, Request $request)
    {
        return (new $this->types[$type][1])->index($request);
    }

    public function show(String $type, Request $request)
    {
        return (new $this->types[$type][1])->show($request);
    }

    public function store(string $type, Request $request)
    {
        return (new $this->types[$type][1])->save($request);
    }

    public function updateTemplateManifest(string $type, int $processId, $request)
    {
        return (new $this->types[$type][1])->updateTemplateManifest($processId, $request);
    }

    public function updateTemplate(string $type, Request $request)
    {
        return (new $this->types[$type][1])->updateTemplate($request);
    }

    public function updateTemplateConfigs(string $type, Request $request)
    {
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

    public function publishTemplate(string $type, Request $request)
    {
        return (new $this->types[$type][1])->publishTemplate($request);
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

    public static function rules($existing = null, $table = null)
    {
        $unique = Rule::unique($table ?? 'templates')->ignore($existing);

        return [
            'name' => ['required', $unique, 'alpha_spaces', 'max:255'],
            'description' => 'required',
            'version' => ['required', 'regex:/^[0-9.]+$/'],
        ];
    }

    public function checkForExistingTemplates($type, $request)
    {
        $result = (new $this->types[$type][1])->existingTemplate($request);
        if (!is_null($result)) {
            return ['id' => $result['id'], 'name' => $result['name']];
        }

        return null;
    }
}
