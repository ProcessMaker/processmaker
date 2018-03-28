<?php
namespace ProcessMaker\Managers;

use DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PDOException;
use ProcessMaker\Exception\DatabaseConnectionFailedException;
use ProcessMaker\Exception\DatabaseConnectionTypeNotSupportedException;
use ProcessMaker\Model\DbSource;

class DatabaseManager
{
    /**
     * Test if the connections specified by the passed parameters exist
     *
     * @param array $connectionParams , connection parameters
     *
     * @return bool
     *
     * @throws DatabaseConnectionFailedException
     * @throws DatabaseConnectionTypeNotSupportedException
     */
    public function testConnection(array $connectionParams)
    {
        //check if the connection type is supported
        if (!array_key_exists($connectionParams['driver'], DbSource::SUPPORTED_ENGINES)) {
            throw new DatabaseConnectionTypeNotSupportedException($connectionParams['driver'] . " is not supported");
        }

        $randomName = Str::random(20);

        config(['database.connections.' . $randomName => [
            'driver' => $connectionParams['driver'],
            'host' => $connectionParams['host'],
            'port' => $connectionParams['port'],
            'database' => $connectionParams['database'],
            'username' => $connectionParams['username'],
            'password' => $connectionParams['password']
        ]]);

        //Test database connection
        try {
            DB::connection($randomName)->getPdo();
        } catch (PDOException $e) {
            throw new DatabaseConnectionFailedException($e->getMessage(), $e->getCode(), $e);
        } finally {
            //as this is just a test, the connection is closed
            DB::disconnect($randomName);
        }

        //if everything was ok, we return true
        return true;
    }

    /**
     * Registers database connections for a particular process so they can be referenced
     * in the DB::connection
     *
     * @param $process The process to lookup
     * @todo Be sure to handle/test Oracle and mssql connections
     */
    public function registerDatabaseConnectionsForProcess($process)
    {
        // Iterate through the db sources for the specified process
        // It's okay if we overwrite existing configurations
        foreach ($process->dbSources as $dbSource) {
            config(['database.connections.' . $dbSource->DBS_UID => [
                'driver' => $dbSource->DBS_TYPE,
                'host' => $dbSource->DBS_SERVER,
                'port' => $dbSource->DBS_PORT,
                'database' => $dbSource->DBS_DATABASE_NAME,
                'username' => $dbSource->DBS_USERNAME,
                'password' => Crypt::decryptString($dbSource->DBS_PASSWORD),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci'
            ]]);
        }
    }

    /**
     * Returns a list of sql encodings we support
     * 
     * @param $engine string The name of the database engine to fetch encodings for
     * @todo Make this an associative array
     * @return array List of encodings
     */
    public function getEncodingList($engine = 'mysql')
    {
        switch ($engine) {
            default:
            case 'mysql':
                $encodes = [['big5','big5 - Big5 Traditional Chinese'
                ],['dec8','dec8 - DEC West European'
                ],['cp850','cp850 - DOS West European'
                ],['hp8','hp8 - HP West European'
                ],['koi8r','koi8r - KOI8-R Relcom Russian'
                ],['latin1','latin1 - cp1252 West European'
                ],['latin2','latin2 - ISO 8859-2 Central European'
                ],['swe7','swe7 - 7bit Swedish'
                ],['ascii','ascii - US ASCII'
                ],['ujis','ujis - EUC-JP Japanese'
                ],['sjis','sjis - Shift-JIS Japanese'
                ],['hebrew','hebrew - ISO 8859-8 Hebrew'
                ],['tis620','tis620 - TIS620 Thai'
                ],['euckr','euckr - EUC-KR Korean'
                ],['koi8u','koi8u - KOI8-U Ukrainian'
                ],['gb2312','gb2312 - GB2312 Simplified Chinese'
                ],['greek','greek - ISO 8859-7 Greek'
                ],['cp1250','cp1250 - Windows Central European'
                ],['gbk','gbk - GBK Simplified Chinese'
                ],['latin5','latin5 - ISO 8859-9 Turkish'
                ],['armscii8','armscii8 - ARMSCII-8 Armenian'
                ],['utf8','utf8 - UTF-8 Unicode'
                ],['ucs2','ucs2 - UCS-2 Unicode'
                ],['cp866','cp866 - DOS Russian'
                ],['keybcs2','keybcs2 - DOS Kamenicky Czech-Slovak'
                ],['macce','macce - Mac Central European'
                ],['macroman','macroman - Mac West European'
                ],['cp852','cp852 - DOS Central European'
                ],['latin7','atin7 - ISO 8859-13 Baltic'
                ],['cp1251','cp1251 - Windows Cyrillic'
                ],['cp1256','cp1256  - Windows Arabic'
                ],['cp1257','cp1257  - Windows Baltic'
                ],['binary','binary  - Binary pseudo charset'
                ],['geostd8','geostd8 - GEOSTD8 Georgian'
                ],['cp932','cp932] - SJIS for Windows Japanese'
                ],['eucjpms','eucjpms - UJIS for Windows Japanese'
                ]
                ];

                break;
            case 'pgsql':
                $encodes = [["BIG5","BIG5"
                ],["EUC_CN","EUC_CN"
                ],["EUC_JP","EUC_JP"
                ],["EUC_KR","EUC_KR"
                ],["EUC_TW","EUC_TW"
                ],["GB18030","GB18030"
                ],["GBK","GBK"
                ],["ISO_8859_5","ISO_8859_5"
                ],["ISO_8859_6","ISO_8859_6"
                ],["ISO_8859_7","ISO_8859_7"
                ],["ISO_8859_8","ISO_8859_8"
                ],["JOHAB","JOHAB"
                ],["KOI8","KOI8"
                ],["selected","LATIN1"
                ],["LATIN2","LATIN2"
                ],["LATIN3","LATIN3"
                ],["LATIN4","LATIN4"
                ],["LATIN5","LATIN5"
                ],["LATIN6","LATIN6"
                ],["LATIN7","LATIN7"
                ],["LATIN8","LATIN8"
                ],["LATIN9","LATIN9"
                ],["LATIN10","LATIN10"
                ],["SJIS","SJIS"
                ],["SQL_ASCII","SQL_ASCII"
                ],["UHC","UHC"
                ],["UTF8","UTF8"
                ],["WIN866","WIN866"
                ],["WIN874","WIN874"
                ],["WIN1250","WIN1250"
                ],["WIN1251","WIN1251"
                ],["WIN1252","WIN1252"
                ],["WIN1256","WIN1256"
                ],["WIN1258","WIN1258"
                ]
                ];
                break;
            case 'mssql':
                $encodes = [['utf8','utf8 - UTF-8 Unicode'
                ]
                ];
                break;
            case 'oracle':
                $encodes = [
                    ["UTF8",      "UTF8 - Unicode 3.0 UTF-8 Universal character set CESU-8 compliant"],
                    ["UTFE",      "UTFE - EBCDIC form of Unicode 3.0 UTF-8 Universal character set"],
                    ["AL16UTF16", "AL16UTF16 - Unicode 3.1 UTF-16 Universal character set"],
                    ["AL32UTF8",  "AL32UTF8 - Unicode 3.1 UTF-8 Universal character set"]
                ];
                break;
        }

        return $encodes;
    }
}
