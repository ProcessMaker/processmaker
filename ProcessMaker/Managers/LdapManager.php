<?php
//    Route::get('ldap/groups', '\ProcessMaker\Http\Controllers\xxxTestController@getGroups')->name('ldap.groups');

namespace ProcessMaker\Managers;


use Illuminate\Http\Request;

class LdapManager
{
    /**
     * The authsource id
     * @var String
     */
    public $sAuthSource = "";

    /**
     * The organizational unit where the removed users are put into
     * @var String
     */
    public $sTerminatedOu = "";

    /**
     * a local variable to store connection with LDAP, and avoid multiple bindings
     * @var String
     */
    public $ldapcnn = null;

    /**
     * The users information array
     * @var Array
     */
    public $aUserInfo = array();

    /**
     * System information
     * @var String
     */
    public $sSystem = "";

    /**
     * Object where an rbac instance is set
     * @var Object
     */
    private static $instance = null;
    private $arrayObjectClassFilter = array(
        "user" => "|(objectclass=inetorgperson)(objectclass=organizationalperson)(objectclass=person)(objectclass=user)",
        "group" => "|(objectclass=posixgroup)(objectclass=group)(objectclass=groupofuniquenames)(objectclass=organizationalunit)",
        "department" => "|(objectclass=organizationalunit)"
    );
    private $arrayAttributes = array(
        "ldap" => array("uid" => "uid", "member" => "memberuid"), //OpenLDAP
        "ad" => array("uid" => "samaccountname", "member" => "member"), //Active Directory
        "ds" => array("uid" => "uid", "member" => "uniquemember") //389 DS
    );

    private $arrayAttributesForUser = array("dn", "uid", "samaccountname", "givenname", "sn", "cn", "mail", "userprincipalname", "useraccountcontrol", "accountexpires", "manager");
    private $frontEnd = false;
    private $debug = false;
    public $arrayAuthenticationSourceUsersByUid = array();
    public $arrayAuthenticationSourceUsersByUsername = array();
    public $arrayDepartmentUsersByUid = array();
    public $arrayDepartmentUsersByUsername = array();
    public $arrayGroupUsersByUid = array();
    public $arrayGroupUsersByUsername = array();
    private $arrayDepartmentUserSynchronizedChecked = array();
    private $arrayUserUpdateChecked = array();

    /**
     * default constructor method
     */
    public function __construct()
    {

    }

    /**
     * Set front end flag
     *
     * @param bool $flag Flag
     *
     * return void
     */
    public function setFrontEnd($flag)
    {
        try {
            $this->frontEnd = $flag;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Set debug
     *
     * @param bool $debug Flag for debug
     *
     * return void
     */
    public function setDebug($debug)
    {
        try {
            $this->debug = $debug;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Set Users that was registered with this Authentication Source
     *
     * @param string $authenticationSourceUid UID of Authentication Source
     *
     * return void
     */
    public function setArrayAuthenticationSourceUsers($authenticationSourceUid)
    {
        try {
            $this->arrayAuthenticationSourceUsersByUid = array();
            $this->arrayAuthenticationSourceUsersByUsername = array();

            //Set data
            $criteria = new Criteria("rbac");

            $criteria->addSelectColumn(RbacUsersPeer::USR_UID);
            $criteria->addSelectColumn(RbacUsersPeer::USR_USERNAME);
            $criteria->addSelectColumn(RbacUsersPeer::USR_AUTH_USER_DN);
            $criteria->add(RbacUsersPeer::UID_AUTH_SOURCE, $authenticationSourceUid, Criteria::EQUAL);
            $criteria->add(RbacUsersPeer::USR_AUTH_TYPE, "ldapadvanced", Criteria::EQUAL);
            //$criteria->add(RbacUsersPeer::USR_STATUS, 1, Criteria::EQUAL);

            $rsCriteria = RbacUsersPeer::doSelectRS($criteria);
            $rsCriteria->setFetchmode(\ResultSet::FETCHMODE_ASSOC);

            while ($rsCriteria->next()) {
                $row = $rsCriteria->getRow();

                $this->arrayAuthenticationSourceUsersByUid[$row["USR_UID"]] = $row;
                $this->arrayAuthenticationSourceUsersByUsername[$row["USR_USERNAME"]] = $row;
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Set User to this Authentication Source
     *
     * @param string $userUid UID of User
     * @param array $arrayUserLdap User LDAP data
     *
     * return void
     */
    public function setArrayAuthenticationSourceUser($userUid, array $arrayUserLdap)
    {
        try {
            $arrayUserData = array(
                "USR_UID" => $userUid,
                "USR_USERNAME" => $arrayUserLdap["sUsername"],
                "USR_AUTH_USER_DN" => $arrayUserLdap["sDN"]
            );

            //Set data
            $this->arrayAuthenticationSourceUsersByUid[$arrayUserData["USR_UID"]] = $arrayUserData;
            $this->arrayAuthenticationSourceUsersByUsername[$arrayUserData["USR_USERNAME"]] = $arrayUserData;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Set Users of the Department
     *
     * @param string $departmentUid UID of Department
     *
     * return void
     */
    public function setArrayDepartmentUsers($departmentUid)
    {
        try {
            $this->arrayDepartmentUsersByUid = array();
            $this->arrayDepartmentUsersByUsername = array();

            //Set data
            $criteria = new Criteria("workflow");

            $criteria->addSelectColumn(UsersPeer::USR_UID);
            $criteria->addSelectColumn(UsersPeer::USR_USERNAME);
            $criteria->addSelectColumn(UsersPeer::USR_REPORTS_TO);
            $criteria->add(UsersPeer::DEP_UID, $departmentUid, Criteria::EQUAL);
            $criteria->add(UsersPeer::USR_STATUS, "CLOSED", Criteria::NOT_EQUAL);

            $rsCriteria = UsersPeer::doSelectRS($criteria);
            $rsCriteria->setFetchmode(\ResultSet::FETCHMODE_ASSOC);

            while ($rsCriteria->next()) {
                $row = $rsCriteria->getRow();

                $this->arrayDepartmentUsersByUid[$row["USR_UID"]] = $row;
                $this->arrayDepartmentUsersByUsername[$row["USR_USERNAME"]] = $row;
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Set Users of the Group
     *
     * @param string $groupUid UID of Group
     *
     * return void
     */
    public function setArrayGroupUsers($groupUid)
    {
        try {
            $this->arrayGroupUsersByUid = array();
            $this->arrayGroupUsersByUsername = array();

            //Set data
            $criteria = new Criteria("workflow");

            $criteria->addSelectColumn(GroupUserPeer::GRP_UID);
            $criteria->addSelectColumn(GroupUserPeer::USR_UID);
            $criteria->addSelectColumn(UsersPeer::USR_USERNAME);
            $criteria->addSelectColumn(UsersPeer::USR_REPORTS_TO);
            $criteria->addJoin(GroupUserPeer::USR_UID, UsersPeer::USR_UID, Criteria::LEFT_JOIN);
            $criteria->add(GroupUserPeer::GRP_UID, $groupUid, Criteria::EQUAL);
            $criteria->add(UsersPeer::USR_STATUS, "CLOSED", Criteria::NOT_EQUAL);

            $rsCriteria = GroupUserPeer::doSelectRS($criteria);
            $rsCriteria->setFetchmode(\ResultSet::FETCHMODE_ASSOC);

            while ($rsCriteria->next()) {
                $row = $rsCriteria->getRow();

                $this->arrayGroupUsersByUid[$row["USR_UID"]] = $row;
                $this->arrayGroupUsersByUsername[$row["USR_USERNAME"]] = $row;
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Set data to array of Users synchronized (Department)
     *
     * @param array $arrayData Data
     *
     * return void
     */
    public function setArrayDepartmentUserSynchronizedChecked(array $arrayData)
    {
        try {
            $this->arrayDepartmentUserSynchronizedChecked = $arrayData;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Set data to array of updated Users
     *
     * @param array $arrayData Data
     *
     * return void
     */
    public function setArrayUserUpdateChecked(array $arrayData)
    {
        try {
            $this->arrayUserUpdateChecked = $arrayData;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * This method gets the singleton Rbac instance.
     * @return Object instance of the rbac class
     */
    public function &getSingleton()
    {
        if (self::$instance == null) {
            self::$instance = new RBAC();
        }

        return self::$instance;
    }

    /**
     * Progress bar
     *
     * @param int $total Total
     * @param int $count Count
     *
     * return string Return a string that represent progress bar
     */
    public function progressBar($total, $count)
    {
        try {
            $p = (int)(($count * 100) / $total);
            $n = (int)($p / 2);

            return "[" . str_repeat("|", $n) . str_repeat(" ", 50 - $n) . "] $p%";
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Show front end
     *
     * @param string $option Option
     * @param string $data Data string
     *
     * return void
     */
    public function frontEndShow($option, $data = "")
    {
        try {
            if (!$this->frontEnd) {
                return;
            }

            $numc = 100;

            switch ($option) {
                case "BAR":
                    echo "\r" . "| " . $data . str_repeat(" ", $numc - 2 - strlen($data));
                    break;
                case "TEXT":
                    echo "\r" . "| " . $data . str_repeat(" ", $numc - 2 - strlen($data)) . "\n";
                    break;
                default:
                    //START, END
                    echo "\r" . "+" . str_repeat("-", $numc - 2) . "+" . "\n";
                    break;
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get valid characteres
     *
     * return array Return an array with valid characteres
     */
    public function characters()
    {
        try {
            $arrayCharacter = array();

            for ($i = 33; $i <= 127; $i++) {
                $char = trim(strtolower(chr($i)));

                if ($char != "") {
                    $arrayCharacter[$i] = $char;
                }
            }

            unset($arrayCharacter[33]);  //!
            unset($arrayCharacter[38]);  //&
            unset($arrayCharacter[40]);  //(
            unset($arrayCharacter[41]);  //)
            unset($arrayCharacter[42]);  //*
            unset($arrayCharacter[60]);  //<
            unset($arrayCharacter[61]);  //=
            unset($arrayCharacter[62]);  //>
            unset($arrayCharacter[124]); //|
            unset($arrayCharacter[126]); //~
            unset($arrayCharacter[127]); //DEL
            //Return
            return array_unique($arrayCharacter);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get User data, if Username was registered with this Authentication Source
     *
     * @param string $username Username
     *
     * return array Return User data, if Username was registered with this Authentication Source; empty data otherwise
     */
    public function authenticationSourceGetUserDataIfUsernameExists($username)
    {
        try {
            if (isset($this->arrayAuthenticationSourceUsersByUsername[$username])) {
                return $this->arrayAuthenticationSourceUsersByUsername[$username];
            }

            return array();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get User data, if Username exists in Department
     *
     * @param string $username Username
     *
     * return array Return User data, if Username exists in Department; empty data otherwise
     */
    public function departmentGetUserDataIfUsernameExists($username)
    {
        try {
            if (isset($this->arrayDepartmentUsersByUsername[$username])) {
                return $this->arrayDepartmentUsersByUsername[$username];
            }

            return array();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get User data, if Username exists in Group
     *
     * @param string $username Username
     *
     * return array Return User data, if Username exists in Group; empty data otherwise
     */
    public function groupGetUserDataIfUsernameExists($username)
    {
        try {
            if (isset($this->arrayGroupUsersByUsername[$username])) {
                return $this->arrayGroupUsersByUsername[$username];
            }

            return array();
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getFieldsForPageSetup()
    {
        return array();
    }

    /**
     * add a line in the ldap log
     *
     * before the log was generated in shared/sites/<site> folder, but it was deprecated
     * and now we are saving the log in  shared/log the entry in the log file.
     *
     * @param Object $_link ldap connection
     * @param String $text
     * @return void
     */
    private function log($obj, $string)
    {
        \Illuminate\Support\Facades\Log::Critical(" var = " . print_r($obj, true) . $string);
    }

    /**
     * Add a debug line in the LDAP log
     *
     * @param string $text Text
     *
     * return void
     */
    public function debugLog($text)
    {
        try {
            if ($this->debug) {
                $this->log(null, "DEBUG: $text");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * This method generates the ldap connection bind and returns the link object
     * for a determined authsource
     *
     * @param Array $aAuthSource the authsource data
     * @return Object A object with the resulting ldap bind
     */
    public function ldapConnection($aAuthSource, &$resultLDAPStartTLS = false)
    {
//        $pass = explode("_", $aAuthSource["AUTH_SOURCE_PASSWORD"]);
//
//        foreach ($pass as $index => $value) {
//            if ($value == "2NnV3ujj3w") {
//                $aAuthSource["AUTH_SOURCE_PASSWORD"] = G::decrypt($pass[0], $aAuthSource["AUTH_SOURCE_SERVER_NAME"]);
//            }
//        }

        $ldapcnn = \ldap_connect($aAuthSource['AUTH_SOURCE_SERVER_NAME'], $aAuthSource['AUTH_SOURCE_PORT']);
        $this->stdLog($ldapcnn, "ldap_connect", $aAuthSource);

        $ldapServer = $aAuthSource["AUTH_SOURCE_SERVER_NAME"] . ":" . $aAuthSource["AUTH_SOURCE_PORT"];

        \ldap_set_option($ldapcnn, LDAP_OPT_PROTOCOL_VERSION, 3);
        $this->stdLog($ldapcnn, "ldap_set_option", $aAuthSource);
        \ldap_set_option($ldapcnn, LDAP_OPT_REFERRALS, 0);
        $this->stdLog($ldapcnn, "ldap_set_option", $aAuthSource);

        if (isset($aAuthSource["AUTH_SOURCE_ENABLED_TLS"]) && $aAuthSource["AUTH_SOURCE_ENABLED_TLS"]) {
            $resultLDAPStartTLS = @ldap_start_tls($ldapcnn);
            $this->stdLog($ldapcnn, "ldap_start_tls", $aAuthSource);
            $ldapServer = "TLS " . $ldapServer;
        }

        if ($aAuthSource["AUTH_ANONYMOUS"] == "1") {
            $bBind = @ldap_bind($ldapcnn);
            $this->log($ldapcnn, "bind $ldapServer like anonymous user");
        } else {
            $bBind = @ldap_bind($ldapcnn, $aAuthSource['AUTH_SOURCE_SEARCH_USER'], $aAuthSource['AUTH_SOURCE_PASSWORD']);
            $this->log($ldapcnn, "bind $ldapServer with user " . $aAuthSource["AUTH_SOURCE_SEARCH_USER"]);
        }
        $this->stdLog($ldapcnn, "ldap_bind", $aAuthSource);
        $this->getDiagnosticMessage($ldapcnn);
        if (!$bBind) {
            throw new \Exception("Unable to bind to server: $ldapServer . " . "LDAP-Errno: " . \ldap_errno($ldapcnn) . " : " . \ldap_error($ldapcnn) . " \n");
        }
        return $ldapcnn;
    }

    /**
     * Get a diagnostic message of the ldap connection status.
     * @param resource $linkIdentifier
     */
    public function getDiagnosticMessage($linkIdentifier)
    {
        //specific message
        $keysError = [
            [
                'key' => 'USER_NOT_FOUND',
                'code' => 525,
                'message' => 'USER_NOT_FOUND'
            ], [
                'key' => 'NOT_PERMITTED_TO_LOGON_AT_THIS_TIME',
                'code' => 530,
                'message' => 'NOT_PERMITTED_TO_LOGON_AT_THIS_TIME',
            ], [
                'key' => 'RESTRICTED_TO_SPECIFIC_MACHINES',
                'code' => 531,
                'message' => 'RESTRICTED_TO_SPECIFIC_MACHINES',
            ], [
                'key' => 'PASSWORD_EXPIRED',
                'code' => 532,
                'message' => 'PASSWORD_EXPIRED',
            ], [
                'key' => 'ACCOUNT_DISABLED',
                'code' => 533,
                'message' => 'ACCOUNT_DISABLED',
            ], [
                'key' => 'ACCOUNT_EXPIRED',
                'code' => 701,
                'message' => 'ACCOUNT_EXPIRED'
            ], [
                'key' => 'USER_MUST_RESET_PASSWORD',
                'code' => 773,
                'message' => 'USER_MUST_RESET_PASSWORD'
            ]
        ];
        $message = '';
        ldap_get_option($linkIdentifier, LDAP_OPT_DIAGNOSTIC_MESSAGE, $messageError);
        $this->stdLog($linkIdentifier, "ldap_get_option", ["error" => $messageError]);
        foreach ($keysError as $key => $value) {
            if (strpos($messageError, (string)$value['code']) !== false) {
                $message = $value['message'];
                break;
            }
        }
        //standard message
        if (empty($message)) {
            $errorNumber = ldap_errno($linkIdentifier);
            $message = ldap_err2str($errorNumber) . ".";
        }
        if (empty($message)) {
            $message = G::LoadTranslation('ID_LDAP_ERROR_CONNECTION');
        }
        $this->log($linkIdentifier, $messageError);
    }

    /**
     * This method obtains the attributes of a ldap Connection passed as parameter
     * @param Object $ldapcnn ldap connection
     *
     * @param Object $oEntry Entry object
     * @return Array attributes
     */
    public function ldapGetAttributes($ldapcnn, $entry)
    {
        try {
            $arrayAttributes = array();

            $arrayAttributes['dn'] = ldap_get_dn($ldapcnn, $entry);
            $this->stdLog($ldapcnn, "ldap_get_dn");

            $arrayAux = ldap_get_attributes($ldapcnn, $entry);
            $this->stdLog($ldapcnn, "ldap_get_attributes");

            for ($i = 0; $i <= $arrayAux["count"] - 1; $i++) {
                $key = strtolower($arrayAux[$i]);

                switch ($arrayAux[$arrayAux[$i]]["count"]) {
                    case 0:
                        $arrayAttributes[$key] = "";
                        break;
                    case 1:
                        $arrayAttributes[$key] = $arrayAux[$arrayAux[$i]][0];
                        break;
                    default:
                        $arrayAttributes[$key] = $arrayAux[$arrayAux[$i]];

                        unset($arrayAttributes[$key]["count"]);
                        break;
                }
            }

            if (!isset($arrayAttributes["mail"]) && isset($arrayAttributes["userprincipalname"])) {
                $arrayAttributes["mail"] = $arrayAttributes["userprincipalname"];
            }

            return $arrayAttributes;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get Users from Department (Search result identifier)
     *
     * @param resource $ldapcnn LDAP link identifier
     * @param resource $searchResult Search result identifier
     * @param string $option Option (GET, SYNCHRONIZE)
     * @param string $dn DN
     * @param string $uidUserIdentifier User identifier
     * @param int $totalUser Total users
     * @param int $countUser User counter
     * @param array $arrayData Data
     *
     * return array Return an array data
     */
    public function ldapGetUsersFromDepartmentSearchResult($ldapcnn, $searchResult, $option, $dn, $uidUserIdentifier, $totalUser, $countUser, array $arrayData)
    {
        try {
            $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromDepartmentSearchResult() > START");

            if ($searchResult) {
                $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromDepartmentSearchResult() > ldap_list > OK");

                $numEntries = ldap_count_entries($ldapcnn, $searchResult);
                $this->stdLog($ldapcnn, "ldap_count_entries");

                $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromDepartmentSearchResult() > ldap_list > OK > \$numEntries ----> $numEntries");

                $totalUser += $numEntries;

                if ($numEntries > 0) {
                    $this->log($ldapcnn, "Search $dn accounts with identifier = $uidUserIdentifier");

                    $entry = ldap_first_entry($ldapcnn, $searchResult);
                    $this->stdLog($ldapcnn, "ldap_first_entry");

                    do {
                        $arrayUserLdap = $this->ldapGetAttributes($ldapcnn, $entry);

                        $username = (isset($arrayUserLdap[$uidUserIdentifier])) ? $arrayUserLdap[$uidUserIdentifier] : "";

                        $countUser++;

                        if ((is_array($username) && !empty($username)) || trim($username) != "") {
                            $arrayUserData = $this->getUserDataFromAttribute($username, $arrayUserLdap);

                            if (!isset($this->arrayDepartmentUserSynchronizedChecked[$arrayUserData["sUsername"]])) {
                                $this->arrayDepartmentUserSynchronizedChecked[$arrayUserData["sUsername"]] = 1;

                                switch ($option) {
                                    case "GET":
                                        $arrayData[] = $arrayUserData;
                                        break;
                                    case "SYNCHRONIZE":
                                        $arrayData = $this->departmentSynchronizeUser("", $arrayUserData, $arrayData);
                                        break;
                                }
                            } else {
                                $this->log($ldapcnn, "User is repeated: Username \"" . $arrayUserData["sUsername"] . "\", DN \"" . $arrayUserData["sDN"] . "\"");
                            }
                        }

                        if ($option == "SYNCHRONIZE") {
                            //Progress bar
                            $this->frontEndShow("BAR", "Departments: " . $arrayData["i"] . "/" . $arrayData["n"] . " " . $this->progressBar($totalUser, $countUser));
                        }
                    } while ($entry = ldap_next_entry($ldapcnn, $entry));
                }
            }

            $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromDepartmentSearchResult() > END");

            //Return
            return array($totalUser, $countUser, $arrayData);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get Users from Department
     *
     * @param string $option Option (GET, SYNCHRONIZE)
     * @param string $dn DN of Department
     * @param array $arrayData Data
     *
     * return array Return an array with data Users or array data
     */
    public function ldapGetUsersFromDepartment($option, $dn, array $arrayData = array())
    {
        try {
            $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromDepartment() > START");
            $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromDepartment() > \$dn ----> $dn");

            $arrayUser = array();
            $totalUser = 0;
            $countUser = 0;

            //Set variables
            $dn = trim($dn);
            $rbac = RBAC::getSingleton();

            if (is_null($rbac->authSourcesObj)) {
                $rbac->authSourcesObj = new AuthenticationSource();
            }

            $arrayAuthenticationSourceData = $rbac->authSourcesObj->load($this->sAuthSource);

            $this->ldapcnn = $this->ldapConnection($arrayAuthenticationSourceData);

            $ldapcnn = $this->ldapcnn;

            //Get Users
            $limit = $this->getPageSizeLimitByData($arrayAuthenticationSourceData);
            $flagError = false;

            if (!isset($arrayAuthenticationSourceData["AUTH_SOURCE_DATA"]["AUTH_SOURCE_USERS_FILTER"])) {
                $arrayAuthenticationSourceData["AUTH_SOURCE_DATA"]["AUTH_SOURCE_USERS_FILTER"] = "";
            }

            $uidUserIdentifier = (isset($arrayAuthenticationSourceData["AUTH_SOURCE_DATA"]["AUTH_SOURCE_IDENTIFIER_FOR_USER"])) ? $arrayAuthenticationSourceData["AUTH_SOURCE_DATA"]["AUTH_SOURCE_IDENTIFIER_FOR_USER"] : "uid";

            $filterUsers = trim($arrayAuthenticationSourceData["AUTH_SOURCE_DATA"]["AUTH_SOURCE_USERS_FILTER"]);

            $filter = ($filterUsers != "") ? $filterUsers : "(" . $this->arrayObjectClassFilter["user"] . ")";

            $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromDepartment() > \$filter ----> $filter");

            $cookie = '';

            do {
                ldap_control_paged_result($ldapcnn, $limit, true, $cookie);
                $this->stdLog($ldapcnn, "ldap_control_paged_result", ["limit" => $limit]);

                $searchResult = @ldap_list($ldapcnn, $dn, $filter, $this->arrayAttributesForUser);
                $this->stdLog($ldapcnn, "ldap_list", ["filter" => $filter, "attributes" => $this->arrayAttributesForUser]);

                if ($error = ldap_errno($ldapcnn)) {
                    $flagError = true;
                } else {
                    $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromDepartment() > ldap_list > OK");

                    switch ($option) {
                        case "GET":
                            list($totalUser, $countUser, $arrayUser) = $this->ldapGetUsersFromDepartmentSearchResult($ldapcnn, $searchResult, $option, $dn, $uidUserIdentifier, $totalUser, $countUser, $arrayUser);
                            break;
                        case "SYNCHRONIZE":
                            list($totalUser, $countUser, $arrayData) = $this->ldapGetUsersFromDepartmentSearchResult($ldapcnn, $searchResult, $option, $dn, $uidUserIdentifier, $totalUser, $countUser, $arrayData);
                            break;
                    }
                }

                if (!$flagError) {
                    ldap_control_paged_result_response($ldapcnn, $searchResult, $cookie);
                    $this->stdLog($ldapcnn, "ldap_control_paged_result_response");
                }
            } while (($cookie !== null && $cookie != '') && !$flagError);

            //Get Users //2
            if ($flagError) {
                $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromDepartment() > Search by characters > START");

                foreach ($this->characters() as $value) {
                    $char = $value;

                    $ldapcnn = $this->ldapConnection($arrayAuthenticationSourceData);

                    $filter = ($filterUsers != "") ? $filterUsers : "(" . $this->arrayObjectClassFilter["user"] . ")";
                    $filter = "(&$filter($uidUserIdentifier=$char*))";

                    $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromDepartment() > \$filter ----> $filter");

                    $searchResult = @ldap_list($ldapcnn, $dn, $filter, $this->arrayAttributesForUser);
                    $this->stdLog($ldapcnn, "ldap_list", ["attributes" => $this->arrayAttributesForUser]);

                    if ($error = ldap_errno($ldapcnn)) {
                        $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromDepartment() > ldap_list > ERROR > \$error ---->\n" . print_r($error, true));
                    } else {
                        $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromDepartment() > ldap_list > OK");

                        switch ($option) {
                            case "GET":
                                list($totalUser, $countUser, $arrayUser) = $this->ldapGetUsersFromDepartmentSearchResult($ldapcnn, $searchResult, $option, $dn, $uidUserIdentifier, $totalUser, $countUser, $arrayUser);
                                break;
                            case "SYNCHRONIZE":
                                list($totalUser, $countUser, $arrayData) = $this->ldapGetUsersFromDepartmentSearchResult($ldapcnn, $searchResult, $option, $dn, $uidUserIdentifier, $totalUser, $countUser, $arrayData);
                                break;
                        }
                    }
                }

                $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromDepartment() > Search by characters > END");
            }

            $this->log($ldapcnn, "Found $totalUser users in department $dn");

            $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromDepartment() > END");

            //Return
            switch ($option) {
                case "GET":
                    return $arrayUser;
                    break;
                case "SYNCHRONIZE":
                    return $arrayData;
                    break;
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Synchronize Group's members
     *
     * @param resource $ldapcnn LDAP link identifier
     * @param array $arrayAuthSourceData Authentication Source Data
     * @param string $groupUid Unique id of Group
     * @param array $arrayGroupLdap LDAP Group
     * @param string $memberAttribute Member attribute
     * @param array $arrayData Data
     *
     * @return array Return array data
     */
    private function ldapGroupSynchronizeMembers($ldapcnn, array $arrayAuthSourceData, $groupUid, array $arrayGroupLdap, $memberAttribute, array $arrayData = [])
    {
        try {
            unset($arrayData['countMembers']);

            //Get members
            if (!isset($arrayAuthSourceData['AUTH_SOURCE_DATA']['AUTH_SOURCE_USERS_FILTER'])) {
                $arrayAuthSourceData['AUTH_SOURCE_DATA']['AUTH_SOURCE_USERS_FILTER'] = '';
            }

            $uidUserIdentifier = (isset($arrayAuthSourceData['AUTH_SOURCE_DATA']['AUTH_SOURCE_IDENTIFIER_FOR_USER'])) ?
                $arrayAuthSourceData['AUTH_SOURCE_DATA']['AUTH_SOURCE_IDENTIFIER_FOR_USER'] : 'uid';

            $filterUsers = trim($arrayAuthSourceData['AUTH_SOURCE_DATA']['AUTH_SOURCE_USERS_FILTER']);

            $filter = ($filterUsers != '') ? $filterUsers : '(' . $this->arrayObjectClassFilter['user'] . ')';

            if (isset($arrayGroupLdap[$memberAttribute])) {
                if (!is_array($arrayGroupLdap[$memberAttribute])) {
                    $arrayGroupLdap[$memberAttribute] = [$arrayGroupLdap[$memberAttribute]];
                }

                $arrayData['countMembers'] = count($arrayGroupLdap[$memberAttribute]);
                $arrayData['totalUser'] += $arrayData['countMembers'];

                //Synchronize members
                foreach ($arrayGroupLdap[$memberAttribute] as $value) {
                    $member = $value; //User DN

                    $searchResult = @ldap_search($ldapcnn, $member, $filter, $this->arrayAttributesForUser);
                    $context = [
                        "baseDN" => $member,
                        "filter" => $filter,
                        "attributes" => $this->arrayAttributesForUser
                    ];
                    $this->stdLog($ldapcnn, "ldap_search", $context);

                    if ($error = ldap_errno($ldapcnn)) {
                        //
                    } else {
                        if ($searchResult) {
                            if (ldap_count_entries($ldapcnn, $searchResult) > 0) {
                                $this->stdLog($ldapcnn, "ldap_count_entries");
                                $entry = ldap_first_entry($ldapcnn, $searchResult);
                                $this->stdLog($ldapcnn, "ldap_first_entry");

                                $arrayUserLdap = $this->ldapGetAttributes($ldapcnn, $entry);

                                $username = (isset($arrayUserLdap[$uidUserIdentifier])) ? $arrayUserLdap[$uidUserIdentifier] : '';

                                $arrayData['countUser']++;

                                if ((is_array($username) && !empty($username)) || trim($username) != '') {
                                    $dataUserLdap = $this->getUserDataFromAttribute($username, $arrayUserLdap);
                                    $dataUserLdap["usrRole"] = "";
                                    if (!empty($arrayAuthSourceData['AUTH_SOURCE_DATA']['USR_ROLE'])) {
                                        $dataUserLdap["usrRole"] = $arrayAuthSourceData['AUTH_SOURCE_DATA']['USR_ROLE'];
                                    }
                                    $arrayData = $this->groupSynchronizeUser(
                                        $groupUid,
                                        $dataUserLdap,
                                        $arrayData
                                    );
                                }

                                //Progress bar
                                $this->frontEndShow(
                                    'BAR',
                                    'Groups: ' . $arrayData['i'] . '/' . $arrayData['n'] . ' ' .
                                    $this->progressBar($arrayData['totalUser'], $arrayData['countUser'])
                                );
                            }
                        }
                    }
                }
            }

            //Return
            return $arrayData;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get Users from Group
     *
     * @param string $option Option (SYNCHRONIZE)
     * @param array $arrayGroupData Group data
     * @param array $arrayData Data
     *
     * return array Return array data
     */
    public function ldapGetUsersFromGroup($option, array $arrayGroupData, array $arrayData = array())
    {
        try {
            $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromGroup() > START");
            $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromGroup() > \$arrayGroupData ---->\n" . print_r($arrayGroupData, true));

            $totalUser = 0;
            $countUser = 0;

            //Set variables
            $dn = trim($arrayGroupData["GRP_LDAP_DN"]);
            $rbac = RBAC::getSingleton();

            if (is_null($rbac->authSourcesObj)) {
                $rbac->authSourcesObj = new AuthenticationSource();
            }

            $arrayAuthenticationSourceData = $rbac->authSourcesObj->load($this->sAuthSource);

            $this->ldapcnn = $this->ldapConnection($arrayAuthenticationSourceData);

            $ldapcnn = $this->ldapcnn;

            //Get Group members
            $memberAttribute = $this->arrayAttributes[$arrayAuthenticationSourceData["AUTH_SOURCE_DATA"]["LDAP_TYPE"]]["member"];

            $filter = "(" . $this->arrayObjectClassFilter["group"] . ")";

            $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromGroup() > \$filter ----> $filter");

            $searchResult = @ldap_search($ldapcnn, $dn, $filter, array($memberAttribute));
            $context = [
                "baseDN" => $dn,
                "filter" => $filter,
                "attributes" => [$memberAttribute]
            ];
            $this->stdLog($ldapcnn, "ldap_search", $context);

            if ($error = ldap_errno($ldapcnn)) {
                $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromGroup() > ldap_search > ERROR > \$error ---->\n" . print_r($error, true));
            } else {
                $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromGroup() > ldap_search > OK1");

                if ($searchResult) {
                    $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromGroup() > ldap_search > OK2");

                    $numEntries = ldap_count_entries($ldapcnn, $searchResult);
                    $this->stdLog($ldapcnn, "ldap_count_entries");

                    $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromGroup() > ldap_search > OK2 > \$numEntries ----> $numEntries");

                    if ($numEntries > 0) {
                        $entry = ldap_first_entry($ldapcnn, $searchResult);
                        $this->stdLog($ldapcnn, "ldap_first_entry");

                        $arrayGroupLdap = $this->ldapGetAttributes($ldapcnn, $entry);

                        //Syncronize members
                        $flagMemberRange = false;

                        $memberAttribute2 = $memberAttribute;

                        if (isset($arrayGroupLdap[$memberAttribute]) && empty($arrayGroupLdap[$memberAttribute])) {
                            foreach ($arrayGroupLdap as $key => $value) {
                                if (preg_match('/^member;range=\d+\-\d+$/i', $key)) {
                                    $memberAttribute2 = $key;

                                    $flagMemberRange = true;
                                    break;
                                }
                            }
                        }

                        $arrayData = $this->ldapGroupSynchronizeMembers(
                            $ldapcnn,
                            $arrayAuthenticationSourceData,
                            $arrayGroupData['GRP_UID'],
                            $arrayGroupLdap,
                            $memberAttribute2,
                            array_merge($arrayData, ['totalUser' => $totalUser, 'countUser' => $countUser])
                        );

                        $totalUser = $arrayData['totalUser'];
                        $countUser = $arrayData['countUser'];

                        $limitMemberRange = (isset($arrayData['countMembers'])) ? $arrayData['countMembers'] : 0;

                        if ($flagMemberRange) {
                            for ($start = $limitMemberRange; true; $start += $limitMemberRange) {
                                $end = $start + $limitMemberRange - 1;

                                $memberAttribute2 = $memberAttribute . ';range=' . $start . '-' . $end;

                                $searchResult = @ldap_search($ldapcnn, $dn, $filter, [$memberAttribute2]);
                                $context = [
                                    "baseDN" => $dn,
                                    "filter" => $filter,
                                    "attributes" => [$memberAttribute2]
                                ];
                                $this->stdLog($ldapcnn, "ldap_search", $context);

                                if ($error = ldap_errno($ldapcnn)) {
                                    break;
                                } else {
                                    if ($searchResult) {
                                        if (ldap_count_entries($ldapcnn, $searchResult) > 0) {
                                            $this->stdLog($ldapcnn, "ldap_count_entries");
                                            $entry = ldap_first_entry($ldapcnn, $searchResult);
                                            $this->stdLog($ldapcnn, "ldap_first_entry");

                                            $arrayGroupLdap = $this->ldapGetAttributes($ldapcnn, $entry);

                                            foreach ($arrayGroupLdap as $key => $value) {
                                                if (preg_match('/^member;range=\d+\-\*$/i', $key)) {
                                                    $memberAttribute2 = $key;
                                                    break;
                                                }
                                            }

                                            $arrayData = $this->ldapGroupSynchronizeMembers(
                                                $ldapcnn,
                                                $arrayAuthenticationSourceData,
                                                $arrayGroupData['GRP_UID'],
                                                $arrayGroupLdap,
                                                $memberAttribute2,
                                                array_merge($arrayData, ['totalUser' => $totalUser, 'countUser' => $countUser])
                                            );

                                            $totalUser = $arrayData['totalUser'];
                                            $countUser = $arrayData['countUser'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->log($ldapcnn, "Found $totalUser users in group $dn");

            $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromGroup() > END");

            //Return
            return $arrayData;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * This method authentifies if a user has the RBAC_user privileges
     * also verifies if the user has the rights to start an application
     *
     *
     * @access public
     * @param string $strUser UserId  (user login)
     * @param string $strPass Password
     * @return
     *  -1: user doesn"t exists / no existe usuario
     *  -2: wrong password / password errado
     *  -3: inactive user / usuario inactivo
     *  -4: user due date / usuario vencido
     *  -5: connection error
     *  n : user uid / uid de usuario
     */
    public function VerifyLogin($strUser, $strPass)
    {
        if (is_array($strUser)) {
            $strUser = $strUser[0];
        } else {
            $strUser = trim($strUser);
        }

        if ($strUser == "") {
            return -1;
        }

        if (strlen($strPass) == 0) {
            return -2;
        }

        $ldapcnn = null;

        $validUserPass = 1;

        try {
            $rbac = RBAC::getSingleton();

            if (is_null($rbac->authSourcesObj)) {
                $rbac->authSourcesObj = new AuthenticationSource();
            }

            if ($rbac->userObj == null) {
                $rbac->userObj = new RbacUsers();
            }

            $arrayAuthSource = $rbac->authSourcesObj->load($this->sAuthSource);

            $setAttributes = 0;

            if (isset($arrayAuthSource['AUTH_SOURCE_DATA']['AUTH_SOURCE_SHOWGRID']) &&
                $arrayAuthSource['AUTH_SOURCE_DATA']['AUTH_SOURCE_SHOWGRID'] == 'on'
            ) {
                $setAttributes = 1;
            }

            //Get UserName
            $criteria = new Criteria("rbac");

            $criteria->addSelectColumn(RbacUsersPeer::USR_USERNAME);
            $criteria->addSelectColumn(RbacUsersPeer::USR_UID);
            $criteria->add(RbacUsersPeer::UID_AUTH_SOURCE, $arrayAuthSource["AUTH_SOURCE_UID"]);
            $criteria->add(RbacUsersPeer::USR_AUTH_USER_DN, $strUser);
            $criteria->add(RbacUsersPeer::USR_USERNAME, "", Criteria::NOT_EQUAL);

            $rsCriteria = RbacUsersPeer::doSelectRS($criteria);
            $rsCriteria->setFetchmode(ResultSet::FETCHMODE_ASSOC);

            $rsCriteria->next();
            $row = $rsCriteria->getRow();

            $usrName = $row["USR_USERNAME"];
            $usrUid = $row["USR_UID"];

            $userDn = $strUser;

            //Get the AuthSource properties
            //Check if the dn in the database record matches with the dn for the ldap account
            try {
                $verifiedUser = $this->searchUserByUid($usrName, $arrayAuthSource["AUTH_SOURCE_DATA"]["AUTH_SOURCE_IDENTIFIER_FOR_USER"]);

                if (empty($verifiedUser) || trim($verifiedUser["sDN"]) == null) {
                    return -1;
                }
                if ($verifiedUser["sDN"] != $strUser || $setAttributes == 1) {
                    $userDn = $verifiedUser['sDN'];

                    //Update data
                    $user = new User();
                    $arrayUserData = $user->getUserRecordByPk($usrUid, [], false);

                    $result = $this->ldapUserUpdateByDnAndData(
                        $this->ldapcnn,
                        $arrayAuthSource,
                        $userDn,
                        [$arrayUserData['USR_USERNAME'] => $arrayUserData]
                    );

                    //Update DN
                    $con = Propel::getConnection(RbacUsersPeer::DATABASE_NAME);
                    // select set
                    $c1 = new Criteria("rbac");
                    $c1->add(RbacUsersPeer::UID_AUTH_SOURCE, $arrayAuthSource["AUTH_SOURCE_UID"]);
                    $c1->add(RbacUsersPeer::USR_AUTH_USER_DN, $strUser);
                    // update set
                    $c2 = new Criteria("rbac");
                    $c2->add(RbacUsersPeer::USR_AUTH_USER_DN, $userDn);

                    BasePeer::doUpdate($c1, $c2, $con);
                }
            } catch (Exception $e) {
                $context = [
                    "action" => "ldapSynchronize",
                    "authSource" => $arrayAuthSource
                ];
                $message = $e->getMessage();
                Log::channel(':ldapSynchronize')->error($message, Bootstrap::context($context));
            }

            //Check ldap connection for user
            $arrayAuthSource["AUTH_ANONYMOUS"] = "0";
            $arrayAuthSource["AUTH_SOURCE_SEARCH_USER"] = $userDn;
            $arrayAuthSource["AUTH_SOURCE_PASSWORD"] = $strPass;

            $ldapcnn = $this->ldapConnection($arrayAuthSource);
            $flagUpdate = false;
            switch (hexdec(ldap_errno($ldapcnn))) {
                case 0:
                    //0x00
                    $flagUpdate = true;
                    $statusRbac = 1;
                    $statusUser = 'ACTIVE';
                    break;
                case 52:
                case 88:
                case 94:
                    //0x34, 0x58, 0x5e
                    //LDAP_UNAVAILABLE
                    //LDAP_USER_CANCELLED
                    //LDAP_NO_RESULTS_RETURNED
                    $flagUpdate = true;
                    $statusRbac = 0;
                    $statusUser = 'INACTIVE';
                    break;
                default:
                    break;
            }
            if ($flagUpdate) {
                $con = Propel::getConnection(RbacUsersPeer::DATABASE_NAME);
                // select set
                $c1 = new Criteria("rbac");
                $c1->add(RbacUsersPeer::UID_AUTH_SOURCE, $arrayAuthSource["AUTH_SOURCE_UID"]);
                $c1->add(RbacUsersPeer::USR_AUTH_USER_DN, $strUser);
                $c1->add(RbacUsersPeer::USR_STATUS, 1);
                // update set
                $c2 = new Criteria("rbac");
                $c2->add(RbacUsersPeer::USR_AUTH_USER_DN, $userDn);
                $c2->add(RbacUsersPeer::USR_STATUS, $statusRbac);
                BasePeer::doUpdate($c1, $c2, $con);
                $columnsWf = array();
                $columnsWf['USR_UID'] = $usrUid;
                $columnsWf['USR_STATUS'] = $statusUser;
                $oUser = new Users();
                $oUser->update($columnsWf);
            }

            $attributes = $arrayAuthSource["AUTH_SOURCE_DATA"];

            if (!isset($attributes['AUTH_SOURCE_RETIRED_OU'])) {
                $attributes ['AUTH_SOURCE_RETIRED_OU'] = '';
            }

            //Check if the user is in the terminated organizational unit
            if (!empty($verifiedUser) && $this->userIsTerminated($usrName, $attributes["AUTH_SOURCE_RETIRED_OU"])) {
                $this->deactivateUser($usrName);
                $this->log($ldapcnn, "user $strUser is member of Remove OU, deactivating this user.");

                return -3;
            }
            $validUserPass = ldap_errno($ldapcnn) == 0;
        } catch (Exception $e) {
            $validUserPass = -5;
        }

        if ($validUserPass == 1) {
            $this->log($ldapcnn, "sucessful login user " . $userDn);
        } else {
            $this->log($ldapcnn, "failure authentication for user $strUser");
        }

        return $validUserPass;
    }

    /**
     * Get data of a User from attribute
     *
     * @param mixed $username Username
     * @param array $arrayAttributes Attributes
     *
     * return array Return an array with data User
     */
    public function getUserDataFromAttribute($username, array $arrayAttributes)
    {
        try {
            $keyMail = (isset($arrayAttributes["mail"])) ? "mail" : ((isset($arrayAttributes["userprincipalname"])) ? "userprincipalname" : "nomail");

            return array(
                "sUsername" => trim((is_array($username)) ? $username[0] : $username),
                "sPassword" => trim((isset($arrayAttributes["userpassword"])) ? ((is_array($arrayAttributes["userpassword"])) ? $arrayAttributes["userpassword"][0] : $arrayAttributes["userpassword"]) : ""),
                "sFullname" => trim((isset($arrayAttributes["cn"])) ? ((is_array($arrayAttributes["cn"])) ? $arrayAttributes["cn"][0] : $arrayAttributes["cn"]) : ""),
                "sFirstname" => trim((isset($arrayAttributes["givenname"])) ? ((is_array($arrayAttributes["givenname"])) ? $arrayAttributes["givenname"][0] : $arrayAttributes["givenname"]) : ""),
                "sLastname" => trim((isset($arrayAttributes["sn"])) ? ((is_array($arrayAttributes["sn"])) ? $arrayAttributes["sn"][0] : $arrayAttributes["sn"]) : ""),
                "sEmail" => trim((isset($arrayAttributes[$keyMail])) ? ((is_array($arrayAttributes[$keyMail])) ? $arrayAttributes[$keyMail][0] : $arrayAttributes[$keyMail]) : ""),
                "sDN" => trim($arrayAttributes["dn"]),
                "sManagerDN" => trim((isset($arrayAttributes["manager"])) ? ((is_array($arrayAttributes["manager"])) ? $arrayAttributes["manager"][0] : $arrayAttributes["manager"]) : "")
            );
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * This method searches for the users that has some attribute
     * that matches the keyword.
     * @param String $keyword search criteria
     * @return array Users that match the search criteria
     */
    public function searchUsers($keyword, $start = null, $limit = null, $settings)
    {
        $arrayUser = array();
        $totalUser = 0;
        $countUser = 0;
//
        $paged = !is_null($start) && !is_null($limit);
//
//        $rbac = RBAC::getSingleton();
//
//        if (is_null($rbac->authSourcesObj)) {
//            $rbac->authSourcesObj = new AuthenticationSource();
//        }

//        $arrayAuthenticationSourceData = $rbac->authSourcesObj->load($this->sAuthSource);

        $arrayAuthenticationSourceData = $this->loadLdapSettings($settings);
        $ldapcnn = $this->ldapConnection($arrayAuthenticationSourceData);

        $attributeUserSet = array();
        $attributeSetAdd = array();

        if (isset($arrayAuthenticationSourceData["AUTH_SOURCE_GRID_ATTRIBUTE"]) && !empty($arrayAuthenticationSourceData["AUTH_SOURCE_GRID_ATTRIBUTE"])
        ) {
            foreach ($arrayAuthenticationSourceData["AUTH_SOURCE_GRID_ATTRIBUTE"] as $value) {
                $attributeSetAdd[] = $value['attributeLdap'];
                $attributeUserSet[$value['attributeUser']] = $value['attributeLdap'];
            }
        }

//        if (is_null($this->ldapcnn)) {
//            $this->ldapcnn = $this->ldapConnection($arrayAuthenticationSourceData);
//        }
//
//        $ldapcnn = $this->ldapcnn;

        //Get Users
        if (!isset($arrayAuthenticationSourceData["AUTH_SOURCE_USERS_FILTER"])) {
            $arrayAuthenticationSourceData["AUTH_SOURCE_USERS_FILTER"] = "";
        }

        $uidUserIdentifier = (isset($arrayAuthenticationSourceData["AUTH_SOURCE_IDENTIFIER_FOR_USER"])) ? $arrayAuthenticationSourceData["AUTH_SOURCE_IDENTIFIER_FOR_USER"] : "uid";

        $filterUsers = trim($arrayAuthenticationSourceData["AUTH_SOURCE_USERS_FILTER"]);

        $filter = ($filterUsers != "") ? $filterUsers : "(" . $this->arrayObjectClassFilter["user"] . ")";
        $filter = "(&$filter(|(dn=$keyword)(uid=$keyword)(samaccountname=$keyword)(givenname=$keyword)(sn=$keyword)(cn=$keyword)(mail=$keyword)(userprincipalname=$keyword)))";

        $oSearch = @ldap_search($ldapcnn, $arrayAuthenticationSourceData["AUTH_SOURCE_BASE_DN"], $filter, array_merge($this->arrayAttributesForUser, $attributeSetAdd));
        $context = [
            "baseDN" => $arrayAuthenticationSourceData["AUTH_SOURCE_BASE_DN"],
            "filter" => $filter,
            "attribute" => array_merge($this->arrayAttributesForUser, $attributeSetAdd)
        ];
        $this->stdLog($ldapcnn, "ldap_search", $context);

        if ($oError = ldap_errno($ldapcnn)) {
            $this->log($ldapcnn, "Error in Search users");
        } else {
            if ($oSearch) {
                $entries = ldap_count_entries($ldapcnn, $oSearch);
                $this->stdLog($ldapcnn, "ldap_count_entries");
                $totalUser = $entries;

                if ($entries > 0) {
                    $oEntry = ldap_first_entry($ldapcnn, $oSearch);
                    $this->stdLog($ldapcnn, "ldap_first_entry");

                    $countEntries = 0;

                    $flagNextRecord = true;

                    do {
                        $aAttr = $this->ldapGetAttributes($ldapcnn, $oEntry);
                        $sUsername = (isset($aAttr[$uidUserIdentifier])) ? $aAttr[$uidUserIdentifier] : "";

                        if ((is_array($sUsername) && !empty($sUsername)) || trim($sUsername) != "") {
                            $countUser++;

                            /* Active Directory userAccountControl Values
                              Normal Day to Day Values:
                              512 - Enable Account
                              514 - Disable account
                              544 - Account Enabled - Require user to change password at first logon
                              4096 - Workstation/server
                              66048 - Enabled, password never expires
                              66050 - Disabled, password never expires
                              262656 - Smart Card Logon Required
                              532480 - Domain controller
                              1 - script
                              2 - accountdisable
                              8 - homedir_required
                              16 - lockout
                              32 - passwd_notreqd
                              64 - passwd_cant_change
                              128 - encrypted_text_pwd_allowed
                              256 - temp_duplicate_account
                              512 - normal_account
                              2048 - interdomain_trust_account
                              4096 - workstation_trust_account
                              8192 - server_trust_account
                              65536 - dont_expire_password
                              131072 - mns_logon_account
                              262144 - smartcard_required
                              524288 - trusted_for_delegation
                              1048576 - not_delegated
                              2097152 - use_des_key_only
                              4194304 - dont_req_preauth
                              8388608 - password_expired
                              16777216 - trusted_to_auth_for_delegation
                             */
                            $userCountControl = '';
                            //Active Directory, openLdap
                            if (isset($aAttr['useraccountcontrol'])) {
                                switch ($aAttr['useraccountcontrol']) {
                                    case '512':
                                    case '544':
                                    case '66048':
                                    case '66080':
                                        $userCountControl = 'ACTIVE';
                                        break;
                                    case '514':
                                    case '546':
                                    case '66050':
                                    case '66082':
                                    case '2':
                                    case '16':
                                    case '8388608':
                                    default:
                                        $userCountControl = 'INACTIVE';
                                        break;
                                }
                            }
                            //apache ldap
                            if (isset($aAttr['status'])) {
                                $userCountControl = strtoupper($aAttr['status']);
                            }
                            $aUserAttributes = array();
                            foreach ($attributeUserSet as $key => $value) {
                                if ($key == 'USR_STATUS') {
                                    $aUserAttributes[$key] = ($userCountControl != '') ? $userCountControl : 'ACTIVE';
                                } elseif (isset($aAttr[$value])) {
                                    $aUserAttributes[$key] = $aAttr[$value];
                                }
                            }

                            if ($paged) {
                                if ($countUser - 1 <= $start + $limit - 1) {
                                    if ($start <= $countUser - 1) {
                                        $arrayUser[] = array_merge($this->getUserDataFromAttribute($sUsername, $aAttr), $aUserAttributes);
                                    }
                                } else {
                                    $flagNextRecord = false;
                                }
                            } else {
                                $arrayUser[] = array_merge($this->getUserDataFromAttribute($sUsername, $aAttr), $aUserAttributes);
                            }

                            $countEntries++;
                        }
                    } while (($oEntry = ldap_next_entry($ldapcnn, $oEntry)) && $flagNextRecord);
                }
            }
        }
        return ($paged) ? array("numRecTotal" => $totalUser, "data" => $arrayUser) : $arrayUser;
    }

    function transformLdapEntry( $entry ) {
        $retEntry = array();
        for ( $i = 0; $i < $entry['count']; $i++ ) {
            $attribute = $entry[$i];
            if ( $entry[$attribute]['count'] == 1 ) {
                $retEntry[$attribute] = $entry[$attribute][0];
            } else {
                for ( $j = 0; $j < $entry[$attribute]['count']; $j++ ) {
                    $retEntry[$attribute][] = $entry[$attribute][$j];
                }
            }
        }
        return $retEntry;
    }

    public function searchUsersByGroup($settings, array $arrayGroupData)
    {
        try {
            $arrayData = array();
            $arrayAuthenticationSourceData = $this->loadLdapSettings($settings);
            $ldapcnn = $this->ldapConnection($arrayAuthenticationSourceData);

            $totalUser = 0;
            $countUser = 0;

            //Set variables
            $dn = trim($arrayGroupData["GRP_LDAP_DN"]);

            //Get Group members
            $memberAttribute = $this->arrayAttributes[$arrayAuthenticationSourceData["LDAP_TYPE"]]["member"];

            $filter = "(" . $this->arrayObjectClassFilter["user"] . ")";

            $this->debugLog("class.ldapAdvanced.php > function ldapGetUsersFromGroup() > \$filter ----> $filter");

            $searchResult = ldap_search($ldapcnn, $dn, $filter, []);
            $entries = ldap_get_entries($ldapcnn, $searchResult);
            $users = [];
            foreach ($entries as $entry) {
                $user = $this->transformLdapEntry($entry);
                if (!empty($user)) {
                    $users[] = $user;
                }
            }
            return $users;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * This method search in the ldap/active directory source for an user using the UID, (samaccountname or uid )
     * the value should be in $aAuthSource['AUTH_SOURCE_DATA']['AUTH_SOURCE_IDENTIFIER_FOR_USER']
     * @param String $keyword The keyword in order to match the record with the identifier attribute
     * @param String $identifier id identifier, this parameter is optional
     * @return mixed if the user has been found or not
     */
    public function searchUserByUid($keyword, $identifier = "")
    {
        try {
            $arrayUserData = array();

            //Set variables
            $rbac = RBAC::getSingleton();

            if (is_null($rbac->authSourcesObj)) {
                $rbac->authSourcesObj = new AuthenticationSource();
            }

            $arrayAuthenticationSourceData = $rbac->authSourcesObj->load($this->sAuthSource);

            if (is_null($this->ldapcnn)) {
                $this->ldapcnn = $this->ldapConnection($arrayAuthenticationSourceData);
            }

            $ldapcnn = $this->ldapcnn;

            //Get User
            $attributeUserSet = array();
            $attributeSetAdd = array();

            if (isset($arrayAuthenticationSourceData["AUTH_SOURCE_DATA"]["AUTH_SOURCE_GRID_ATTRIBUTE"]) && !empty($arrayAuthenticationSourceData["AUTH_SOURCE_DATA"]["AUTH_SOURCE_GRID_ATTRIBUTE"])
            ) {
                foreach ($arrayAuthenticationSourceData["AUTH_SOURCE_DATA"]["AUTH_SOURCE_GRID_ATTRIBUTE"] as $value) {
                    $attributeSetAdd[] = $value["attributeLdap"];
                    $attributeUserSet[$value["attributeUser"]] = $value["attributeLdap"];
                }
            }

            $uidUserIdentifier = (isset($arrayAuthenticationSourceData["AUTH_SOURCE_DATA"]["AUTH_SOURCE_IDENTIFIER_FOR_USER"])) ? $arrayAuthenticationSourceData["AUTH_SOURCE_DATA"]["AUTH_SOURCE_IDENTIFIER_FOR_USER"] : "uid";

            $filter2 = "";

            if ($identifier != "" && $identifier != $uidUserIdentifier) {
                $filter2 = "($identifier=$keyword)";
            }

            $filter = "(&(" . $this->arrayObjectClassFilter["user"] . ")(|($uidUserIdentifier=$keyword)$filter2))";

            $searchResult = @ldap_search($ldapcnn, $arrayAuthenticationSourceData["AUTH_SOURCE_BASE_DN"], $filter, array_merge($this->arrayAttributesForUser, $attributeSetAdd));
            $context = [
                "baseDN" => $arrayAuthenticationSourceData["AUTH_SOURCE_BASE_DN"],
                "filter" => $filter,
                "attribute" => array_merge($this->arrayAttributesForUser, $attributeSetAdd)
            ];
            $this->stdLog($ldapcnn, "ldap_search", $context);

            if ($error = ldap_errno($ldapcnn)) {
                $messageError = ldap_err2str($error);
                Cache::put('ldapMessageError', $messageError, 2);
                //
            } else {
                if ($searchResult) {
                    $numEntries = ldap_count_entries($ldapcnn, $searchResult);
                    $this->stdLog($ldapcnn, "ldap_count_entries");

                    if ($numEntries > 0) {
                        $entry = ldap_first_entry($ldapcnn, $searchResult);
                        $this->stdLog($ldapcnn, "ldap_first_entry");

                        $arrayUserLdap = $this->ldapGetAttributes($ldapcnn, $entry);

                        $username = (isset($arrayUserLdap[$uidUserIdentifier])) ? $arrayUserLdap[$uidUserIdentifier] : "";

                        if ((is_array($username) && !empty($username)) || trim($username) != "") {
                            $userCountControl = "";

                            //Active Directory, OpenLDAP
                            if (isset($arrayUserLdap["useraccountcontrol"])) {
                                switch ($arrayUserLdap["useraccountcontrol"]) {
                                    case "512":
                                    case "544":
                                    case "66048":
                                    case "66080":
                                        $userCountControl = "ACTIVE";
                                        break;
                                    case "514":
                                    case "546":
                                    case "66050":
                                    case "66082":
                                    case "2":
                                    case "16":
                                    case "8388608":
                                    default:
                                        $userCountControl = "INACTIVE";
                                        break;
                                }
                            }

                            //Apache LDAP
                            if (isset($arrayUserLdap["status"])) {
                                $userCountControl = strtoupper($arrayUserLdap["status"]);
                            }

                            $aUserAttributes = array();

                            foreach ($attributeUserSet as $key => $value) {
                                if ($key == "USR_STATUS") {
                                    $aUserAttributes[$key] = ($userCountControl != "") ? $userCountControl : "ACTIVE";
                                } else {
                                    if (isset($arrayUserLdap[$value])) {
                                        $aUserAttributes[$key] = $arrayUserLdap[$value];
                                    }
                                }
                            }

                            $arrayUserData = array_merge($this->getUserDataFromAttribute($username, $arrayUserLdap), $aUserAttributes);
                        }
                    }
                }
            }

            return $arrayUserData;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Automatic register.
     * @param array $authSource
     * @param string $strUser
     * @param string $strPass
     * @return bool
     */
    public function automaticRegister($authSource, $strUser, $strPass)
    {
        $rbac = RBAC::getSingleton();

        if ($rbac->userObj == null) {
            $rbac->userObj = new RbacUsers();
        }

        if ($rbac->rolesObj == null) {
            $rbac->rolesObj = new Roles();
        }

        $user = $this->searchUserByUid($strUser);

        $result = 0;

        if (!empty($user)) {
            if ($this->VerifyLogin($user['sUsername'], $strPass) === true) {
                $result = 1;
            }

            if ($result == 0 && $this->VerifyLogin($user['sDN'], $strPass) === true) {
                $result = 1;
            }
        } else {
            return $result;
        }

        if ($result == 0) {
            $authSource = $rbac->authSourcesObj->load($this->sAuthSource);
            $attributes = [];

            if (isset($authSource['AUTH_SOURCE_DATA']['AUTH_SOURCE_GRID_ATTRIBUTE'])) {
                $attributes = $authSource['AUTH_SOURCE_DATA']['AUTH_SOURCE_GRID_ATTRIBUTE'];
            }

            $usrRole = 'PROCESSMAKER_OPERATOR';
            if (!empty($authSource['AUTH_SOURCE_DATA']['USR_ROLE'])) {
                $usrRole = $authSource['AUTH_SOURCE_DATA']['USR_ROLE'];
            }
            $data = [];
            $data['USR_USERNAME'] = $user['sUsername'];
            $data["USR_PASSWORD"] = "00000000000000000000000000000000";
            $data['USR_FIRSTNAME'] = $user['sFirstname'];
            $data['USR_LASTNAME'] = $user['sLastname'];
            $data['USR_EMAIL'] = $user['sEmail'];
            $data['USR_DUE_DATE'] = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d'), date('Y') + 2));
            $data['USR_CREATE_DATE'] = date('Y-m-d H:i:s');
            $data['USR_UPDATE_DATE'] = date('Y-m-d H:i:s');
            $data['USR_BIRTHDAY'] = date('Y-m-d');
            $data['USR_STATUS'] = (isset($user['USR_STATUS'])) ? (($user['USR_STATUS'] == 'ACTIVE') ? 1 : 0) : 1;
            $data['USR_AUTH_TYPE'] = strtolower($authSource['AUTH_SOURCE_PROVIDER']);
            $data['UID_AUTH_SOURCE'] = $authSource['AUTH_SOURCE_UID'];
            $data['USR_AUTH_USER_DN'] = $user['sDN'];
            $data['USR_ROLE'] = $usrRole;

            if (!empty($attributes)) {
                foreach ($attributes as $value) {
                    if (isset($user[$value['attributeUser']])) {
                        $data[$value['attributeUser']] = str_replace("*", "'", $user[$value['attributeUser']]);
                        if ($value['attributeUser'] == 'USR_STATUS') {
                            $evalValue = $data[$value['attributeUser']];
                            $statusValue = (isset($user['USR_STATUS'])) ? $user['USR_STATUS'] : 'ACTIVE';
                            $data[$value['attributeUser']] = $statusValue;
                        }
                    }
                }
            }

            //req - accountexpires
            if (isset($user["USR_DUE_DATE"]) && $user["USR_DUE_DATE"] != '') {
                $data["USR_DUE_DATE"] = $this->convertDateADtoPM($user["USR_DUE_DATE"]);
            }
            //end

            $userUid = $rbac->createUser($data, $usrRole);
            $data['USR_UID'] = $userUid;

            require_once 'classes/model/Users.php';

            $users = new Users();
            $data['USR_STATUS'] = (isset($user['USR_STATUS'])) ? $user['USR_STATUS'] : 'ACTIVE';
            $users->create($data);
            $this->log(null, "Automatic Register for user $strUser ");
            $result = 1;
        }

        return $result;
    }

    /**
     * Get a deparment list
     * @return <type>
     */
    public function searchDepartments()
    {
        try {
            $arrayDepartment = [];

            //Set variables
            $rbac = RBAC::getSingleton();

            if (is_null($rbac->authSourcesObj)) {
                $rbac->authSourcesObj = new AuthenticationSource();
            }

            $arrayAuthenticationSourceData = $rbac->authSourcesObj->load($this->sAuthSource);

            if (is_null($this->ldapcnn)) {
                $this->ldapcnn = $this->ldapConnection($arrayAuthenticationSourceData);
            }

            $ldapcnn = $this->ldapcnn;

            //Get Departments
            $limit = $this->getPageSizeLimitByData($arrayAuthenticationSourceData);
            $flagError = false;

            $filter = '(' . $this->arrayObjectClassFilter['department'] . ')';

            $this->log($ldapcnn, 'search Departments with Filter: ' . $filter);

            $unitsBase = $this->custom_ldap_explode_dn($arrayAuthenticationSourceData['AUTH_SOURCE_BASE_DN']);

            $cookie = '';

            do {
                ldap_control_paged_result($ldapcnn, $limit, true, $cookie);
                $this->stdLog($ldapcnn, "ldap_control_paged_result", ["pageSize" => $limit, "isCritical" => true]);

                $searchResult = @ldap_search($ldapcnn, $arrayAuthenticationSourceData['AUTH_SOURCE_BASE_DN'], $filter, ['dn', 'ou']);
                $context = [
                    "baseDN" => $arrayAuthenticationSourceData['AUTH_SOURCE_BASE_DN'],
                    "filter" => $filter,
                    "attributes" => ['dn', 'ou']
                ];
                $this->stdLog($ldapcnn, "ldap_search", $context);

                if ($error = ldap_errno($ldapcnn)) {
                    $this->log($ldapcnn, 'Error in Search');

                    $flagError = true;
                } else {
                    if ($searchResult) {
                        //The first node is root
                        if (empty($arrayDepartment)) {
                            $arrayDepartment[] = [
                                'dn' => $arrayAuthenticationSourceData['AUTH_SOURCE_BASE_DN'],
                                'parent' => '',
                                'ou' => 'ROOT',
                                'users' => 0
                            ];
                        }

                        //Get departments from the ldap entries
                        if (ldap_count_entries($ldapcnn, $searchResult) > 0) {
                            $this->stdLog($ldapcnn, "ldap_count_entries");
                            $entry = ldap_first_entry($ldapcnn, $searchResult);
                            $this->stdLog($ldapcnn, "ldap_first_entry", $context);

                            do {
                                $arrayEntryData = $this->ldapGetAttributes($ldapcnn, $entry);
                                $unitsEqual = $this->custom_ldap_explode_dn($arrayEntryData['dn']);

                                if (count($unitsEqual) == 1 && $unitsEqual[0] == '') {
                                    continue;
                                }

                                if (count($unitsEqual) > count($unitsBase)) {
                                    unset($unitsEqual[0]);
                                }

                                if (isset($arrayEntryData['ou']) && !is_array($arrayEntryData['ou'])) {
                                    $arrayDepartment[] = [
                                        'dn' => $arrayEntryData['dn'],
                                        'parent' => (isset($unitsEqual[1])) ? implode(',', $unitsEqual) : '',
                                        'ou' => trim($arrayEntryData['ou']),
                                        'users' => 0
                                    ];
                                }
                            } while ($entry = ldap_next_entry($ldapcnn, $entry));
                        }
                    }
                }

                if (!$flagError) {
                    ldap_control_paged_result_response($ldapcnn, $searchResult, $cookie);
                    $this->stdLog($ldapcnn, "ldap_control_paged_result_response", $context);
                }
            } while (($cookie !== null && $cookie != '') && !$flagError);

            $str = '';

            foreach ($arrayDepartment as $dep) {
                $str .= ' ' . $dep['ou'];
            }

            $this->log($ldapcnn, 'found ' . count($arrayDepartment) . ' departments: ' . $str);

            return $arrayDepartment;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get the Userlist from a department based on the name
     * @param string $departmentName
     * @return array
     */
    public function getUsersFromDepartmentByName($departmentName)
    {
        $dFilter = "(&(" . $this->arrayObjectClassFilter["department"] . ")(ou=" . $departmentName . "))";

        $aUsers = array();
        $rbac = RBAC::getSingleton();

        $rbac->authSourcesObj = new AuthenticationSource();
        $aAuthSource = $rbac->authSourcesObj->load($this->sAuthSource);

        if (is_null($this->ldapcnn)) {
            $this->ldapcnn = $this->ldapConnection($aAuthSource);
        }

        $ldapcnn = $this->ldapcnn;

        $oSearch = @ldap_search($ldapcnn, $aAuthSource["AUTH_SOURCE_BASE_DN"], $dFilter, $this->arrayAttributesForUser);
        $context = [
            "baseDN" => $aAuthSource["AUTH_SOURCE_BASE_DN"],
            "filter" => $dFilter,
            "attributes" => $this->arrayAttributesForUser
        ];
        $this->stdLog($ldapcnn, "ldap_search", $context);

        if ($oError = ldap_errno($ldapcnn)) {
            return $aUsers;
        } else {
            if ($oSearch) {
                //get the departments from the ldap entries
                if (ldap_count_entries($ldapcnn, $oSearch) > 0) {
                    $this->stdLog($ldapcnn, "ldap_count_entries");
                    $oEntry = ldap_first_entry($ldapcnn, $oSearch);
                    $this->stdLog($ldapcnn, "ldap_first_entry");

                    do {
                        $aAttr = $this->ldapGetAttributes($ldapcnn, $oEntry);
                        $result = $this->ldapGetUsersFromDepartment("GET", $aAttr["dn"]);
                        foreach ($result as $item) {
                            $aUsers[] = $item;
                        }
                    } while ($oEntry = ldap_next_entry($ldapcnn, $oEntry));
                }
            }
            return $aUsers;
        }
    }

    /**
     * Check if the department exists and returns the PM UID
     * @param <type> $currentDN
     * @return <type>
     */
    public function getDepUidIfExistsDN($currentDN)
    {
        try {
            $oCriteria = new Criteria('workflow');
            $oCriteria->add(DepartmentPeer::DEP_STATUS, 'ACTIVE');
            $oCriteria->add(DepartmentPeer::DEP_LDAP_DN, $currentDN);

            $oDataset = DepartmentPeer::doSelectRS($oCriteria);
            $oDataset->setFetchmode(ResultSet::FETCHMODE_ASSOC);

            if ($oDataset->next()) {
                $aRow = $oDataset->getRow();

                return $aRow["DEP_UID"];
            }

            return "";
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get number of Users in each Department from the Database
     *
     * return array Return array with the number of Users in each Department from the Database
     */
    public function departmentsGetNumberOfUsersFromDb()
    {
        try {
            $arrayData = array();

            //Get data
            $criteria = new Criteria("workflow");

            $criteria->addSelectColumn(UsersPeer::DEP_UID);
            $criteria->addSelectColumn("COUNT(" . UsersPeer::DEP_UID . ") AS NUM_REC");
            $criteria->add(UsersPeer::USR_STATUS, "CLOSED", Criteria::NOT_EQUAL);
            $criteria->add(UsersPeer::DEP_UID, "", Criteria::NOT_EQUAL);
            $criteria->add(UsersPeer::DEP_UID, null, Criteria::ISNOTNULL);
            $criteria->addGroupByColumn(UsersPeer::DEP_UID);

            $rsCriteria = UsersPeer::doSelectRS($criteria);
            $rsCriteria->setFetchmode(ResultSet::FETCHMODE_ASSOC);

            while ($rsCriteria->next()) {
                $row = $rsCriteria->getRow();

                $arrayData[$row["DEP_UID"]] = $row["NUM_REC"];
            }

            return $arrayData;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function userIsTerminated($userUid, $sOuTerminated)
    {
        $terminated = false;
        $aLdapUsers = $this->getUsersFromDepartmentByName($sOuTerminated);

        foreach ($aLdapUsers as $aLdapUser) {
            if ($aLdapUser['sUsername'] == $userUid) {
                $terminated = true;
                break;
            }
        }

        return $terminated;
    }

    /* activate an user previously deactivated
      if user is now in another department, we need the second parameter, the depUid

      @param string $userUid
      @param string optional department DN
      @param string optional DepUid
     */

    public function activateUser($userUid, $userDn = null, $depUid = null)
    {
        if (!class_exists('RbacUsers')) {
            require_once(PATH_RBAC . 'model/RbacUsers.php');
        }

        $con = Propel::getConnection(RbacUsersPeer::DATABASE_NAME);
        // select set
        $c1 = new Criteria('rbac');
        $c1->add(RbacUsersPeer::USR_UID, $userUid);
        // update set
        $c2 = new Criteria('rbac');
        $c2->add(RbacUsersPeer::USR_STATUS, '1');

        if ($userDn != null) {
            $c2->add(RbacUsersPeer::USR_AUTH_USER_DN, $userDn);
            $c2->add(RbacUsersPeer::USR_AUTH_SUPERVISOR_DN, '');
        }

        BasePeer::doUpdate($c1, $c2, $con);

        if (!class_exists('Users')) {
            require_once('classes/model/Users.php');
        }

        $con = Propel::getConnection(UsersPeer::DATABASE_NAME);
        // select set
        $c1 = new Criteria('workflow');
        $c1->add(UsersPeer::USR_UID, $userUid);
        // update set
        $c2 = new Criteria('workflow');
        $c2->add(UsersPeer::USR_STATUS, 'ACTIVE');

        if ($depUid != null) {
            $c2->add(UsersPeer::DEP_UID, $depUid);
        }

        BasePeer::doUpdate($c1, $c2, $con);
    }

    public function deactivateUser($userUid)
    {
        if (!class_exists('RbacUsers')) {
            require_once(PATH_RBAC . 'model/RbacUsers.php');
        }

        $con = Propel::getConnection(RbacUsersPeer::DATABASE_NAME);
        // select set
        $c1 = new Criteria('rbac');
        $c1->add(RbacUsersPeer::USR_USERNAME, $userUid);
        // update set
        $c2 = new Criteria('rbac');
        $c2->add(RbacUsersPeer::USR_STATUS, '0');

        BasePeer::doUpdate($c1, $c2, $con);

        if (!class_exists('Users')) {
            require_once('classes/model/Users.php');
        }

        $con = Propel::getConnection(UsersPeer::DATABASE_NAME);
        // select set
        $c1 = new Criteria('workflow');
        $c1->add(UsersPeer::USR_USERNAME, $userUid);
        // update set
        $c2 = new Criteria('workflow');
        $c2->add(UsersPeer::USR_STATUS, 'INACTIVE');
        $c2->add(UsersPeer::DEP_UID, '');

        BasePeer::doUpdate($c1, $c2, $con);
    }

    public function getTerminatedOu()
    {
        if (trim($this->sAuthSource) != '') {
            $rbac = RBAC::getSingleton();
            $aAuthSource = $rbac->authSourcesObj->load($this->sAuthSource);
            $attributes = $aAuthSource['AUTH_SOURCE_DATA'];
            $this->sTerminatedOu = isset($attributes['AUTH_SOURCE_RETIRED_OU']) ? $attributes['AUTH_SOURCE_RETIRED_OU'] : '';
        }

        return $this->sTerminatedOu;
    }

    /**
     * get all authsource for this plugin ( ldapAdvanced plugin, because other authsources are not needed )
     * this function is used only by cron
     * returns only AUTH_SOURCE_PROVIDER = ldapAdvanced
     *
     * @return array with authsources with type = ldap
     */
    public function getAuthSources()
    {
        require_once(PATH_RBAC . 'model/AuthenticationSource.php');

        $oCriteria = new Criteria('rbac');
        $aAuthSources = array();

        $oAuthSource = new AuthenticationSource();
        $oCriteria = $oAuthSource->getAllAuthSources();
        $oDataset = AuthenticationSourcePeer::doSelectRS($oCriteria);
        $oDataset->setFetchmode(ResultSet::FETCHMODE_ASSOC);

        while ($oDataset->next()) {
            $aRow = $oDataset->getRow();

            if ($aRow['AUTH_SOURCE_PROVIDER'] == 'ldapAdvanced') {
                $aRow["AUTH_SOURCE_DATA"] = ($aRow["AUTH_SOURCE_DATA"] != "") ? unserialize($aRow["AUTH_SOURCE_DATA"]) : array();

                $aAuthSources[] = $aRow;
            }
        }

        return $aAuthSources;
    }

    /**
     * function to get departments from the array previously obtained from LDAP
     * we are calling registered departments
     * it is a recursive function, in the first call with an array with first top level departments from PM
     * then go thru all departments and obtain a list of departments already created in PM and pass that array
     * to next function to synchronize All users for each department
     * this function is used in cron only
     *
     * @param array departments obtained from LDAP/Active Directory
     * @param array of departments, first call have only top level departments
     */
    public function getRegisteredDepartments(array $arrayLdapDepartment, array $arrayDbDepartment)
    {
        $aResult = array();

        if (!empty($arrayLdapDepartment)) {
            $arrayLdapDepartment[0]["ou"] = $arrayLdapDepartment[0]["ou"] . " " . $arrayLdapDepartment[0]["dn"]; //Discard ROOT

            foreach ($arrayLdapDepartment as $ldapDept) {
                foreach ($arrayDbDepartment as $department) {
                    if ($department["DEP_TITLE"] == $ldapDept["ou"] && $department["DEP_LDAP_DN"] == $ldapDept["dn"]) {
                        $aResult[] = $department;
                        break;
                    }
                }
            }
        }

        return $aResult;
    }

    /**
     * select departments but it is not recursive, only returns departments in this level
     * @param string $DepParent the DEP_UID for parent department
     */
    public function getDepartments($DepParent)
    {
        try {
            $result = array();
            $criteria = new Criteria('workflow');

            if (!empty($DepParent)) {
                $criteria->add(DepartmentPeer::DEP_PARENT, $DepParent);
            }

            $con = Propel::getConnection(DepartmentPeer::DATABASE_NAME);
            $objects = DepartmentPeer::doSelect($criteria, $con);

            foreach ($objects as $oDepartment) {
                $node = array();
                $node['DEP_UID'] = $oDepartment->getDepUid();
                $node['DEP_PARENT'] = $oDepartment->getDepParent();
                $node['DEP_TITLE'] = stripslashes($oDepartment->getDepTitle());
                $node['DEP_STATUS'] = $oDepartment->getDepStatus();
                $node['DEP_MANAGER'] = $oDepartment->getDepManager();
                $node['DEP_LDAP_DN'] = $oDepartment->getDepLdapDn();
                $node['DEP_LAST'] = 0;

                $criteriaCount = new Criteria('workflow');
                $criteriaCount->clearSelectColumns();
                $criteriaCount->addSelectColumn('COUNT(*)');
                $criteriaCount->add(DepartmentPeer::DEP_PARENT, $oDepartment->getDepUid(), Criteria::EQUAL);
                $rs = DepartmentPeer::doSelectRS($criteriaCount);
                $rs->next();
                $row = $rs->getRow();
                $node['HAS_CHILDREN'] = $row[0];
                $result[] = $node;
            }

            if (count($result) >= 1) {
                $result[count($result) - 1]['DEP_LAST'] = 1;
            }

            return $result;
        } catch (exception $e) {
            throw $e;
        }
    }

    /**
     * function to get users from USERS table in wf_workflow and filter by department
     * this function is used in cron only
     *
     * @param string department UID ( DEP_UID value )
     * @return array of users
     */
    public function getUserFromPM($username)
    {
        try {
            $criteria = new Criteria("workflow");

            $criteria->addSelectColumn(UsersPeer::USR_UID);
            $criteria->addSelectColumn(UsersPeer::USR_USERNAME);
            $criteria->addSelectColumn(UsersPeer::DEP_UID);
            $criteria->add(UsersPeer::USR_STATUS, "CLOSED", Criteria::NOT_EQUAL);
            $criteria->add(UsersPeer::USR_USERNAME, $username, Criteria::EQUAL);

            $rsCriteria = UsersPeer::doSelectRS($criteria);
            $rsCriteria->setFetchmode(ResultSet::FETCHMODE_ASSOC);

            if ($rsCriteria->next()) {
                return $rsCriteria->getRow();
            }

            return array();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * get all user (UID, USERNAME) moved to Removed OU
     * this function is used in cron only
     *
     * @param array authSource row, in this fuction we are validating if Removed OU is defined or not
     * @return array of users
     */
    public function getUsersFromRemovedOu($aAuthSource)
    {
        $aUsers = array(); //empty array is the default result
        $attributes = $aAuthSource["AUTH_SOURCE_DATA"];
        $this->sTerminatedOu = isset($attributes['AUTH_SOURCE_RETIRED_OU']) ? trim($attributes['AUTH_SOURCE_RETIRED_OU']) : '';

        if ($this->sTerminatedOu == '') {
            return $aUsers;
        }

        return $this->getUsersFromDepartmentByName($this->sTerminatedOu);
    }

    /**
     * set STATUS=0 for all users in the array $aUsers
     * this functin is used to deactivate an array of users ( usually used for Removed OU )
     * this function is used in cron only
     *
     * @param array authSource row, in this fuction we are validating if Removed OU is defined or not
     * @return array of users
     */
    public function deactiveArrayOfUsers($aUsers)
    {
        if (!class_exists('RbacUsers')) {
            require_once(PATH_RBAC . 'model/RbacUsers.php');
        }

        if (!class_exists('Users')) {
            require_once('classes/model/Users.php');
        }

        $aUsrUid = array();

        foreach ($aUsers as $key => $val) {
            $aUsrUid[] = $val['sUsername'];
        }

        $con = Propel::getConnection('rbac');
        // select set
        $c1 = new Criteria('rbac');
        $c1->add(RbacUsersPeer::USR_USERNAME, $aUsrUid, Criteria::IN);
        $c1->add(RbacUsersPeer::USR_STATUS, 1);
        // update set
        $c2 = new Criteria('rbac');
        $c2->add(RbacUsersPeer::USR_STATUS, '0');
        BasePeer::doUpdate($c1, $c2, $con);

        $con = Propel::getConnection('workflow');
        // select set
        $c1 = new Criteria('workflow');
        $c1->add(UsersPeer::USR_USERNAME, $aUsrUid, Criteria::IN);
        // update set
        $c2 = new Criteria('workflow');
        $c2->add(UsersPeer::USR_STATUS, 'INACTIVE');
        $c2->add(UsersPeer::DEP_UID, '');

        BasePeer::doUpdate($c1, $c2, $con);

        return true;
    }

    /**
     * creates an users using the data send in the array $user
     * and then add the user to specific department
     * this function is used in cron only
     *
     * @param array $user info taken from ldap
     * @param string $depUid the department UID
     * @return boolean
     */
    public function createUserAndActivate($user, $depUid)
    {
        $rbac = RBAC::getSingleton();

        if ($rbac->userObj == null) {
            $rbac->userObj = new RbacUsers();
        }

        if ($rbac->rolesObj == null) {
            $rbac->rolesObj = new Roles();
        }

        if ($rbac->usersRolesObj == null) {
            $rbac->usersRolesObj = new UsersRoles();
        }

        $sUsername = $user['sUsername'];
        $sFullname = $user['sFullname'];
        $sFirstname = $user['sFirstname'];
        $sLastname = $user['sLastname'];
        $sEmail = $user['sEmail'];
        $sDn = $user['sDN'];
        $usrRole = empty($user['usrRole']) ? 'PROCESSMAKER_OPERATOR' : $user['usrRole'];

        $data = [];
        $data['USR_USERNAME'] = $sUsername;
        $data["USR_PASSWORD"] = "00000000000000000000000000000000";
        $data['USR_FIRSTNAME'] = $sFirstname;
        $data['USR_LASTNAME'] = $sLastname;
        $data['USR_EMAIL'] = $sEmail;
        $data['USR_DUE_DATE'] = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d'), date('Y') + 2));
        $data['USR_CREATE_DATE'] = date('Y-m-d H:i:s');
        $data['USR_UPDATE_DATE'] = date('Y-m-d H:i:s');
        $data['USR_BIRTHDAY'] = date('Y-m-d');
        $data['USR_STATUS'] = 1;
        $data['USR_AUTH_TYPE'] = 'ldapadvanced';
        $data['UID_AUTH_SOURCE'] = $this->sAuthSource;
        $data['USR_AUTH_USER_DN'] = $sDn;

        $userUid = $rbac->createUser($data, $usrRole);

        $data['USR_STATUS'] = 'ACTIVE';
        $data['USR_UID'] = $userUid;
        $data['DEP_UID'] = $depUid;
        $data['USR_ROLE'] = $usrRole;

        require_once 'classes/model/Users.php';

        $users = new Users();
        $users->create($data);

        return $userUid;
    }

    public function synchronizeManagers($managersHierarchy)
    {
        require_once 'classes/model/RbacUsers.php';

        try {
            foreach ($managersHierarchy as $managerDN => $subordinates) {
                $criteria = new Criteria('rbac');
                $criteria->addSelectColumn('*');
                $criteria->add(RbacUsersPeer::USR_AUTH_USER_DN, $managerDN);
                $dataset = RbacUsersPeer::doSelectRS($criteria);
                $dataset->setFetchmode(ResultSet::FETCHMODE_ASSOC);

                if ($dataset->next()) {
                    $row = $dataset->getRow();
                    $criteriaSet = new Criteria('workflow');
                    $criteriaSet->add(UsersPeer::USR_REPORTS_TO, $row['USR_UID']);
                    $criteriaWhere = new Criteria('workflow');
                    $criteriaWhere->add(UsersPeer::USR_UID, $subordinates, Criteria::IN);
                    BasePeer::doUpdate($criteriaWhere, $criteriaSet, Propel::getConnection('workflow'));
                }
            }
        } catch (Exception $error) {
            $this->log($this->ldapcnn, $error->getMessage());
        }
    }

    public function clearManager($usersUIDs)
    {
        try {
            $criteriaSet = new Criteria('workflow');
            $criteriaSet->add(UsersPeer::USR_REPORTS_TO, '');
            $criteriaWhere = new Criteria('workflow');
            $criteriaWhere->add(UsersPeer::USR_UID, $usersUIDs, Criteria::IN);
            BasePeer::doUpdate($criteriaWhere, $criteriaSet, Propel::getConnection('workflow'));
        } catch (Exception $error) {
            $this->log($this->ldapcnn, $error->getMessage());
        }
    }

    private function transformSettings($settings)
    {
        return [
            "AUTH_SOURCE_SERVER_NAME" => $settings["services.ldap.server.address"],
            "AUTH_SOURCE_PORT" => $settings["services.ldap.server.port"],
            "AUTH_SOURCE_ENABLED_TLS" => $settings["services.ldap.server.tls"],
            "AUTH_SOURCE_BASE_DN" => $settings["services.ldap.base_dn"],
            "AUTH_SOURCE_SEARCH_USER" => $settings["services.ldap.authentication.username"],
            "LDAP_TYPE" => $settings["services.ldap.type"] != null ? $settings["services.ldap.type"] : 'ldap',
            "AUTH_SOURCE_IDENTIFIER_FOR_USER" => $settings["services.ldap.identifiers.user"],
            "AUTH_SOURCE_PASSWORD" => $settings["services.ldap.authentication.password"],
            "AUTH_SOURCE_IDENTIFIER_FOR_USER_GROUP" => $settings["services.ldap.identifiers.group"],
            "AUTH_SOURCE_IDENTIFIER_FOR_USER_CLASS" => $settings["services.ldap.identifiers.user_class"],
        ];
    }

    private function loadLdapSettings($settings)
    {
        $aFields = [
            "AUTH_SOURCE_UID" => "",
            "AUTH_SOURCE_NAME" => "ProcessMaker",
            "AUTH_SOURCE_PROVIDER" => "ldapAdvanced",
            "AUTH_SOURCE_SERVER_NAME" => "ldap.forumsys.com",
            "AUTH_SOURCE_PORT" => "389",
            "AUTH_SOURCE_ENABLED_TLS" => "0",
            "AUTH_SOURCE_BASE_DN" => "ou=mathematicians,dc=example,dc=com",
            "AUTH_ANONYMOUS" => "1",
            "AUTH_SOURCE_SEARCH_USER" => "ou=scientists,dc=example,dc=com",
            "LDAP_TYPE" => "ad",
            "AUTH_SOURCE_AUTO_REGISTER" => "0",
            "AUTH_SOURCE_IDENTIFIER_FOR_USER" => "samaccountname",
            "AUTH_SOURCE_USERS_FILTER" => "",
            "AUTH_SOURCE_RETIRED_OU" => "",
            "AUTH_SOURCE_SHOWGRID" => "on",
            "AUTH_SOURCE_SIGNIN_POLICY_FOR_LDAP" => "1",
            "AUTH_SOURCE_DATA" => "",
            "AUTH_SOURCE_PASSWORD" => "password",
            "USR_ROLE" => "PROCESSMAKER_OPERATOR",
            "AUTH_SOURCE_IDENTIFIER_FOR_USER_GROUP" => "member",
            "AUTH_SOURCE_IDENTIFIER_FOR_USER_CLASS" => "",
            "GROUP_CLASS_IDENTIFIER" => "(objectclass=posixgroup)(objectclass=group)(objectclass=groupofuniquenames)(objectclass=organizationalunit)",
            "DEPARTMENT_CLASS_IDENTIFIER" => "(objectclass=organizationalunit)",
            "CUSTOM_CHECK_AUTH_SOURCE_IDENTIFIER_FOR_USER" => "0",
            "CUSTOM_CHECK_AUTH_SOURCE_IDENTIFIER_FOR_USER_GROUP" => "0",
            "CUSTOM_CHECK_DEPARTMENT_CLASS_IDENTIFIER" => "0",
            "CUSTOM_CHECK_GROUP_CLASS_IDENTIFIER" => "0",
            "CUSTOM_AUTH_SOURCE_IDENTIFIER_FOR_USER" => "",
            "CUSTOM_AUTH_SOURCE_IDENTIFIER_FOR_USER_GROUP" => "",
            "CUSTOM_DEPARTMENT_CLASS_IDENTIFIER" => "",
            "CUSTOM_GROUP_CLASS_IDENTIFIER" => ""
        ];

        $aFields = array_merge($aFields, $this->transformSettings($settings));

        try {
            //fixing the problem where the BaseDN has spaces.  we are removing the spaces.
            $baseDn = explode(',', $aFields['AUTH_SOURCE_BASE_DN']);
            foreach ($baseDn as $key => $val) $baseDn[$key] = trim($val);
            $aFields['AUTH_SOURCE_BASE_DN'] = implode(',', $baseDn);

            //$this->fromArray($aFields, BasePeer::TYPE_FIELDNAME);
            $aFields['AUTH_SOURCE_DATA'] = ($aFields['AUTH_SOURCE_DATA'] != '' ? unserialize($aFields['AUTH_SOURCE_DATA']) : array());
            return $aFields;
        } catch (\Exception $oError) {
            throw($oError);
        }
    }

    /**
     * Get a group list
     * @return <type>
     */
    public function searchGroups($settings)
    {
        $arrayAuthenticationSourceData = $this->loadLdapSettings($settings);
        $ldapcnn = $this->ldapConnection($arrayAuthenticationSourceData);
        $arrayGroup = [];
        //Get Groups
        // xxxx $limit = $this->getPageSizeLimitByData($arrayAuthenticationSourceData);
        $limit = 1000;

        $flagError = false;

        $filter = '(' . $this->arrayObjectClassFilter['group'] . ')';

        $this->log($ldapcnn, 'search groups with Filter: ' . $filter);

        $cookie = '';

        do {
            //xxx deprecated ldap_control_paged_result($ldapcnn, $limit, true, $cookie);

            // $this->stdLog($ldapcnn, "ldap_control_paged_result", ["pageSize" => $limit, "isCritical" => true]);

            $searchResult = @ldap_search($ldapcnn, $arrayAuthenticationSourceData['AUTH_SOURCE_BASE_DN'], $filter, ['dn', 'cn', 'ou']);
            $context = [
                "baseDN" => $arrayAuthenticationSourceData['AUTH_SOURCE_BASE_DN'],
                "filter" => $filter,
                "attributes" => ['dn', 'cn', 'ou']
            ];
            $this->stdLog($ldapcnn, "ldap_search", $context);

            if ($error = ldap_errno($ldapcnn)) {
                $this->log($ldapcnn, 'Error in Search');

                $flagError = true;
            } else {
                if ($searchResult) {
                    //Get groups from the ldap entries
                    $countEntries = ldap_count_entries($ldapcnn, $searchResult);
                    $this->stdLog($ldapcnn, "ldap_count_entries");

                    if ($countEntries > 0) {
                        $entry = ldap_first_entry($ldapcnn, $searchResult);
                        $this->stdLog($ldapcnn, "ldap_first_entry");

                        do {
                            $arrayEntryData = $this->ldapGetAttributes($ldapcnn, $entry);

                            if (isset($arrayEntryData['cn']) && !is_array($arrayEntryData['cn'])) {
                                $arrayGroup[] = [
                                    'dn' => $arrayEntryData['dn'],
                                    'cn' => trim($arrayEntryData['cn']),
                                    'ou' => trim($arrayEntryData['ou']),
                                    'users' => 0,
                                ];
                            } else {
                                $arrayGroup[] = [
                                    'dn' => $arrayEntryData['dn'],
                                    'ou' => trim($arrayEntryData['ou']),
                                    'users' => 0,
                                ];
                            }
                        } while ($entry = ldap_next_entry($ldapcnn, $entry));
                    }
                }
            }

            if (!$flagError) {
                // xxxx deprecated ldap_control_paged_result_response($ldapcnn, $searchResult, $cookie);
                //$this->stdLog($ldapcnn, "ldap_control_paged_result_response");
            }
        } while (($cookie !== null && $cookie != '') && !$flagError);

        return $arrayGroup;
    }

    public function searchUsersAlt($settings)
    {
        $arrayAuthenticationSourceData = $this->loadLdapSettings($settings);
        $ldapcnn = $this->ldapConnection($arrayAuthenticationSourceData);

        //Get Groups
        // xxxx $limit = $this->getPageSizeLimitByData($arrayAuthenticationSourceData);
        $limit = 1000;

        $flagError = false;

        $filter = '(' . $this->arrayObjectClassFilter['user'] . ')';

        $this->log($ldapcnn, 'search users with Filter: ' . $filter);

        $cookie = '';

        do {
            //xxx deprecated ldap_control_paged_result($ldapcnn, $limit, true, $cookie);

            // $this->stdLog($ldapcnn, "ldap_control_paged_result", ["pageSize" => $limit, "isCritical" => true]);

            $searchResult = @ldap_search($ldapcnn, $arrayAuthenticationSourceData['AUTH_SOURCE_BASE_DN'], $filter, ['dn', 'cn']);
            $context = [
                "baseDN" => $arrayAuthenticationSourceData['AUTH_SOURCE_BASE_DN'],
                "filter" => $filter,
                "attributes" => ['dn', 'cn']
            ];
            $this->stdLog($ldapcnn, "ldap_search", $context);

            if ($error = ldap_errno($ldapcnn)) {
                $this->log($ldapcnn, 'Error in Search');

                $flagError = true;
            } else {
                if ($searchResult) {
                    //Get groups from the ldap entries
                    $countEntries = ldap_count_entries($ldapcnn, $searchResult);
                    $this->stdLog($ldapcnn, "ldap_count_entries");

                    if ($countEntries > 0) {
                        $entry = ldap_first_entry($ldapcnn, $searchResult);
                        $this->stdLog($ldapcnn, "ldap_first_entry");

                        do {
                            $arrayEntryData = $this->ldapGetAttributes($ldapcnn, $entry);

                            if (isset($arrayEntryData['cn']) && !is_array($arrayEntryData['cn'])) {
                                $arrayUser[] = $arrayEntryData;
                            }
                        } while ($entry = ldap_next_entry($ldapcnn, $entry));
                    }
                }
            }

            if (!$flagError) {
                // xxxx deprecated ldap_control_paged_result_response($ldapcnn, $searchResult, $cookie);
                //$this->stdLog($ldapcnn, "ldap_control_paged_result_response");
            }
        } while (($cookie !== null && $cookie != '') && !$flagError);

        $str = '';

        foreach ($arrayUser as $user) {
            $str .= ' ' . $user['cn'];
        }

        $this->log($ldapcnn, 'found ' . count($arrayUser) . ' users: ' . $str);

        return $arrayUser;

    }

    /**
     * Check if the group exists and returns the PM UID
     * @param <type> $currentDN
     * @return <type>
     */
    public function getGrpUidIfExistsDN($currentDN)
    {
        try {
            $criteria = new Criteria('workflow');
            $criteria->add(GroupwfPeer::GRP_STATUS, 'ACTIVE');
            $criteria->add(GroupwfPeer::GRP_LDAP_DN, $currentDN);
            $dataset = GroupwfPeer::doSelectRS($criteria);
            $dataset->setFetchmode(ResultSet::FETCHMODE_ASSOC);

            if ($dataset->next()) {
                $row = $dataset->getRow();

                return $row['GRP_UID'];
            }

            return "";
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get group Uid by title.
     * @param string $title
     * @return string
     */
    public function getGroupUidByTitle(string $title): string
    {
        try {
            $groupWf = Groupwf::query()
                ->where('GRP_STATUS', '=', 'ACTIVE')
                ->where('GRP_TITLE', '=', $title)
                ->orderBy('GRP_ID', 'ASC')
                ->get()
                ->first();
            if (!empty($groupWf)) {
                return $groupWf->GRP_UID;
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            Log::channel(':ldapSynchronizeGroups')->error($message, Bootstrap::context());
        }
        return "";
    }

    /**
     * Check duplicate titles in GROUPWF table.
     * @return bool
     */
    public function checkDuplicateTitles(): bool
    {
        $sql = ""
            . "select GRP_TITLE,count(GRP_TITLE) "
            . "from GROUPWF "
            . "group by GRP_TITLE having count(GRP_TITLE)>1";
        $results = DB::select(DB::raw($sql));
        if (empty($results)) {
            return false;
        }
        return true;
    }

    /**
     * Get number of Users in each Group from the Database
     *
     * return array Return array with the number of Users in each Group from the Database
     */
    public function groupsGetNumberOfUsersFromDb()
    {
        try {
            $arrayData = array();

            //Get data
            $criteria = new Criteria("workflow");

            $criteria->addSelectColumn(GroupUserPeer::GRP_UID);
            $criteria->addSelectColumn("COUNT(" . GroupUserPeer::GRP_UID . ") AS NUM_REC");
            $criteria->addJoin(GroupUserPeer::USR_UID, UsersPeer::USR_UID, Criteria::LEFT_JOIN);
            $criteria->add(UsersPeer::USR_STATUS, "CLOSED", Criteria::NOT_EQUAL);
            $criteria->addGroupByColumn(GroupUserPeer::GRP_UID);

            $rsCriteria = GroupUserPeer::doSelectRS($criteria);
            $rsCriteria->setFetchmode(ResultSet::FETCHMODE_ASSOC);

            while ($rsCriteria->next()) {
                $row = $rsCriteria->getRow();

                $arrayData[$row["GRP_UID"]] = $row["NUM_REC"];
            }

            return $arrayData;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * select groups but it is not recursive, only returns groups in this level
     */
    public function getGroups()
    {
        try {
            $result = array();
            $criteria = new Criteria('workflow');
            $con = Propel::getConnection(GroupwfPeer::DATABASE_NAME);
            $objects = GroupwfPeer::doSelect($criteria, $con);

            foreach ($objects as $oGroup) {
                $node = array();
                $node['GRP_UID'] = $oGroup->getGrpUid();
                $node['GRP_TITLE'] = stripslashes($oGroup->getGrpTitle());
                $node['GRP_STATUS'] = $oGroup->getGrpStatus();
                $node['GRP_LDAP_DN'] = $oGroup->getGrpLdapDn();
                $result[] = $node;
            }

            return $result;
        } catch (exception $e) {
            throw $e;
        }
    }

    /**
     * function to get groups from the array previously obtained from LDAP
     * we are calling registered groups
     * it is a recursive function, in the first call with an array with first top level groups from PM
     * then go thru all groups and obtain a list of groups already created in PM and pass that array
     * to next function to synchronize All users for each group
     * this function is used in cron only
     *
     * @param array groups obtained from LDAP/Active Directory
     * @param array of groups, first call have only top level groups
     */
    public function getRegisteredGroups(array $arrayLdapGroup, array $arrayDbGroup)
    {
        $aResult = array();

        if (!empty($arrayLdapGroup)) {
            foreach ($arrayLdapGroup as $ldapGroup) {
                foreach ($arrayDbGroup as $group) {
                    if ($group["GRP_TITLE"] == $ldapGroup["cn"] && $group["GRP_LDAP_DN"] == $ldapGroup["dn"]) {
                        $aResult[] = $group;
                    }
                }
            }
        }

        return $aResult;
    }

    /**
     * Convert 18-digit LDAP timestamps  to format PM
     *
     * @param Date | $dateAD | Date of AD ('Windows NT time format' and 'Win32 FILETIME or SYSTEMTIME')
     * @param Date | $datePM | Date of PM
     */
    public function convertDateADtoPM($dateAD)
    {
        $unixTimestamp = ($dateAD / 10000000) - 11644560000;
        $datePM = date('Y-m-d', mktime(0, 0, 0, date('m'), '01', date('Y') + 2));
        if ($unixTimestamp > 0) {
            $dateAux = date("Y-m-d", $unixTimestamp);
            $yearAux = date("Y", $unixTimestamp);
            if (strlen(trim($yearAux)) <= 4) {
                $datePM = $dateAux;
            }
        }
        return $datePM;
    }

    public function custom_ldap_explode_dn($dn)
    {
        $dn = trim($dn, ',');
        $result = ldap_explode_dn($dn, 0);
        $this->stdLog(null, "ldap_explode_dn", ["dn" => $dn]);

        if (is_array($result)) {
            unset($result['count']);

            foreach ($result as $key => $value) {
                $result[$key] = addcslashes(preg_replace_callback("/\\\([0-9A-Fa-f]{2})/", function ($m) {
                    return chr(hexdec($m[1]));
                }, $value), '<>,"');
            }
        }

        return $result;
    }

    /**
     * Synchronize User for this Department
     *
     * @param string $departmentUid UID of Department
     * @param array $arrayUserLdap User LDAP data
     * @param array $arrayData Data
     *
     * return array Return data
     */
    public function departmentSynchronizeUser($departmentUid, array $arrayUserLdap, array $arrayData)
    {
        try {
            $this->debugLog("class.ldapAdvanced.php > function departmentSynchronizeUser() > START");
            $this->debugLog("class.ldapAdvanced.php > function departmentSynchronizeUser() > \$arrayUserLdap[sUsername] ----> " . $arrayUserLdap["sUsername"]);

            $userUid = "";
            $found = false;

            $arrayUserData = $this->departmentGetUserDataIfUsernameExists($arrayUserLdap["sUsername"]);

            if (!empty($arrayUserData)) {
                //User already exists in this department and there is nothing to do
                //User already exists
                $userUid = $arrayUserData["USR_UID"];
                $found = true;

                $arrayData["already"]++;
                $arrayData["alreadyUsers"] .= $arrayUserData["USR_USERNAME"] . " ";
            }

            if (!$found) {
                //If user DO NOT exists in this department.. do:
                //If exists with another AuthSource -> impossible
                //If exists in another department, but in PM and for this authsource, we need to move it
                //$arrayNewUserData = $this->searchUserByUid($arrayUserLdap["sUsername"]);
                $arrayNewUserData = $arrayUserLdap;

                $arrayAux = $this->custom_ldap_explode_dn($arrayNewUserData["sDN"]);
                array_shift($arrayAux);

                $departmentUid = $this->getDepUidIfExistsDN(implode(",", $arrayAux)); //Check if exists the Department DN in DB

                $this->debugLog("class.ldapAdvanced.php > function departmentSynchronizeUser() > \$departmentUid ----> $departmentUid");

                if ($departmentUid != "") {
                    $arrayUserData = $this->authenticationSourceGetUserDataIfUsernameExists($arrayNewUserData["sUsername"]);

                    if (!empty($arrayUserData)) {
                        //User exists in this Authentication Source
                        //Move User
                        $userUid = $arrayUserData["USR_UID"];

                        $this->activateUser($arrayUserData["USR_UID"], $arrayNewUserData["sDN"], $departmentUid);

                        $arrayData["moved"]++;
                        $arrayData["movedUsers"] .= $arrayUserData["USR_USERNAME"] . " ";

                        $this->setArrayAuthenticationSourceUser($userUid, $arrayNewUserData); //INITIALIZE DATA //Update User
                    } else {
                        $arrayUserData = $this->getUserFromPM($arrayNewUserData["sUsername"]);

                        if (!empty($arrayUserData)) {
                            //User exists in another Authentication Source and another Department
                            //Impossible
                            $userUid = $arrayUserData["USR_UID"];

                            $arrayData["impossible"]++;
                            $arrayData["impossibleUsers"] .= $arrayUserData["USR_USERNAME"] . " ";
                        } else {
                            //User not exists
                            //Create User
                            $userUid = $this->createUserAndActivate($arrayNewUserData, $departmentUid);

                            $arrayData["created"]++;
                            $arrayData["createdUsers"] .= $arrayNewUserData["sUsername"] . " ";

                            $this->setArrayAuthenticationSourceUser($userUid, $arrayNewUserData); //INITIALIZE DATA //Add User
                        }
                    }
                }
            }

            if ($userUid != "") {
                $arrayData["arrayUserUid"][] = $userUid;

                if (isset($arrayUserLdap["sManagerDN"]) && $arrayUserLdap["sManagerDN"] != "") {
                    if (!isset($arrayData["managersHierarchy"][$arrayUserLdap["sManagerDN"]])) {
                        $arrayData["managersHierarchy"][$arrayUserLdap["sManagerDN"]] = array();
                    }

                    $arrayData["managersHierarchy"][$arrayUserLdap["sManagerDN"]][$userUid] = $userUid;
                }
            }

            $this->debugLog("class.ldapAdvanced.php > function departmentSynchronizeUser() > \$userUid ----> $userUid");
            $this->debugLog("class.ldapAdvanced.php > function departmentSynchronizeUser() > END");

            return $arrayData;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Synchronize User for this Group
     *
     * @param string $groupUid UID of Group
     * @param array $arrayUserLdap User LDAP data
     * @param array $arrayData Data
     *
     * return array Return data
     */
    public function groupSynchronizeUser($groupUid, array $arrayUserLdap, array $arrayData)
    {
        try {
            $this->debugLog("class.ldapAdvanced.php > function groupSynchronizeUser() > START");
            $this->debugLog("class.ldapAdvanced.php > function groupSynchronizeUser() > \$arrayUserLdap[sUsername] ----> " . $arrayUserLdap["sUsername"]);

            $group = new Groups();

            $userUid = "";
            $found = false;

            $arrayUserData = $this->groupGetUserDataIfUsernameExists($arrayUserLdap["sUsername"]);

            if (!empty($arrayUserData)) {
                //User already exists in this group and there is nothing to do
                //User already exists
                $userUid = $arrayUserData["USR_UID"];
                $found = true;

                $arrayData["already"]++;
                $arrayData["alreadyUsers"] .= $arrayUserData["USR_USERNAME"] . " ";
            }

            if (!$found) {
                //If user DO NOT exists in this group.. do:
                //If exists with another AuthSource -> impossible
                //If exists in another group, but in PM and for this authsource, we need to move it
                //$arrayNewUserData = $this->searchUserByUid($arrayUserLdap["sUsername"]);
                $arrayNewUserData = $arrayUserLdap;

                $arrayUserData = $this->authenticationSourceGetUserDataIfUsernameExists($arrayNewUserData["sUsername"]);

                if (!empty($arrayUserData)) {
                    //User exists in this Authentication Source
                    //Move User
                    $userUid = $arrayUserData["USR_UID"];

                    $this->activateUser($arrayUserData["USR_UID"], $arrayNewUserData["sDN"]);

                    $group->addUserToGroup($groupUid, $userUid);

                    $arrayData["moved"]++;
                    $arrayData["movedUsers"] .= $arrayUserData["USR_USERNAME"] . " ";

                    $this->setArrayAuthenticationSourceUser($userUid, $arrayNewUserData); //INITIALIZE DATA //Update User
                } else {
                    $arrayUserData = $this->getUserFromPM($arrayNewUserData["sUsername"]);

                    if (!empty($arrayUserData)) {
                        //User exists in another Authentication Source and another Group
                        //Impossible
                        $userUid = $arrayUserData["USR_UID"];

                        $arrayData["impossible"]++;
                        $arrayData["impossibleUsers"] .= $arrayUserData["USR_USERNAME"] . " ";
                    } else {
                        //User not exists
                        //Create User
                        $userUid = $this->createUserAndActivate($arrayNewUserData, "");

                        $group->addUserToGroup($groupUid, $userUid);

                        $arrayData["created"]++;
                        $arrayData["createdUsers"] .= $arrayNewUserData["sUsername"] . " ";

                        $this->setArrayAuthenticationSourceUser($userUid, $arrayNewUserData); //INITIALIZE DATA //Add User
                    }
                }
            }

            if ($userUid != "") {
                $arrayData["arrayUserUid"][] = $userUid;

                if (isset($arrayUserLdap["sManagerDN"]) && $arrayUserLdap["sManagerDN"] != "") {
                    if (!isset($arrayData["managersHierarchy"][$arrayUserLdap["sManagerDN"]])) {
                        $arrayData["managersHierarchy"][$arrayUserLdap["sManagerDN"]] = array();
                    }

                    $arrayData["managersHierarchy"][$arrayUserLdap["sManagerDN"]][$userUid] = $userUid;
                }
            }

            $this->debugLog("class.ldapAdvanced.php > function groupSynchronizeUser() > \$userUid ----> $userUid");
            $this->debugLog("class.ldapAdvanced.php > function groupSynchronizeUser() > END");

            //Return
            return $arrayData;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Update User data based on the LDAP Server
     *
     * @param resource $ldapcnn LDAP link identifier
     * @param array $arrayAuthSourceData Authentication Source Data
     * @param string $userDn User DN
     * @param array $arrayUser Users
     *
     * @return bool
     */
    private function ldapUserUpdateByDnAndData($ldapcnn, array $arrayAuthSourceData, $userDn, array $arrayUser)
    {
        try {
            //Set variables
            $rbac = RBAC::getSingleton();

            if (is_null($rbac->userObj)) {
                $rbac->userObj = new RbacUsers();
            }

            //Set variables
            $flagUser = false;

            $arrayAttributesToSync = [
                //Default attributes to sync
                'USR_FIRSTNAME' => 'givenname',
                'USR_LASTNAME' => 'sn',
                'USR_EMAIL' => 'mail',
                'USR_STATUS' => 'useraccountcontrol'
            ];

            if (isset($arrayAuthSourceData['AUTH_SOURCE_DATA']['AUTH_SOURCE_GRID_ATTRIBUTE']) &&
                !empty($arrayAuthSourceData['AUTH_SOURCE_DATA']['AUTH_SOURCE_GRID_ATTRIBUTE'])
            ) {
                foreach ($arrayAuthSourceData['AUTH_SOURCE_DATA']['AUTH_SOURCE_GRID_ATTRIBUTE'] as $value) {
                    $arrayAttributesToSync[$value['attributeUser']] = $value['attributeLdap'];
                }
            }

            //Search User from LDAP Server
            $uidUserIdentifier = (isset($arrayAuthSourceData['AUTH_SOURCE_DATA']['AUTH_SOURCE_IDENTIFIER_FOR_USER'])) ?
                $arrayAuthSourceData['AUTH_SOURCE_DATA']['AUTH_SOURCE_IDENTIFIER_FOR_USER'] : 'uid';

            $arrayAttribute = array_merge($this->arrayAttributesForUser, array_values($arrayAttributesToSync));

            $searchResult = @ldap_search($ldapcnn, $userDn, '(objectclass=*)', $arrayAttribute);
            $context = [
                "baseDN" => $userDn,
                "filter" => "(objectclass=*)",
                "attributes" => $arrayAttribute
            ];
            $this->stdLog($ldapcnn, "ldap_search", $context);

            if ($error = ldap_errno($ldapcnn)) {
                //
            } else {
                if ($searchResult && ldap_count_entries($ldapcnn, $searchResult) > 0) {
                    $this->stdLog($ldapcnn, "ldap_count_entries");
                    $entry = ldap_first_entry($ldapcnn, $searchResult);
                    $this->stdLog($ldapcnn, "ldap_first_entry", $context);

                    $arrayUserLdap = $this->ldapGetAttributes($ldapcnn, $entry);

                    $username = (isset($arrayUserLdap[$uidUserIdentifier])) ? $arrayUserLdap[$uidUserIdentifier] : '';

                    if ((is_array($username) && !empty($username)) || trim($username) != '') {
                        $username = trim((is_array($username)) ? $username[0] : $username);

                        if (isset($arrayUser[$username])) {
                            if (!isset($this->arrayUserUpdateChecked[$username])) {
                                $this->arrayUserUpdateChecked[$username] = 1;

                                $arrayUserDataUpdate = [];

                                foreach ($arrayAttributesToSync as $key => $value) {
                                    $fieldName = $key;
                                    $attributeName = strtolower($value);

                                    if (isset($arrayUserLdap[$attributeName])) {
                                        $ldapAttributeValue = trim((is_array($arrayUserLdap[$attributeName])) ? $arrayUserLdap[$attributeName][0] : $arrayUserLdap[$attributeName]);

                                        switch ($fieldName) {
                                            case 'USR_STATUS':
                                                if ($attributeName == 'useraccountcontrol') {
                                                    $ldapAttributeValue = (preg_match('/^(?:' . '512|544|66048|66080' . ')$/', $ldapAttributeValue)) ? (($arrayUser[$username][$fieldName] == 'VACATION') ? 'VACATION' : 'ACTIVE') : 'INACTIVE';
                                                }
                                                break;
                                            case 'USR_DUE_DATE':
                                                if ($attributeName == 'accountexpires') {
                                                    $ldapAttributeValue = $this->convertDateADtoPM($ldapAttributeValue);
                                                }
                                                break;
                                        }

                                        if ($ldapAttributeValue != $arrayUser[$username][$fieldName]) {
                                            $arrayUserDataUpdate[$fieldName] = $ldapAttributeValue;
                                        }
                                    }
                                }

                                if (!empty($arrayUserDataUpdate)) {
                                    $arrayUserDataUpdate['USR_UID'] = $arrayUser[$username]['USR_UID'];

                                    //Update User data
                                    $rbac->updateUser($arrayUserDataUpdate);

                                    $user = new Users();
                                    $result = $user->update($arrayUserDataUpdate);
                                }
                            } else {
                                $this->log(
                                    $ldapcnn,
                                    'User is repeated: Username "' . $username . '", DN "' . $arrayUserLdap['dn'] . '"'
                                );
                            }

                            $flagUser = true;
                        }
                    }
                }
            }

            //Return
            return $flagUser;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Update Users data based on the LDAP Server
     *
     * @param resource $ldapcnn LDAP link identifier
     * @param array $arrayAuthSourceData Authentication Source Data
     * @param string $filterUsers Filter
     * @param array $arrayUserUid UID of Users
     * @param array $arrayData Data
     *
     * @return array
     */
    private function ldapUsersUpdateData($ldapcnn, array $arrayAuthSourceData, $filterUsers, array $arrayUserUid, array $arrayData)
    {
        try {
            $totalUser = $arrayData['totalUser'];
            $countUser = $arrayData['countUser'];

            //Search Users
            $filter = '(&(' . $this->arrayObjectClassFilter['user'] . ')(|' . $filterUsers . '))';

            $searchResult = @ldap_search($ldapcnn, $arrayAuthSourceData['AUTH_SOURCE_BASE_DN'], $filter, $this->arrayAttributesForUser);
            $context = [
                "baseDN" => $arrayAuthSourceData['AUTH_SOURCE_BASE_DN'],
                "filter" => $filter,
                "attributes" => $this->arrayAttributesForUser
            ];
            $this->stdLog($ldapcnn, "ldap_search", $context);

            if ($error = ldap_errno($ldapcnn)) {
                //
            } else {
                if ($searchResult && ldap_count_entries($ldapcnn, $searchResult) > 0) {
                    $this->stdLog($ldapcnn, "ldap_count_entries");
                    //Get Users from DB
                    $arrayUser = [];

                    $criteria = new Criteria('workflow');

                    $criteria->add(UsersPeer::USR_UID, $arrayUserUid, Criteria::IN);
                    $criteria->add(UsersPeer::USR_STATUS, 'CLOSED', Criteria::NOT_EQUAL);

                    $rsCriteria = UsersPeer::doSelectRS($criteria);
                    $rsCriteria->setFetchmode(ResultSet::FETCHMODE_ASSOC);

                    while ($rsCriteria->next()) {
                        $row = $rsCriteria->getRow();

                        $arrayUser[$row['USR_USERNAME']] = $row;
                    }

                    //Get Users from LDAP Server
                    $entry = ldap_first_entry($ldapcnn, $searchResult);
                    $this->stdLog($ldapcnn, "ldap_first_entry");

                    do {
                        if ($this->ldapUserUpdateByDnAndData(
                            $ldapcnn,
                            $arrayAuthSourceData,
                            ldap_get_dn($ldapcnn, $entry),
                            $arrayUser
                        )
                        ) {
                            $countUser++;

                            //Progress bar
                            $this->frontEndShow(
                                'BAR',
                                'Update Users data: ' . $countUser . '/' . $totalUser . ' ' . $this->progressBar($totalUser, $countUser)
                            );
                        }
                    } while ($entry = ldap_next_entry($ldapcnn, $entry));
                }
            }

            return [$totalUser, $countUser];
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Update Users data based on the LDAP Server
     *
     * @param string $authenticationSourceUid UID of Authentication Source
     *
     * return void
     */
    public function usersUpdateData($authenticationSourceUid)
    {
        try {
            $totalUser = count($this->arrayAuthenticationSourceUsersByUid);
            $countUser = 0;

            //Set variables
            $rbac = RBAC::getSingleton();

            if (is_null($rbac->authSourcesObj)) {
                $rbac->authSourcesObj = new AuthenticationSource();
            }

            $arrayAuthenticationSourceData = $rbac->authSourcesObj->load($authenticationSourceUid);

            $this->ldapcnn = $this->ldapConnection($arrayAuthenticationSourceData);

            $ldapcnn = $this->ldapcnn;

            //Update Users
            $limit = $this->getPageSizeLimitByData($arrayAuthenticationSourceData);
            $count = 0;

            $uidUserIdentifier = (isset($arrayAuthenticationSourceData["AUTH_SOURCE_DATA"]["AUTH_SOURCE_IDENTIFIER_FOR_USER"])) ? $arrayAuthenticationSourceData["AUTH_SOURCE_DATA"]["AUTH_SOURCE_IDENTIFIER_FOR_USER"] : "uid";

            $filterUsers = "";
            $arrayUserUid = array();

            foreach ($this->arrayAuthenticationSourceUsersByUid as $value) {
                $arrayUserData = $value;

                $count++;

                $filterUsers .= "($uidUserIdentifier=" . $arrayUserData["USR_USERNAME"] . ")";
                $arrayUserUid[] = $arrayUserData["USR_UID"];

                if ($count == $limit) {
                    list($totalUser, $countUser) = $this->ldapUsersUpdateData(
                        $ldapcnn,
                        $arrayAuthenticationSourceData,
                        $filterUsers,
                        $arrayUserUid,
                        ['totalUser' => $totalUser, 'countUser' => $countUser]
                    );

                    $count = 0;

                    $filterUsers = "";
                    $arrayUserUid = array();
                }
            }

            if ($count > 0) {
                list($totalUser, $countUser) = $this->ldapUsersUpdateData(
                    $ldapcnn,
                    $arrayAuthenticationSourceData,
                    $filterUsers,
                    $arrayUserUid,
                    ['totalUser' => $totalUser, 'countUser' => $countUser]
                );
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Get page size limit for a search result
     *
     * @param array $arrayAuthSourceData Authentication Source Data
     *
     * @return int Returns the page size limit for a search result
     */
    private function getPageSizeLimitByData(array $arrayAuthSourceData)
    {
        if (isset($arrayAuthSourceData['AUTH_SOURCE_DATA']['LDAP_PAGE_SIZE_LIMIT'])) {
            return $arrayAuthSourceData['AUTH_SOURCE_DATA']['LDAP_PAGE_SIZE_LIMIT'];
        } else {
            return $this->getPageSizeLimit(false);
        }
    }

    /**
     * Get page size limit for a search result
     *
     * @param resource $ldapcnn LDAP link identifier
     * @param string $baseDn The base DN for the directory
     *
     * @return int Returns the page size limit for a search result
     */
    public function getPageSizeLimit($ldapcnn, $baseDn = '')
    {
        try {
            $limit = 1000;

            if ($ldapcnn === false) {
                return $limit;
            }

            $searchResult = @ldap_search($ldapcnn, $baseDn, '(|(objectclass=*))', ['dn']);
            $context = [
                "baseDN" => $baseDn,
                "filter" => "(|(objectclass=*))",
                "attributes" => ['dn']
            ];
            $this->stdLog($ldapcnn, "ldap_search", $context);

            if ($searchResult) {
                $countEntries = ldap_count_entries($ldapcnn, $searchResult);
                $this->stdLog($ldapcnn, "ldap_count_entries");

                if ($countEntries > 0) {
                    $limit = ($countEntries > $limit) ? $limit : $countEntries;
                }
            }

            return $limit;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Standard log
     * @param resource $link
     * @param string $message
     * @param array $context
     * @param string $level
     */
    private function stdLog($obj, string $string, array $array = [])
    {
        \Illuminate\Support\Facades\Log::Critical(" var = " . print_r($obj, true) . $string, $array);
    }
}
