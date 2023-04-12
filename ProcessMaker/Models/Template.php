<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ProcessMakerModel;
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
        'process' => [Process::class, ProcessTemplate::class, ProcessCategory::class, 'process_category_id', 'process_templates'],
    ];

    public function index(String $type, Request $request)
    {
        return (new $this->types[$type][1])->index($request);
    }

    public function store(string $type, Request $request): mixed
    {
        $request->validate(self::rules(true, $this->types[$type][4]));

        return (new $this->types[$type][1])->save($request);
    }

    public function updateTemplate(string $type, Request $request): mixed
    {
        $request->validate(self::rules(true, $this->types[$type][4]));

        return (new $this->types[$type][1])->updateTemplate($request);
    }

    public function updateTemplateConfigs(string $type, Request $request): mixed
    {
        $request->validate(self::rules(true, $this->types[$type][4]));

        return (new $this->types[$type][1])->updateTemplateConfigs($request);
    }

    public function create(string $type, Request $request): mixed
    {
        $request->validate($this->types[$type][1]::rules($request));

        return (new $this->types[$type][1])->create($request);
    }

    public function deleteTemplate(string $type, Request $request): mixed
    {
        $id = $request->id;

        return (new $this->types[$type][1])->destroy($id);
    }

    public function user(): mixed
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categories()
    {
        $categoryClass = $this->types[$type][2];
        $categoryColumn = $this->types[$type][3];

        return $this->belongsTo($categoryClass, $categoryColumn)->withDefault();
    }

    public static function rules($existing = null, $table = null): array
    {
        $unique = Rule::unique($table ?? 'templates')->ignore($existing);

        return [
            'name' => ['required', $unique, 'alpha_spaces', 'max:255'],
            'description' => 'required',
            'process_id' => 'nullable',
            'user_id' => 'nullable',
            'manifest' => 'required',
            'svg' => 'nullable',
        ];
    }
}
