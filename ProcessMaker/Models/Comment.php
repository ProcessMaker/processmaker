<?php

namespace ProcessMaker\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use ProcessMaker\Traits\SerializeToIso8601;
use ProcessMaker\Traits\SqlsrvSupportTrait;

/**
 * Represents a business process definition.
 *
 * @property int 'id',
 * @property int 'user_id',
 * @property int 'commentable_id',
 * @property string 'commentable_type',
 * @property int 'up',
 * @property int 'down',
 * @property string 'subject',
 * @property string 'body',
 * @property bool 'hidden',
 * @property string 'type',
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $created_at
 *
 * @OA\Schema(
 *   schema="commentsEditable",
 *   @OA\Property(property="id", type="string", format="id"),
 *   @OA\Property(property="user_id", type="string", format="id"),
 *   @OA\Property(property="commentable_id", type="string", format="id"),
 *   @OA\Property(property="commentable_type", type="string"),
 *   @OA\Property(property="up", type="integer"),
 *   @OA\Property(property="down", type="integer"),
 *   @OA\Property(property="subject", type="string"),
 *   @OA\Property(property="body", type="string"),
 *   @OA\Property(property="hidden", type="boolean"),
 *   @OA\Property(property="type", type="string", enum={"LOG", "MESSAGE"}),
 * ),
 * @OA\Schema(
 *   schema="comments",
 *   allOf={@OA\Schema(ref="#/components/schemas/commentsEditable")},
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time"),
 * )
 */
class Comment extends ProcessMakerModel
{
    use SerializeToIso8601;
    use SqlsrvSupportTrait;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'parent_id',
        'group_id',
        'group_name',
        'commentable_id',
        'commentable_type',
        'subject',
        'body',
        'hidden',
        'type',
        'case_number',
    ];

    protected $casts = [
        'up' => 'array',
        'down' => 'array',
    ];

    public static function rules()
    {
        return [
            'user_id' => 'required',
            'commentable_id' => 'required',
            'commentable_type' => 'required|in:' . ProcessRequestToken::class . ',' . ProcessRequest::class,
            'subject' => 'required',
            'body' => 'required',
            'hidden' => 'required|boolean',
            'type' => 'required|in:LOG,MESSAGE',
        ];
    }

    /**
     * Scope comments hidden
     *
     * @param $query
     * @param $parameter hidden, visible, all
     *
     * @return mixed
     */
    public function scopeHidden($query, $parameter)
    {
        switch ($parameter) {
            case 'visible':
                return $query->where('hidden', false);
                break;
            case 'hidden':
                return $query->where('hidden', true);
                break;
            case 'ALL':
                return $query;
                break;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function commentable()
    {
        return $this->morphTo(null, null, 'commentable_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Children comments with user
     */
    public function children()
    {
        return $this->hasMany(self::class, 'commentable_id', 'id')
            ->where('commentable_type', self::class)
            ->with('user');
    }

    /**
     * Replied message.
     */
    public function repliedMessage()
    {
        return $this->hasOne(self::class, 'id', 'parent_id')
                ->with('user');
    }

    /**
     * Get element_name attribute for notifications
     */
    public function getElementNameAttribute()
    {
        if ($this->commentable instanceof ProcessRequest) {
            return $this->commentable->name;
        } elseif ($this->commentable instanceof ProcessRequestToken) {
            return $this->commentable->getDefinition()['name'];
        } elseif ($this->commentable instanceof Media) {
            return $this->commentable->manager_name;
        } elseif ($this->commentable instanceof self) {
            return $this->commentable->element_name;
        } elseif ($this->commentable instanceof Process) {
            return $this->commentable->name;
        } else {
            return get_class($this->commentable);
        }
    }

    public function setBodyAttribute($value)
    {
        // Get al mentions and replace with the user id in mustaches
        $value = mb_ereg_replace_callback('(^|\s)([@][\p{L}\p{N}\-_]+)', function ($matches) {
            $username = str_replace([' @', '@'], '', $matches[0]);
            $user = User::where('username', $username)->first();
            if ($user) {
                return ' {{' . $user->id . '}}';
            }

            return $matches[0];
        }, $value);
        $this->attributes['body'] = $value;
    }

    public function getBodyAttribute($body)
    {
        // Replace mustache user id with username
        $body = preg_replace_callback('/\{\{(\d+)\}\}/', function ($matches) {
            $user = User::find($matches[1]);
            if ($user) {
                return '@' . $user->username;
            }
        }, $body);

        return $body;
    }

    /**
     * Get url attribute for notifications
     */
    public function getUrlAttribute($id = null)
    {
        if (!$id) {
            $id = $this->id;
        }

        if ($this->commentable instanceof ProcessRequest) {
            return sprintf('/requests/%s#comment-%s', $this->commentable->id, $id);
        } elseif ($this->commentable instanceof ProcessRequestToken) {
            return sprintf('/tasks/%s/edit#comment-%s', $this->commentable->id, $id);
        } elseif ($this->commentable instanceof Process) {
            return sprintf('/modeler/%s#comment-%s', $this->commentable->id, $id);
        } elseif ($this->commentable instanceof Media) {
            return $this->commentable->manager_url;
        } elseif ($this->commentable instanceof self) {
            return $this->commentable->getUrlAttribute($this->id);
        } else {
            return '/';
        }
    }
}
