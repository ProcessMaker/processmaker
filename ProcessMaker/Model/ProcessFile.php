<?php
namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Process files stores templates or public files that could be used in
 * email messages, publishing resources and routing screens.
 *
 * @package ProcessMaker\Model
 *
 * @property int $PRF_ID
 * @property string $PRF_UID
 * @property integer $process_id
 * @property string $USR_UID
 * @property string $PRF_UPDATE_USR_UID
 * @property string $PRF_PATH
 * @property string $PRF_TYPE
 * @property boolean $PRF_EDITABLE
 * @property string $PRF_DRIVE
 * @property string $PRF_PATH_FOR_CLIENT
 * @property \Carbon\Carbon $PRF_CREATE_DATE
 * @property \Carbon\Carbon $PRF_UPDATE_DATE
 * @property boolean $IS_USED_AS_ROUTING_SCREEN
 * @property \Illuminate\Database\Eloquent\Collection $emailEvents
 * @method \Illuminate\Database\Eloquent\Builder static withPath($path)
 */
class ProcessFile extends Model
{
    use Notifiable;

    /**
     * The table associated with the model.
     * @var string $table
     */
    protected $table = 'PROCESS_FILES';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'PRF_ID';

    /**
     * The name of the "created at" column.
     */
    const CREATED_AT = 'PRF_CREATE_DATE';

    /**
     * The name of the "updated at" column.
     */
    const UPDATED_AT = 'PRF_UPDATE_DATE';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'PRF_UID',
        'process_id',
        'USR_UID',
        'PRF_UPDATE_USR_UID',
        'PRF_PATH',
        'PRF_TYPE',
        'PRF_EDITABLE',
        'PRF_DRIVE',
        'PRF_PATH_FOR_CLIENT',
        'PRF_CREATE_DATE',
        'PRF_UPDATE_DATE',
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'PRF_UID' => null,
        'process_id' => null,
        'USR_UID' => null,
        'PRF_UPDATE_USR_UID' => null,
        'PRF_PATH' => '',
        'PRF_TYPE' => '',
        'PRF_EDITABLE' => '1',
        'PRF_DRIVE' => '',
        'PRF_PATH_FOR_CLIENT' => '',
        'PRF_CREATE_DATE' => null,
        'PRF_UPDATE_DATE' => null,
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'PRF_UID' => 'string',
        'process_id' => 'int',
        'USR_UID' => 'string',
        'PRF_UPDATE_USR_UID' => 'string',
        'PRF_PATH' => 'string',
        'PRF_TYPE' => 'string',
        'PRF_EDITABLE' => 'boolean',
        'PRF_DRIVE' => 'string',
        'PRF_PATH_FOR_CLIENT' => 'string',
        'PRF_CREATE_DATE' => 'datetime',
        'PRF_UPDATE_DATE' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'IS_USED_AS_ROUTING_SCREEN',
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
        return 'PRF_UID';
    }

    /**
     * Returns true, if the owner process, or any of its tasks uses this file
     * as a routing screen.
     *
     * @return boolean
     */
    public function getIsUsedAsRoutingScreenAttribute()
    {
        $filename = basename($this->PRF_PATH);
        return $this->process->derivation_screen_template === $filename
            || $this->process->tasks()
                ->where('TAS_DERIVATION_SCREEN_TPL', $filename)
                ->count() > 0;
    }

    /**
     * Returns true, if the file can be updated.
     *
     * @return boolean
     */
    public function setPrfEditableAttribute()
    {
        $extension = File::extension($this->PRF_PATH);
        $value =  array_search($extension, self::EDITABLE_FILE_EXTENSIONS) !== false;
        $this->attributes['PRF_EDITABLE'] = $value ? 1 : 0;
    }

    /**
     * Return the path of the file inside the disk.
     *
     * @return string
     */
    public function getPathInDisk()
    {
        return $this->process->uid . '/' . ltrim($this->PRF_PATH_FOR_CLIENT, '/');
    }

    /**
     * Return the path of the file that client see.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->PRF_DRIVE . '/' . ltrim($this->PRF_PATH_FOR_CLIENT, '/');
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
        return Storage::disk(static::DISKS[$this->PRF_DRIVE]);
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
        return $this->belongsTo( User::class, "USR_UID", "uid");
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
            "PRF_UID",
            "PRF_UID"
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
            ->where('PRF_PATH_FOR_CLIENT', 'like', '%' . $pathForClient . '%');
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
            if ($model->PRF_TYPE === 'folder') {
                $model->disk()->deleteDirectory($model->getPathInDisk());
            } else {
                $model->disk()->delete($model->getPathInDisk());
            }
        });
    }
}
