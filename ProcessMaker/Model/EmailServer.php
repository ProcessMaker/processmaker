<?php

namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use ProcessMaker\Model\Traits\Uuid;

/**
 * Represents an Eloquent model of a Email Server
 *
 * @property integer id
 * @property string uid
 * @property string engine
 * @property string server
 * @property integer port
 * @property boolean rauth
 * @property string account
 * @property string password
 * @property string from_mail
 * @property string from_name
 * @property string smtp_secure
 * @property boolean try_send_immediately
 * @property string mail_to
 * @property string by_default
 *
 */
class EmailServer extends Model
{

    use Notifiable,
        Uuid;

    //type engine
    const TYPE_MAIL = 'MAIL';
    const TYPE_PHP_MAILER = 'PHPMAILER';

    //type connection secure
    const NO_SECURE = 'NO';
    const SSL_SECURE = 'SSL';
    const TLS_SECURE = 'TLS';

    protected $fillable = [
        'id',
        'uid',
        'engine',
        'server',
        'port',
        'rauth',
        'account',
        'password',
        'from_mail',
        'from_name',
        'smtp_secure',
        'try_send_immediately',
        'mail_to',
        'by_default',
        'created_at',
        'updated_at'
    ];

    protected $rules = [
        'uid' => 'max:36',
        'rauth' => 'required|boolean',
        'account' => 'required',
        'password' => 'required',
        'try_send_immediately' => 'required|boolean',
        'engine' => 'required|in:' . self::TYPE_MAIL . ',' . self::TYPE_PHP_MAILER,
        'smtp_secure' => 'required|in:' . self::NO_SECURE . ',' . self::SSL_SECURE . ',' . self::TLS_SECURE
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
     * Accessor properties to json
     *
     * @param $value
     *
     * @return array|null
     */
    public function getPasswordAttribute($value): ?array
    {
        return empty($value) ? null : decrypt($value);
    }

    /**
     * Mutator properties json decode
     *
     * @param $value
     *
     * @return void
     */
    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = empty($value) ? null : bcrypt($value);
    }

}
