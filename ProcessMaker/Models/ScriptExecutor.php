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
        $initDockerfile = self::config($language)['init_dockerfile'];
        
        // remove check after lang packages updated
        if (!is_array($initDockerfile)) {
            $initDockerfile = explode("\n", $initDockerfile);
        }
        $dockerfile .= "\n" . implode("\n", $initDockerfile);

        return $dockerfile;
    }

    public static function packagePath($language)
    {
        return self::config($language)['package_path'];
    }

    public static function config($language) {
        $config = config('script-runners');
        if (!isset($config[$language])) {
            throw new \ErrorException("Language not in config: " . $language);
        }
        return $config[$language];
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
        $tag = $this->imageTag();
        return "processmaker4/executor-${lang}-${id}:${tag}";
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

    /**
     * If we need to run a docker image in a test, chances are the Executor IDs wont
     * match up with the docker image names. To prevent errors, lets just grab the first
     * image available on the testing machine and explicitly set the executor image.
     * When 'image' is set in the config, it will ignore the one provided by the factory executor.
     *
     * @param string $language
     * @return void
     */
    public static function setTestConfig($language)
    {
        $useImage = null;
        exec('docker images | awk \'{r=$1":"$2; print r}\'', $out);
        foreach ($out as $image) {
            $search = "processmaker4/executor-${language}-";
            $found = strpos($image, $search) !== false;
            if ($found) {
                $useImage = $image;
                break;
            }
        }
        if (!$useImage) {
            throw new \Exception("No matching docker image for $language");
        }
        config(["script-runners.${language}.image" => $useImage]);
    }
}
