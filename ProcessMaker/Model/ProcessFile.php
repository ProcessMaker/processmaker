<?php
namespace ProcessMaker\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ProcessMaker\Model\Traits\Uuid;

/**
 * Process files stores templates or public files that could be used in
 * email messages, publishing resources and routing screens.
 *
 * @package ProcessMaker\Model
 *
 * @property int $id
 * @property string $uid
 * @property integer $process_id
 * @property integer $user_id
 * @property integer $update_user_id
 * @property string $path
 * @property string $type
 * @property boolean $editable
 * @property string $drive
 * @property string $path_for_client
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property boolean $is_used_as_routing_screen
 * @property \illuminate\database\eloquent\collection $emailevents
 * @method \illuminate\database\eloquent\builder static withPath($path)
 */
class ProcessFile extends Model
{
    use Notifiable,
        Uuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uid',
        'process_id',
        'user_id',
        'update_user_id',
        'path',
        'type',
        'editable',
        'drive',
        'path_for_client',
        'created_at',
        'updated_at',
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'uid' => null,
        'process_id' => null,
        'user_id' => null,
        'update_user_id' => null,
        'path' => '',
        'type' => '',
        'editable' => '1',
        'drive' => '',
        'path_for_client' => '',
        'created_at' => null,
        'updated_at' => null,
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'uid' => 'string',
        'process_id' => 'int',
        'user_id' => 'int',
        'update_user_id' => 'int',
        'path' => 'string',
        'type' => 'string',
        'editable' => 'boolean',
        'drive' => 'string',
        'path_for_client' => 'string'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_used_as_routing_screen',
    ];

    /**
     * File extensions of files that can be updated.
     *
     */
    const EDITABLE_FILE_EXTENSIONS = [
        'docx', 'doc', 'html', 'php', 'jsp', 'xlsx', 'xls', 'js', 'css', 'txt'
    ];

    /**
     * Disks where ProcessFiles are stored.
     *
     */
    const DISKS = [
        'templates' => 'mailtemplates',
        'public' => 'public',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uid';
    }

    /**
     * Returns true, if the owner process, or any of its tasks uses this file
     * as a routing screen.
     *
     * @return boolean
     */
    public function getIsUsedAsRoutingScreenAttribute()
    {
        $filename = basename($this->path);
        return $this->process->derivation_screen_template === $filename
            || $this->process->tasks()
                ->where('routing_screen_template', $filename)
                ->count() > 0;
    }

    /**
     * Returns true, if the file can be updated.
     *
     * @return boolean
     */
    public function setPrfEditableAttribute()
    {
        $extension = File::extension($this->path);
        $value =  array_search($extension, self::EDITABLE_FILE_EXTENSIONS) !== false;
        $this->attributes['EDITABLE'] = $value ? 1 : 0;
    }

    /**
     * Return the path of the file inside the disk.
     *
     * @return string
     */
    public function getPathInDisk()
    {
        return $this->process->uid . '/' . ltrim($this->path_for_client, '/');
    }

    /**
     * Return the path of the file that client see.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->drive . '/' . ltrim($this->path_for_client, '/');
    }

    /**
     * Returns the contents of the file.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->disk()->get($this->getPathInDisk());
    }

    /**
     * Returns the contents of the file.
     *
     * @param string $content
     *
     * @return string
     */
    public function setContent($content)
    {
        return $this->disk()->put($this->getPathInDisk(), $content);
    }

    /**
     * Disk where the file is stored.
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function disk()
    {
        return Storage::disk(static::DISKS[$this->drive]);
    }

    /**
     * Process owner of the file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * User owner of the file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo( User::class, "user_id", "uid");
    }

    /**
     * Email events that use the process file.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function emailEvents()
    {
        return $this->hasMany(
            EmailEvent::class,
            "process_file_id",
            "id"
        );
    }

    /**
     * Find files from a path substring.
     * This should take in count that path could use \ or / as path
     * separator interchangeably.
     *
     * PMLOCAL-3367: Editing and deleting templates on Windows
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $pathInDisk Ex. processUid/folder/file.html
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithPath($query, $pathInDisk)
    {
        list($processUid, $pathForClient) = explode('/', ltrim($pathInDisk, '/'), 2);
        return $query->where('process_id', Process::where('uid', $processUid)->first()->id)
            ->where('path_for_client', 'like', '%' . $pathForClient . '%');
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::deleted(function (ProcessFile $model) {
            if ($model->TYPE === 'folder') {
                $model->disk()->deleteDirectory($model->getPathInDisk());
            } else {
                $model->disk()->delete($model->getPathInDisk());
            }
        });
    }
}
