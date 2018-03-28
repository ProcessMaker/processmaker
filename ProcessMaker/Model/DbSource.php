<?php
namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Facades\DatabaseManager;
use Watson\Validating\ValidatingTrait;

/**
 * Represents an external database connection. Each DB Source is related to a owner process.
 * @package ProcessMaker\Model
 */
class DbSource extends Model
{
    use ValidatingTrait {
        isValid as public validatingIsValid;
    }

    // We do not store timestamps
    public $timestamps = false;
    protected $table = 'DB_SOURCE';
    protected $primaryKey = 'DBS_UID';
    public $incrementing = false;

    protected $appends = [
        'DBS_DATABASE_DESCRIPTION',
        //this attr. is rewritten
        'DBS_SERVER',
        //this attr. is rewritten
        'DBS_DATABASE_NAME'
    ];

    protected $rules = [
        'DBS_TYPE' => 'required',
        'DBS_ENCODE' => 'required'
    ];

    // List of supported engines
    const SUPPORTED_ENGINES = [
        'mysql' => ['id' => 'mysql', 'name' => 'MySql', 'defaultPort' => 3306],
        'pgsql' => ['id' => 'pgsql', 'name' => 'PostgreSql', 'defaultPort' => 5432],
        'sqlsrv' => ['id' => 'sqlsrv', 'name' => 'Microsoft SQL Server', 'defaultPort' => 1433],
        'oracle' => ['id' => 'oracle', 'name' => 'Oracle', 'defaultPort' => 1521]
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * This attribute is returned in the json of the API
     *
     * @param $value
     *
     * @return string
     */
    public function getDbsDatabaseDescriptionAttribute()
    {
        return $this->isTns()
            ? '[' . $this->DBS_TNS . ']' . $this->DBS_DESCRIPTION
            : $this->DBS_DESCRIPTION;
    }

    /**
     * The server attribute is overwritten when using a tns connection
     *
     * @param $value
     *
     * @return string
     */
    public function getDbsServerAttribute($value)
    {
        $server = $this->isTns()
            ? '[' . $this->DBS_TNS . ']'
            : $value;
        return $server;
    }

    /**
     * The database name attribute is overwritten when using a tns connection
     *
     * @param $value
     *
     * @return string
     */
    public function getDbsDatabaseNameAttribute($value)
    {
        $server = $this->isTns()
            ? '[' . $this->DBS_TNS . ']'
            : $value;
        return $server;
    }

    /**
     * Additional validations for the model
     *
     * @return bool
     */
    public function isValid()
    {
        if (!$this->validatingIsValid()) {
            return false;
        }

        //type validation
        $typesExists = [];
        foreach (DbSource::SUPPORTED_ENGINES as $val) {
            $typesExists[] = $val['id'];
        }

        if (!in_array($this->DBS_TYPE, $typesExists)) {
            $this->validationErrors->add('dbs_type', __('ID_DBC_TYPE_INVALID'));
        }

        if (isset($this->DBS_SERVER) && $this->DBS_SERVER == '' && !$this->isTns()) {
            $this->validationErrors->add('dbs_server', __('ID_DBC_SERVER_INVALID'));
        }

        if (isset($this->DBS_DATABASE_NAME) && $this->DBS_DATABASE_NAME == '' && !$this->isTns()) {
            $this->validationErrors->add('dbs_database_name', __('ID_DBC_DBNAME_INVALID'));
        }

        if (isset($this->DBS_PORT) &&
            ($this->DBS_PORT == '' || $this->DBS_PORT == 0)
        ) {
            if (!$this->isTns()) {
                $this->validationErrors->add('dbs_port', __('ID_DBC_PORT_INVALID'));
            }
        }

        if (isset($this->DBS_TNS) && $this->DBS_TNS == '' && $this->isTns()) {
            $this->validationErrors->add('dbs_tns', __('ID_DBC_TNS_NOT_EXIST'));
        }

        if (isset($this->DBS_ENCODE)) {
            $encodingExists = false;
            $dbEncodes = DatabaseManager::getEncodingList($this->DBS_TYPE);
            foreach ($dbEncodes as $encoding) {
                if (strtolower($encoding[0]) == strtolower($this->DBS_ENCODE)) {
                    $encodingExists = true;
                    break;
                }
            }
            if (!$encodingExists) {
                $this->validationErrors->add('dbs_encode', __('ID_DBC_ENCODE_INVALID'));
            }
        }

        return $this->validationErrors->isEmpty();
    }


    /**
     * Returns true if is a Tns Oracle Database Connection
     *
     * @return bool
     */
    public function isTns()
    {
        return $this->DBS_TYPE === 'oracle'
            && $this->DBS_CONNECTION_TYPE === 'TNS';
    }
}
