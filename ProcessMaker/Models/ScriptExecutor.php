<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Traits\HasVersioning;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ScriptExecutor extends Model
{
    use HasVersioning;

    protected $fillable = [
        'title', 'description', 'language', 'config'
    ];

    protected $appends = ['scripts_count'];

    public static function install($params)
    {
        $language = $params['language'];
        try {
            $initialExecutor = self::initialExecutor($language);
        } catch(ModelNotFoundException $e) {
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
        return self::where('language', $language)
            ->orderBy('created_at', 'asc')
            ->firstOrFail();
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
        $initDockerfile = config('script-runners.' . $language . '.init_dockerfile');
        
        // remove check after lang packages updated
        if (!is_array($initDockerfile)) {
            $initDockerfile = explode("\n", $initDockerfile);
        }
        $dockerfile .= "\n" . implode("\n", $initDockerfile);

        return $dockerfile;
    }

    public static function packagePath($language)
    {
        $config = config('script-runners');
        if (!isset($config[$language])) {
            throw new \ErrorException("Language not in config: " . $language);
        }
        return config('script-runners.' . $language . '.package_path');
    }
    
    public static function rules($existing = null)
    {
        return [
            'title' => 'required',
            'language' => [
                'required',
                Rule::in(Script::scriptFormatValues())
            ],
        ];
    }

    public static function list($language = null)
    {
        $list = [];
        $executors =
            self::orderBy('language', 'asc')
            ->orderBy('created_at', 'asc');

        if ($language) {
            $executors->where('language', $language);
        }

        foreach ($executors->get() as $executor) {
            $list[$executor->id] = $executor->language . " - " . $executor->title;
        }
        return $list;
    }

    public function dockerImageName()
    {
        $lang = strtolower($this->language);
        $id = $this->id;
        $tag = 'latest'; // might change with script executor versions
        return "processmaker4/executor-${lang}-${id}:${tag}";
    }

    public function scripts()
    {
        return $this->hasMany(Script::class);
    }

    public function getScriptsCountAttribute()
    {
        return $this->scripts()->count();
    }
}
