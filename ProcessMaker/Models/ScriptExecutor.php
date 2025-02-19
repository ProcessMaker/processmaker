<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use ProcessMaker\Enums\ScriptExecutorType;
use ProcessMaker\Exception\ScriptLanguageNotSupported;
use ProcessMaker\Facades\Docker;
use ProcessMaker\Traits\Exportable;
use ProcessMaker\Traits\HasVersioning;
use ProcessMaker\Traits\HideSystemResources;

/**
 * Represents an Eloquent model of a Script Executor
 *
 *
 * @property int id
 * @property string title
 * @property text description
 * @property string language
 * @property text config
 * @property string value
 * @property text initDockerFile
 *
 * @OA\Schema(
 *   schema="scriptExecutorsEditable",
 *   @OA\Property(property="title", type="string"),
 *   @OA\Property(property="description", type="string"),
 *   @OA\Property(property="language", type="string"),
 *   @OA\Property(property="config", type="string"),
 *   @OA\Property(property="is_system", type="boolean"),
 * ),
 * @OA\Schema(
 *   schema="scriptExecutors",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/scriptExecutorsEditable"),
 *     @OA\Schema(
 *       @OA\Property(property="id", type="integer", format="id"),
 *       @OA\Property(property="created_at", type="string", format="date-time"),
 *       @OA\Property(property="updated_at", type="string", format="date-time"),
 *     )
 *   },
 * ),
 *
 * @OA\Schema(
 *   schema="availableLanguages",
 *   @OA\Property(property="text", type="string"),
 *   @OA\Property(property="value", type="string"),
 *   @OA\Property(property="initDockerFile", type="string"),
 * ),
 */
class ScriptExecutor extends ProcessMakerModel
{
    const MICROSERVICE_LANGUAGES = [
        'php' => 'PHP Executor',
        'javascript' => 'JavaScript Executor',
        'python' => 'Python Executor',
        'csharp' => 'C# Executor',
        'java' => 'Java Executor',
        'javascript-ssr' => 'JavaScript SSR Executor',
    ];

    use HasVersioning;
    use Exportable;
    use HideSystemResources;

    protected $fillable = [
        'title', 'description', 'language', 'config', 'is_system', 'type',
    ];

    protected $casts = [
        'type' => ScriptExecutorType::class,
    ];

    // Lua and R are deprecated. This scope can be removed
    // when they are removed permanently.
    public function scopeActive($query)
    {
        return $query->whereNotIn('language', Script::deprecatedLanguages);
    }

    public static function install($params)
    {
        $language = $params['language'];
        try {
            $initialExecutor = self::initialExecutor($language);
        } catch (ScriptLanguageNotSupported $e) {
            $initialExecutor = null;
        }

        if ($initialExecutor) {
            $initialExecutor->update($params);
        } else {
            $initialExecutor = self::create($params);
            Script::where('language', $language)->update(['script_executor_id' => $initialExecutor->id]);
            ScriptVersion::where('language', $language)->update(['script_executor_id' => $initialExecutor->id]);
        }

        return $initialExecutor;
    }

    public static function initialExecutor($language)
    {
        $initialExecutor = self::where('language', $language)
            ->orderBy('created_at', 'asc')
            ->first();
        if (!$initialExecutor) {
            if (app()->runningInConsole()) {
                Log::error('Script Executor not found for language: ' . $language);

                return null;
            } else {
                throw new ScriptLanguageNotSupported($language);
            }
        }

        return $initialExecutor;
    }

    public function versions()
    {
        return $this->hasMany(ScriptExecutorVersion::class);
    }

    public static function initDockerfile($language)
    {
        // remove try/catch block after lang packages updated
        try {
            $dockerfile = file_get_contents(self::packagePath($language) . '/Dockerfile');
        } catch (\ErrorException $e) {
            $dockerfile = '';
        }
        $initDockerfile = self::config($language)['init_dockerfile'];

        // remove check after lang packages updated
        if (!is_array($initDockerfile)) {
            $initDockerfile = explode("\n", $initDockerfile);
        }
        $dockerfile .= "\n" . implode("\n", $initDockerfile);

        return $dockerfile;
    }

    public static function finalInstructions($language)
    {
        $finalInstruction = self::config($language)['final_instructions'] ?? '';

        return is_array($finalInstruction) ? implode("\n", $finalInstruction) : $finalInstruction;
    }

    public static function packagePath($language)
    {
        return self::config($language)['package_path'];
    }

    public static function config($language)
    {
        $config = config('script-runners');
        $language = strtolower($language);
        if (!isset($config[$language])) {
            throw new \ErrorException('Language not in config: ' . $language);
        }

        return $config[$language];
    }

    public static function rules($existing = null)
    {
        if ($existing) {
            $allowedLanguages = Script::scriptFormatValues();
        } else {
            $allowedLanguages = array_filter(Script::scriptFormatValues(), function ($language) {
                return !in_array($language, Script::deprecatedLanguages);
            });
        }

        return [
            'title' => 'required',
            'language' => [
                'required',
                Rule::in($allowedLanguages),
            ],
            'type' => [
                'sometimes',
                new Enum(ScriptExecutorType::class),
                'nullable',
            ],
        ];
    }

    public static function list($language = null, $forEditMode = false)
    {
        $list = [];
        $executors =
            self::active()->where('is_system', false)->orderBy('language', 'asc')
            ->orderBy('created_at', 'asc');

        if ($language) {
            $executors->where('language', $language);

            // If the list is for edition mode and "php" language is selected also include "php-nayra"
            if ($language === 'php' && $forEditMode) {
                $executors->orWhere('language', 'php-nayra');
            }

            // If the list is for edition mode and "php-nayra" language is selected also include "php"
            if ($language === 'php-nayra' && $forEditMode) {
                $executors->orWhere('language', 'php');
            }
        }

        foreach ($executors->get() as $executor) {
            $list[$executor->id] = [
                'language' => $executor->language,
                'title' => $executor->title,
            ];
        }

        return $list;
    }

    public function dockerImageName()
    {
        $lang = strtolower($this->language);
        $id = $this->id;
        $tag = $this->imageTag();
        $instance = config('app.instance');

        return "processmaker4/executor-{$instance}-{$lang}-{$id}:{$tag}";
    }

    public function imageTag()
    {
        $config = self::config($this->language);
        $tag = 'v';
        if (isset($config['package_version'])) {
            $tag .= $config['package_version'];
        }

        return $tag;
    }

    public function scripts()
    {
        return $this->hasMany(Script::class);
    }

    public function getScriptsCountAttribute()
    {
        return $this->scripts()->count();
    }

    public function dockerImageExists()
    {
        $images = self::listOfExecutorImages();

        return in_array($this->dockerImageName(), $images);
    }

    public static function listOfExecutorImages($filterByLanguage = null)
    {
        exec(Docker::command() . ' images | awk \'{r=$1":"$2; print r}\'', $result);

        $instance = config('app.instance');

        return array_values(array_filter($result, function ($image) use ($filterByLanguage, $instance) {
            $filter = "processmaker4/executor-{$instance}-";
            if ($filterByLanguage) {
                $filter .= $filterByLanguage . '-';
            }

            return strpos($image, $filter) !== false;
        }));
    }
}
