<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Traits\HasVersioning;

class ScriptExecutor extends Model
{
    use HasVersioning;

    protected $fillable = [
        'title', 'description', 'language', 'config'
    ];

    protected $appends = ['initDockerfile'];

    public static function install($params)
    {
        $language = $params['language'];
        $initialExecutor = self::initialExecutor($language);

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
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function versions()
    {
        return $this->hasMany(ScriptExecutorVersion::class);
    }

    public function getInitDockerfileAttribute()
    {
        $dockerfile = file_get_contents($this->packagePath() . '/Dockerfile');
        $initDockerfile = config('script-runners.' . $this->language . '.init_dockerfile');
        $dockerfile .= "\n" . implode("\n", $initDockerfile);
        return $dockerfile;
    }

    public function packagePath()
    {
        return config('script-runners.' . $this->language . '.package_path');
    }
}
