<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use ProcessMaker\Models\Process;
use ProcessMaker\Models\ProcessRequest;
use ProcessMaker\Traits\Exportable;
use Spatie\MediaLibrary\MediaCollections\Models\Media as MediaLibraryModel;

/**
 * Represents media files stored in the database
 *
 * @property int 'id',
 * @property int 'model_id',
 * @property string 'model_type',
 * @property string 'collection_name',
 * @property string 'name',
 * @property string 'file_name',
 * @property string 'mime_type',
 * @property string 'disk',
 * @property string 'size',
 * @property string 'manipulations',
 * @property string 'custom_properties',
 * @property string 'responsive_images',
 * @property string 'order_column',
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="mediaEditable",
 *   @OA\Property(property="id", type="integer", format="id"),
 *   @OA\Property(property="model_id", type="integer", format="id"),
 *   @OA\Property(property="model_type", type="string", format="id"),
 *   @OA\Property(property="collection_name", type="string"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="file_name", type="string"),
 *   @OA\Property(property="mime_type", type="string"),
 *   @OA\Property(property="disk", type="string"),
 *   @OA\Property(property="size", type="integer"),
 *   @OA\Property(property="manipulations", type="object"),
 *   @OA\Property(property="custom_properties", type="object"),
 *   @OA\Property(property="responsive_images", type="object"),
 *   @OA\Property(property="order_column", type="integer"),
 * ),
 * @OA\Schema(
 *   schema="media",
 *   allOf={@OA\Schema(ref="#/components/schemas/mediaEditable")},
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * ),
 * @OA\Schema(
 *   schema="mediaExported",
 *   @OA\Property(property="url", type="string"),
 * )
 */
class Media extends MediaLibraryModel
{
    use Exportable;
    use HasFactory;

    protected $connection = 'processmaker';

    protected $table = 'media';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'model_id',
        'model_type',
        'collection_name',
        'name',
        'file_name',
        'mime_type',
        'disk',
        'size',
        'manipulations',
        'custom_properties',
        'responsive_images',
        'order_column',
    ];

    /**
     * Validation rules
     *
     * @param $existing
     *
     * @return array
     */
    public static function rules($existing = null)
    {
        return [
            'model_id' => 'required',
            'model_type' => 'required',
            'collection_name' => 'required',
            'name' => 'required,alpha_spaces',
            'file_name' => 'required',
            'mime_type' => 'required',
            'disk' => 'required',
            'size' => 'required',
            'manipulations' => 'required',
            'custom_properties' => 'required',
            'responsive_images' => 'required',
            'order_column' => 'required',
        ];
    }

    /**
     * The binary UUID attributes that should be converted to text.
     *
     * @var array
     */
    protected $ids = [
        'model_id',
    ];

    /**
     * Override the default boot method to allow access to lifecycle hooks
     *
     * @return null
     */
    public static function boot()
    {
        parent::boot();
        self::creating(function ($media) {
            $user = pmUser();
            if (!$media->hasCustomProperty('createdBy')) {
                $media->setCustomProperty('createdBy', $user ? $user->id : null);
            }
            $media->setCustomProperty('updatedBy', $user ? $user->id : null);
        });
        self::saving(function ($media) {
            if ($media->model instanceof ProcessRequest) {
                if (empty($media->getCustomProperty('data_name'))) {
                    throw ValidationException::withMessages(['data_name' => 'data_name is required']);
                }
            }
            $media->setCustomProperty('updatedBy', pmUser() ? pmUser()->id : null);
        });
    }

    public function isPublicFile()
    {
        if ($this->model instanceof ProcessRequest) {
            if ($this->model->process && $this->model->process->package_key == 'package-files/public-files') {
                return true;
            }
        }

        return false;
    }

    public function getManagerNameAttribute()
    {
        if (isset($this->custom_properties['data_name'])) {
            return last(explode('/', $this->custom_properties['data_name']));
        } else {
            return $this->name;
        }
    }

    public function getManagerUrlAttribute()
    {
        if ($this->isPublicFile()) {
            $route = route('file-manager.index');

            return $route . '#/public/' . $this->custom_properties['data_name'];
        } else {
            return route('requests.show.files.viewer', [
                'request' => $this->model,
                'filePath' => $this->custom_properties['data_name'],
            ]);
        }
    }

    /**
     * Save the media related to the Process
     *
     * @param  Process $process
     * @param array $properties
     * @param string $key
     *
     * @return void
     */
    public function saveProcessMedia(Process $process, $properties, $key = 'uuid')
    {
        $collectionName = 'images_carousel';
        $exist = $process->media()->where($key, $properties[$key])->exists();
        if (!$exist) {
            // Store the images related move to MEDIA
            $process->addMediaFromBase64($properties['url'])
                ->withCustomProperties(['type' => $properties['type']])
                ->toMediaCollection($collectionName);
        }
    }

    /**
     * getFilesRequest
     *
     * @param  ProcessRequest $request
     * @return Collection | Media
     */
    public static function getFilesRequest(ProcessRequest $request, $id = null)
    {
        $requestTokenIds = [$request->id];
        if ($request->collaboration && $request->collaboration->requests()) {
            // Get all processes and subprocesses request token id's ..
            $requestTokenIds = $request->collaboration->requests->pluck('id');
        }

        // Return a single file when $id is set
        if ($id) {
            $mediaById = self::findOrFail($id);

            if (!in_array($mediaById->process_request_id, $requestTokenIds)) {
                abort(404, __('File is not part of this request'));
            }

            return $mediaById;
        }

        // Get all files for process and all subprocesses ..
        return self::where('model_type', ProcessRequest::class)
            ->whereIn('model_id', $requestTokenIds)->get();
    }

    public function getProcessRequestIdAttribute()
    {
        if ($this->model_type == TaskDraft::class) {
            return $this->model->processRequestToken->processRequest->id;
        } elseif ($this->model_type == ProcessRequest::class) {
            return $this->model->id;
        }
        abort(404, 'No process request id for ' . $this->model_type);
    }

    public function toArray()
    {
        $array = parent::toArray();

        $array['process_request_id'] = $this->process_request_id;

        return $array;
    }

    /**
     * Check if the S3 is ready to use
     */
    public static function s3IsReady()
    {
        return config('filesystems.disks.s3.key')
            && config('filesystems.disks.s3.secret')
            && config('filesystems.disks.s3.region')
            && config('filesystems.disks.s3.bucket');
    }
}
