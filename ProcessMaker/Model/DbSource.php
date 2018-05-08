<?php
namespace ProcessMaker\Model;

use Illuminate\Database\Eloquent\Model;
use ProcessMaker\Facades\DatabaseManager;
use ProcessMaker\Model\Traits\Uuid;
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
    use Uuid;

    // We do not store timestamps
    public $timestamps = false;

    protected $rules = [
        'type' => 'required',
        'database_name' => 'required',
        'port' => 'required',
        'encode' => 'required'
    ];

    // List of supported engines
    const SUPPORTED_ENGINES = [
        'mysql' => ['id' => 'mysql', 'name' => 'MySql', 'defaultPort' => 3306],
        'pgsql' => ['id' => 'pgsql', 'name' => 'PostgreSql', 'defaultPort' => 5432],
        'sqlsrv' => ['id' => 'sqlsrv', 'name' => 'Microsoft SQL Server', 'defaultPort' => 1433],
        'oracle' => ['id' => 'oracle', 'name' => 'Oracle', 'defaultPort' => 1521]
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
     * This attribute is returned in the json of the API
     *
     * @param $value
     *
     * @return string
     */
    public function getDescriptionAttribute($value)
    {
        return $this->isTns()
            ? '[' . $this->tns . ']' . $value
            : $value;
    }

    /**
     * The server attribute is overwritten when using a tns connection
     *
     * @param $value
     *
     * @return string
     */
    public function getServerAttribute($value)
    {
        $server = $this->isTns()
            ? '[' . $this->tns . ']'
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
    public function getDatabaseNameAttribute($value)
    {
        $databaseName = $this->isTns()
            ? '[' . $this->tns . ']'
            : $value;
        return $databaseName;
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

        if (!in_array($this->type, $typesExists)) {
            $this->validationErrors->add('type', __('ID_DBC_TYPE_INVALID'));
        }

        if (isset($this->server) && $this->server == '' && !$this->isTns()) {
            $this->validationErrors->add('server', __('ID_DBC_SERVER_INVALID'));
        }

        if (isset($this->database_name) && $this->database_name == '' && !$this->isTns()) {
            $this->validationErrors->add('database_name', __('ID_DBC_DBNAME_INVALID'));
        }

        if (isset($this->port) &&
            ($this->port == '' || $this->port == 0)
        ) {
            if (!$this->isTns()) {
                $this->validationErrors->add('port', __('ID_DBC_PORT_INVALID'));
            }
        }

        if (isset($this->tns) && $this->tns == '' && $this->isTns()) {
            $this->validationErrors->add('tns', __('ID_DBC_TNS_NOT_EXIST'));
        }

        if (isset($this->encode)) {
            $encodingExists = false;
            $dbEncodes = DatabaseManager::getEncodingList($this->type);
            foreach ($dbEncodes as $encoding) {
                if (strtolower($encoding[0]) == strtolower($this->encode)) {
                    $encodingExists = true;
                    break;
                }
            }
            if (!$encodingExists) {
                $this->validationErrors->add('encode', __('ID_DBC_ENCODE_INVALID'));
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
        return $this->type === 'oracle'
            && $this->connection_type === 'TNS';
    }

    /**
     * Eloquent relation that return the associated process of the report table
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function process()
    {
        return $this->belongsTo(Process::class);
    }

}
