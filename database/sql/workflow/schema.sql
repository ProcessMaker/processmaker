
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

#-----------------------------------------------------------------------------
#-- APPLICATION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APPLICATION`;


CREATE TABLE `APPLICATION`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`APP_TITLE` MEDIUMTEXT  NOT NULL,
	`APP_DESCRIPTION` MEDIUMTEXT,
	`APP_NUMBER` INTEGER NOT NULL AUTO_INCREMENT,
	`APP_PARENT` VARCHAR(32) default '0' NOT NULL,
	`APP_STATUS` VARCHAR(100) default '' NOT NULL,
	`APP_STATUS_ID` TINYINT default 0 NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`APP_PROC_STATUS` VARCHAR(100) default '' NOT NULL,
	`APP_PROC_CODE` VARCHAR(100) default '' NOT NULL,
	`APP_PARALLEL` VARCHAR(32) default 'NO' NOT NULL,
	`APP_INIT_USER` VARCHAR(32) default '' NOT NULL,
	`APP_CUR_USER` VARCHAR(32) default '' NOT NULL,
	`APP_CREATE_DATE` DATETIME  NOT NULL,
	`APP_INIT_DATE` DATETIME  NOT NULL,
	`APP_FINISH_DATE` DATETIME,
	`APP_UPDATE_DATE` DATETIME  NOT NULL,
	`APP_DATA` JSON  NOT NULL,
	`APP_PIN` VARCHAR(256) default '' NOT NULL,
	`APP_DURATION` DOUBLE default 0,
	`APP_DELAY_DURATION` DOUBLE default 0,
	`APP_DRIVE_FOLDER_UID` VARCHAR(32) default '',
	`APP_ROUTING_DATA` MEDIUMTEXT,
	PRIMARY KEY (`APP_UID`),
	UNIQUE KEY `INDEX_APP_NUMBER` (`APP_NUMBER`),
	KEY `indexApp`(`PRO_UID`, `APP_STATUS`, `APP_UID`),
	KEY `indexAppNumber`(`APP_NUMBER`),
	KEY `indexAppStatus`(`APP_STATUS`),
	KEY `indexAppCreateDate`(`APP_CREATE_DATE`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='The application';
#-----------------------------------------------------------------------------
#-- APP_SEQUENCE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_SEQUENCE`;


CREATE TABLE `APP_SEQUENCE`
(
	`ID` INTEGER  NOT NULL,
	PRIMARY KEY (`ID`)
)ENGINE=InnoDB ;
#-----------------------------------------------------------------------------
#-- APP_DELEGATION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_DELEGATION`;


CREATE TABLE `APP_DELEGATION`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`DELEGATION_ID` INTEGER  NOT NULL AUTO_INCREMENT,
	`APP_NUMBER` INTEGER default 0,
	`DEL_PREVIOUS` INTEGER default 0 NOT NULL,
	`DEL_LAST_INDEX` INTEGER default 0 NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_TYPE` VARCHAR(32) default 'NORMAL' NOT NULL,
	`DEL_THREAD` INTEGER default 0 NOT NULL,
	`DEL_THREAD_STATUS` VARCHAR(32) default 'OPEN' NOT NULL,
	`DEL_PRIORITY` VARCHAR(32) default '3' NOT NULL,
	`DEL_DELEGATE_DATE` DATETIME  NOT NULL,
	`DEL_INIT_DATE` DATETIME,
	`DEL_FINISH_DATE` DATETIME,
	`DEL_TASK_DUE_DATE` DATETIME,
	`DEL_RISK_DATE` DATETIME,
	`DEL_DURATION` DOUBLE default 0,
	`DEL_QUEUE_DURATION` DOUBLE default 0,
	`DEL_DELAY_DURATION` DOUBLE default 0,
	`DEL_STARTED` TINYINT default 0,
	`DEL_FINISHED` TINYINT default 0,
	`DEL_DELAYED` TINYINT default 0,
	`DEL_DATA` MEDIUMTEXT  NOT NULL,
	`APP_OVERDUE_PERCENTAGE` DOUBLE default 0 NOT NULL,
	`USR_ID` INTEGER default 0,
	`PRO_ID` INTEGER default 0,
	`TAS_ID` INTEGER default 0,
	PRIMARY KEY (`APP_UID`,`DEL_INDEX`),
	UNIQUE KEY `DELEGATION_ID` (`DELEGATION_ID`),
	KEY `INDEX_APP_NUMBER`(`APP_NUMBER`),
	KEY `INDEX_USR_ID`(`USR_ID`),
	KEY `INDEX_PRO_ID`(`PRO_ID`),
	KEY `INDEX_TAS_ID`(`TAS_ID`),
	KEY `INDEX_USR_UID`(`USR_UID`),
	KEY `INDEX_THREAD_STATUS_APP_NUMBER`(`DEL_THREAD_STATUS`, `APP_NUMBER`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Delegation a task to user';
#-----------------------------------------------------------------------------
#-- APP_DOCUMENT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_DOCUMENT`;


CREATE TABLE `APP_DOCUMENT`
(
	`APP_DOC_UID` VARCHAR(32) default '' NOT NULL,
	`APP_DOC_FILENAME` MEDIUMTEXT  NOT NULL,
	`APP_DOC_TITLE` MEDIUMTEXT,
	`APP_DOC_COMMENT` MEDIUMTEXT,
	`DOC_VERSION` INTEGER default 1 NOT NULL,
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`DOC_UID` VARCHAR(32) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`APP_DOC_TYPE` VARCHAR(32) default '' NOT NULL,
	`APP_DOC_CREATE_DATE` DATETIME  NOT NULL,
	`APP_DOC_INDEX` INTEGER  NOT NULL,
	`FOLDER_UID` VARCHAR(32) default '',
	`APP_DOC_PLUGIN` VARCHAR(150) default '',
	`APP_DOC_TAGS` MEDIUMTEXT,
	`APP_DOC_STATUS` VARCHAR(32) default 'ACTIVE' NOT NULL,
	`APP_DOC_STATUS_DATE` DATETIME,
	`APP_DOC_FIELDNAME` VARCHAR(150),
	`APP_DOC_DRIVE_DOWNLOAD` MEDIUMTEXT,
	`SYNC_WITH_DRIVE` VARCHAR(32) default 'UNSYNCHRONIZED' NOT NULL,
	`SYNC_PERMISSIONS` MEDIUMTEXT,
	PRIMARY KEY (`APP_DOC_UID`,`DOC_VERSION`),
	KEY `indexAppDocument`(`FOLDER_UID`, `APP_DOC_UID`),
	KEY `indexAppUid`(`APP_UID`),
	KEY `indexAppUidDocUidDocVersionDocType`(`APP_UID`, `DOC_UID`, `DOC_VERSION`, `APP_DOC_TYPE`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Documents in an Application';
#-----------------------------------------------------------------------------
#-- APP_MESSAGE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_MESSAGE`;


CREATE TABLE `APP_MESSAGE`
(
	`APP_MSG_UID` VARCHAR(32)  NOT NULL,
	`MSG_UID` VARCHAR(32),
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`APP_MSG_TYPE` VARCHAR(100) default '' NOT NULL,
	`APP_MSG_SUBJECT` VARCHAR(150) default '' NOT NULL,
	`APP_MSG_FROM` VARCHAR(100) default '' NOT NULL,
	`APP_MSG_TO` MEDIUMTEXT  NOT NULL,
	`APP_MSG_BODY` MEDIUMTEXT  NOT NULL,
	`APP_MSG_DATE` DATETIME  NOT NULL,
	`APP_MSG_CC` MEDIUMTEXT,
	`APP_MSG_BCC` MEDIUMTEXT,
	`APP_MSG_TEMPLATE` MEDIUMTEXT,
	`APP_MSG_STATUS` VARCHAR(20),
	`APP_MSG_ATTACH` MEDIUMTEXT,
	`APP_MSG_SEND_DATE` DATETIME  NOT NULL,
	`APP_MSG_SHOW_MESSAGE` TINYINT default 1 NOT NULL,
	`APP_MSG_ERROR` MEDIUMTEXT,
	`TAS_ID` INTEGER default 0,
	`APP_NUMBER` INTEGER default 0,
	PRIMARY KEY (`APP_MSG_UID`),
	KEY `indexForAppUid`(`APP_UID`),
	KEY `indexForMsgStatus`(`APP_MSG_STATUS`),
	KEY `INDEX_TAS_ID`(`TAS_ID`),
	KEY `INDEX_APP_NUMBER`(`APP_NUMBER`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Messages in an Application';
#-----------------------------------------------------------------------------
#-- APP_OWNER
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_OWNER`;


CREATE TABLE `APP_OWNER`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`OWN_UID` VARCHAR(32) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	PRIMARY KEY (`APP_UID`,`OWN_UID`,`USR_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- CONFIGURATION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `CONFIGURATION`;


CREATE TABLE `CONFIGURATION`
(
	`CFG_UID` VARCHAR(32) default '' NOT NULL,
	`OBJ_UID` VARCHAR(128) default '' NOT NULL,
	`CFG_VALUE` MEDIUMTEXT  NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	PRIMARY KEY (`CFG_UID`,`OBJ_UID`,`PRO_UID`,`USR_UID`,`APP_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Stores the users, processes and/or applications configuratio';
#-----------------------------------------------------------------------------
#-- CONTENT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `CONTENT`;


CREATE TABLE `CONTENT`
(
	`CON_CATEGORY` VARCHAR(30) default '' NOT NULL,
	`CON_PARENT` VARCHAR(32) default '' NOT NULL,
	`CON_ID` VARCHAR(100) default '' NOT NULL,
	`CON_LANG` VARCHAR(10) default '' NOT NULL,
	`CON_VALUE` MEDIUMTEXT  NOT NULL,
	PRIMARY KEY (`CON_CATEGORY`,`CON_PARENT`,`CON_ID`,`CON_LANG`),
	KEY `indexUidLang`(`CON_ID`, `CON_LANG`),
	KEY `indexCatParUidLang`(`CON_CATEGORY`, `CON_PARENT`, `CON_ID`, `CON_LANG`),
	KEY `indexUid`(`CON_ID`, `CON_CATEGORY`, `CON_LANG`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- DEPARTMENT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `DEPARTMENT`;


CREATE TABLE `DEPARTMENT`
(
	`DEP_UID` VARCHAR(32) default '' NOT NULL,
	`DEP_TITLE` MEDIUMTEXT  NOT NULL,
	`DEP_PARENT` VARCHAR(32) default '' NOT NULL,
	`DEP_MANAGER` VARCHAR(32) default '' NOT NULL,
	`DEP_LOCATION` INTEGER default 0 NOT NULL,
	`DEP_STATUS` VARCHAR(10) default 'ACTIVE' NOT NULL,
	`DEP_REF_CODE` VARCHAR(50) default '' NOT NULL,
	`DEP_LDAP_DN` VARCHAR(255) default '' NOT NULL,
	PRIMARY KEY (`DEP_UID`),
	KEY `DEP_BYPARENT`(`DEP_PARENT`),
	KEY `BY_DEP_LDAP_DN`(`DEP_LDAP_DN`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Departments';
#-----------------------------------------------------------------------------
#-- DYNAFORM
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `DYNAFORM`;


CREATE TABLE `DYNAFORM`
(
	`DYN_UID` VARCHAR(32) default '' NOT NULL,
	`DYN_TITLE` MEDIUMTEXT  NOT NULL,
	`DYN_DESCRIPTION` MEDIUMTEXT,
	`PRO_UID` VARCHAR(32) default '0' NOT NULL,
	`DYN_TYPE` VARCHAR(20) default 'xmlform' NOT NULL,
	`DYN_FILENAME` VARCHAR(100) default '' NOT NULL,
	`DYN_CONTENT` MEDIUMTEXT,
	`DYN_LABEL` MEDIUMTEXT,
	`DYN_VERSION` INTEGER  NOT NULL,
	`DYN_UPDATE_DATE` DATETIME,
	PRIMARY KEY (`DYN_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Forms required';
#-----------------------------------------------------------------------------
#-- GROUPWF
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `GROUPWF`;


CREATE TABLE `GROUPWF`
(
	`GRP_UID` VARCHAR(32)  NOT NULL,
	`GRP_TITLE` MEDIUMTEXT  NOT NULL,
	`GRP_STATUS` CHAR(8) default 'ACTIVE' NOT NULL,
	`GRP_LDAP_DN` VARCHAR(255) default '' NOT NULL,
	`GRP_UX` VARCHAR(128) default 'NORMAL',
	PRIMARY KEY (`GRP_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- GROUP_USER
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `GROUP_USER`;


CREATE TABLE `GROUP_USER`
(
	`GRP_UID` VARCHAR(32) default '0' NOT NULL,
	`USR_UID` VARCHAR(32) default '0' NOT NULL,
	PRIMARY KEY (`GRP_UID`,`USR_UID`),
	KEY `indexForUsrUid`(`USR_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- HOLIDAY
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `HOLIDAY`;


CREATE TABLE `HOLIDAY`
(
	`HLD_UID` INTEGER  NOT NULL AUTO_INCREMENT,
	`HLD_DATE` VARCHAR(10) default '0000-00-00' NOT NULL,
	`HLD_DESCRIPTION` VARCHAR(200) default '' NOT NULL,
	PRIMARY KEY (`HLD_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- INPUT_DOCUMENT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `INPUT_DOCUMENT`;


CREATE TABLE `INPUT_DOCUMENT`
(
	`INP_DOC_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '0' NOT NULL,
	`INP_DOC_TITLE` MEDIUMTEXT  NOT NULL,
	`INP_DOC_DESCRIPTION` MEDIUMTEXT,
	`INP_DOC_FORM_NEEDED` VARCHAR(20) default 'REAL' NOT NULL,
	`INP_DOC_ORIGINAL` VARCHAR(20) default 'COPY' NOT NULL,
	`INP_DOC_PUBLISHED` VARCHAR(20) default 'PRIVATE' NOT NULL,
	`INP_DOC_VERSIONING` TINYINT default 0 NOT NULL,
	`INP_DOC_DESTINATION_PATH` MEDIUMTEXT,
	`INP_DOC_TAGS` MEDIUMTEXT,
	`INP_DOC_TYPE_FILE` VARCHAR(200) default '*.*',
	`INP_DOC_MAX_FILESIZE` INTEGER default 0 NOT NULL,
	`INP_DOC_MAX_FILESIZE_UNIT` VARCHAR(2) default 'KB' NOT NULL,
	PRIMARY KEY (`INP_DOC_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Documentation required';
#-----------------------------------------------------------------------------
#-- ISO_COUNTRY
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `ISO_COUNTRY`;


CREATE TABLE `ISO_COUNTRY`
(
	`IC_UID` VARCHAR(2) default '' NOT NULL,
	`IC_NAME` VARCHAR(255),
	`IC_SORT_ORDER` VARCHAR(255),
	PRIMARY KEY (`IC_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- ISO_LOCATION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `ISO_LOCATION`;


CREATE TABLE `ISO_LOCATION`
(
	`IC_UID` VARCHAR(2) default '' NOT NULL,
	`IL_UID` VARCHAR(5) default '' NOT NULL,
	`IL_NAME` VARCHAR(255),
	`IL_NORMAL_NAME` VARCHAR(255),
	`IS_UID` VARCHAR(4),
	PRIMARY KEY (`IC_UID`,`IL_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- ISO_SUBDIVISION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `ISO_SUBDIVISION`;


CREATE TABLE `ISO_SUBDIVISION`
(
	`IC_UID` VARCHAR(2) default '' NOT NULL,
	`IS_UID` VARCHAR(4) default '' NOT NULL,
	`IS_NAME` VARCHAR(255) default '' NOT NULL,
	PRIMARY KEY (`IC_UID`,`IS_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- LANGUAGE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LANGUAGE`;


CREATE TABLE `LANGUAGE`
(
	`LAN_ID` VARCHAR(4) default '' NOT NULL,
	`LAN_LOCATION` VARCHAR(4) default '' NOT NULL,
	`LAN_NAME` VARCHAR(30) default '' NOT NULL,
	`LAN_NATIVE_NAME` VARCHAR(30) default '' NOT NULL,
	`LAN_DIRECTION` CHAR(1) default 'L' NOT NULL,
	`LAN_WEIGHT` INTEGER default 0 NOT NULL,
	`LAN_ENABLED` CHAR(1) default '1' NOT NULL,
	`LAN_CALENDAR` VARCHAR(30) default 'GREGORIAN' NOT NULL,
	PRIMARY KEY (`LAN_ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- LEXICO
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LEXICO`;


CREATE TABLE `LEXICO`
(
	`LEX_TOPIC` VARCHAR(64) default '' NOT NULL,
	`LEX_KEY` VARCHAR(128) default '' NOT NULL,
	`LEX_VALUE` VARCHAR(128) default '' NOT NULL,
	`LEX_CAPTION` VARCHAR(128) default '' NOT NULL,
	PRIMARY KEY (`LEX_TOPIC`,`LEX_KEY`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='LEXICOS, una tabla que contiene tablas';
#-----------------------------------------------------------------------------
#-- OUTPUT_DOCUMENT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `OUTPUT_DOCUMENT`;


CREATE TABLE `OUTPUT_DOCUMENT`
(
	`OUT_DOC_UID` VARCHAR(32) default '' NOT NULL,
	`OUT_DOC_TITLE` MEDIUMTEXT  NOT NULL,
	`OUT_DOC_DESCRIPTION` MEDIUMTEXT,
	`OUT_DOC_FILENAME` MEDIUMTEXT,
	`OUT_DOC_TEMPLATE` MEDIUMTEXT,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`OUT_DOC_REPORT_GENERATOR` VARCHAR(10) default 'HTML2PDF' NOT NULL,
	`OUT_DOC_LANDSCAPE` TINYINT default 0 NOT NULL,
	`OUT_DOC_MEDIA` VARCHAR(10) default 'Letter' NOT NULL,
	`OUT_DOC_LEFT_MARGIN` INTEGER default 30,
	`OUT_DOC_RIGHT_MARGIN` INTEGER default 15,
	`OUT_DOC_TOP_MARGIN` INTEGER default 15,
	`OUT_DOC_BOTTOM_MARGIN` INTEGER default 15,
	`OUT_DOC_GENERATE` VARCHAR(10) default 'BOTH' NOT NULL,
	`OUT_DOC_TYPE` VARCHAR(32) default 'HTML' NOT NULL,
	`OUT_DOC_CURRENT_REVISION` INTEGER default 0,
	`OUT_DOC_FIELD_MAPPING` MEDIUMTEXT,
	`OUT_DOC_VERSIONING` TINYINT default 0 NOT NULL,
	`OUT_DOC_DESTINATION_PATH` MEDIUMTEXT,
	`OUT_DOC_TAGS` MEDIUMTEXT,
	`OUT_DOC_PDF_SECURITY_ENABLED` TINYINT default 0,
	`OUT_DOC_PDF_SECURITY_OPEN_PASSWORD` VARCHAR(32) default '',
	`OUT_DOC_PDF_SECURITY_OWNER_PASSWORD` VARCHAR(32) default '',
	`OUT_DOC_PDF_SECURITY_PERMISSIONS` VARCHAR(150) default '',
	`OUT_DOC_OPEN_TYPE` INTEGER default 1,
	PRIMARY KEY (`OUT_DOC_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- PROCESS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `PROCESS`;


CREATE TABLE `PROCESS`
(
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_ID` INTEGER  NOT NULL AUTO_INCREMENT,
	`PRO_TITLE` MEDIUMTEXT  NOT NULL,
	`PRO_DESCRIPTION` MEDIUMTEXT,
	`PRO_PARENT` VARCHAR(32) default '0' NOT NULL,
	`PRO_TIME` DOUBLE default 1 NOT NULL,
	`PRO_TIMEUNIT` VARCHAR(20) default 'DAYS' NOT NULL,
	`PRO_STATUS` VARCHAR(20) default 'ACTIVE' NOT NULL,
	`PRO_TYPE_DAY` CHAR(1) default '0' NOT NULL,
	`PRO_TYPE` VARCHAR(256) default 'NORMAL' NOT NULL,
	`PRO_ASSIGNMENT` VARCHAR(20) default 'FALSE' NOT NULL,
	`PRO_SHOW_MAP` TINYINT default 1 NOT NULL,
	`PRO_SHOW_MESSAGE` TINYINT default 1 NOT NULL,
	`PRO_SUBPROCESS` TINYINT default 0 NOT NULL,
	`PRO_TRI_CREATE` VARCHAR(32) default '' NOT NULL,
	`PRO_TRI_OPEN` VARCHAR(32) default '' NOT NULL,
	`PRO_TRI_DELETED` VARCHAR(32) default '' NOT NULL,
	`PRO_TRI_CANCELED` VARCHAR(32) default '' NOT NULL,
	`PRO_TRI_PAUSED` VARCHAR(32) default '' NOT NULL,
	`PRO_TRI_REASSIGNED` VARCHAR(32) default '' NOT NULL,
	`PRO_TRI_UNPAUSED` VARCHAR(32) default '' NOT NULL,
	`PRO_TYPE_PROCESS` VARCHAR(32) default 'PUBLIC' NOT NULL,
	`PRO_SHOW_DELEGATE` TINYINT default 1 NOT NULL,
	`PRO_SHOW_DYNAFORM` TINYINT default 0 NOT NULL,
	`PRO_CATEGORY` VARCHAR(48) default '' NOT NULL,
	`PRO_SUB_CATEGORY` VARCHAR(48) default '' NOT NULL,
	`PRO_INDUSTRY` INTEGER default 1 NOT NULL,
	`PRO_UPDATE_DATE` DATETIME,
	`PRO_CREATE_DATE` DATETIME  NOT NULL,
	`PRO_CREATE_USER` VARCHAR(32) default '' NOT NULL,
	`PRO_HEIGHT` INTEGER default 5000 NOT NULL,
	`PRO_WIDTH` INTEGER default 10000 NOT NULL,
	`PRO_TITLE_X` INTEGER default 0 NOT NULL,
	`PRO_TITLE_Y` INTEGER default 6 NOT NULL,
	`PRO_DEBUG` INTEGER default 0 NOT NULL,
	`PRO_DYNAFORMS` MEDIUMTEXT,
	`PRO_DERIVATION_SCREEN_TPL` VARCHAR(128) default '',
	`PRO_COST` DECIMAL(7,2) default 0,
	`PRO_UNIT_COST` VARCHAR(50) default '',
	`PRO_ITEE` INTEGER default 0 NOT NULL,
	`PRO_ACTION_DONE` MEDIUMTEXT,
	PRIMARY KEY (`PRO_UID`),
	UNIQUE KEY `INDEX_PRO_ID` (`PRO_ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Store process Information';
#-----------------------------------------------------------------------------
#-- PROCESS_OWNER
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `PROCESS_OWNER`;


CREATE TABLE `PROCESS_OWNER`
(
	`OWN_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	PRIMARY KEY (`OWN_UID`,`PRO_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- REPORT_TABLE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `REPORT_TABLE`;


CREATE TABLE `REPORT_TABLE`
(
	`REP_TAB_UID` VARCHAR(32) default '' NOT NULL,
	`REP_TAB_TITLE` MEDIUMTEXT  NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`REP_TAB_NAME` VARCHAR(100) default '' NOT NULL,
	`REP_TAB_TYPE` VARCHAR(6) default '' NOT NULL,
	`REP_TAB_GRID` VARCHAR(150) default '',
	`REP_TAB_CONNECTION` VARCHAR(32) default '' NOT NULL,
	`REP_TAB_CREATE_DATE` DATETIME  NOT NULL,
	`REP_TAB_STATUS` CHAR(8) default 'ACTIVE' NOT NULL,
	PRIMARY KEY (`REP_TAB_UID`),
	KEY `indexProcessStatus`(`PRO_UID`, `REP_TAB_STATUS`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- REPORT_VAR
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `REPORT_VAR`;


CREATE TABLE `REPORT_VAR`
(
	`REP_VAR_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`REP_TAB_UID` VARCHAR(32) default '' NOT NULL,
	`REP_VAR_NAME` VARCHAR(255) default '' NOT NULL,
	`REP_VAR_TYPE` VARCHAR(20) default '' NOT NULL,
	PRIMARY KEY (`REP_VAR_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- ROUTE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `ROUTE`;


CREATE TABLE `ROUTE`
(
	`ROU_UID` VARCHAR(32) default '' NOT NULL,
	`ROU_PARENT` VARCHAR(32) default '0' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`ROU_NEXT_TASK` VARCHAR(32) default '0' NOT NULL,
	`ROU_CASE` INTEGER default 0 NOT NULL,
	`ROU_TYPE` VARCHAR(25) default 'SEQUENTIAL' NOT NULL,
	`ROU_DEFAULT` INTEGER default 0 NOT NULL,
	`ROU_CONDITION` VARCHAR(512) default '' NOT NULL,
	`ROU_TO_LAST_USER` VARCHAR(20) default 'FALSE' NOT NULL,
	`ROU_OPTIONAL` VARCHAR(20) default 'FALSE' NOT NULL,
	`ROU_SEND_EMAIL` VARCHAR(20) default 'TRUE' NOT NULL,
	`ROU_SOURCEANCHOR` INTEGER default 1,
	`ROU_TARGETANCHOR` INTEGER default 0,
	`ROU_TO_PORT` INTEGER default 1 NOT NULL,
	`ROU_FROM_PORT` INTEGER default 2 NOT NULL,
	`ROU_EVN_UID` VARCHAR(32) default '' NOT NULL,
	`GAT_UID` VARCHAR(32) default '' NOT NULL,
	PRIMARY KEY (`ROU_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Differents flows for a flow in business process';
#-----------------------------------------------------------------------------
#-- STEP
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `STEP`;


CREATE TABLE `STEP`
(
	`STEP_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '0' NOT NULL,
	`TAS_UID` VARCHAR(32) default '0' NOT NULL,
	`STEP_TYPE_OBJ` VARCHAR(20) default 'DYNAFORM' NOT NULL,
	`STEP_UID_OBJ` VARCHAR(32) default '0' NOT NULL,
	`STEP_CONDITION` MEDIUMTEXT  NOT NULL,
	`STEP_POSITION` INTEGER default 0 NOT NULL,
	`STEP_MODE` VARCHAR(10) default 'EDIT',
	PRIMARY KEY (`STEP_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- STEP_TRIGGER
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `STEP_TRIGGER`;


CREATE TABLE `STEP_TRIGGER`
(
	`STEP_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`TRI_UID` VARCHAR(32) default '' NOT NULL,
	`ST_TYPE` VARCHAR(20) default '' NOT NULL,
	`ST_CONDITION` VARCHAR(255) default '' NOT NULL,
	`ST_POSITION` INTEGER default 0 NOT NULL,
	PRIMARY KEY (`STEP_UID`,`TAS_UID`,`TRI_UID`,`ST_TYPE`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- SWIMLANES_ELEMENTS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `SWIMLANES_ELEMENTS`;


CREATE TABLE `SWIMLANES_ELEMENTS`
(
	`SWI_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`SWI_TYPE` VARCHAR(20) default 'LINE' NOT NULL,
	`SWI_X` INTEGER default 0 NOT NULL,
	`SWI_Y` INTEGER default 0 NOT NULL,
	`SWI_WIDTH` INTEGER default 0 NOT NULL,
	`SWI_HEIGHT` INTEGER default 0 NOT NULL,
	`SWI_NEXT_UID` VARCHAR(32) default '',
	PRIMARY KEY (`SWI_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- TASK
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `TASK`;


CREATE TABLE `TASK`
(
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_ID` INTEGER  NOT NULL AUTO_INCREMENT,
	`TAS_TITLE` MEDIUMTEXT  NOT NULL,
	`TAS_DESCRIPTION` MEDIUMTEXT,
	`TAS_DEF_TITLE` MEDIUMTEXT,
	`TAS_DEF_SUBJECT_MESSAGE` MEDIUMTEXT,
	`TAS_DEF_PROC_CODE` MEDIUMTEXT,
	`TAS_DEF_MESSAGE` MEDIUMTEXT,
	`TAS_DEF_DESCRIPTION` MEDIUMTEXT,
	`TAS_TYPE` VARCHAR(50) default 'NORMAL' NOT NULL,
	`TAS_DURATION` DOUBLE default 0 NOT NULL,
	`TAS_DELAY_TYPE` VARCHAR(30) default '' NOT NULL,
	`TAS_TEMPORIZER` DOUBLE default 0 NOT NULL,
	`TAS_TYPE_DAY` CHAR(1) default '1' NOT NULL,
	`TAS_TIMEUNIT` VARCHAR(20) default 'DAYS' NOT NULL,
	`TAS_ALERT` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_PRIORITY_VARIABLE` VARCHAR(100) default '' NOT NULL,
	`TAS_ASSIGN_TYPE` VARCHAR(30) default 'BALANCED' NOT NULL,
	`TAS_ASSIGN_VARIABLE` VARCHAR(100) default '@@SYS_NEXT_USER_TO_BE_ASSIGNED' NOT NULL,
	`TAS_GROUP_VARIABLE` VARCHAR(100),
	`TAS_MI_INSTANCE_VARIABLE` VARCHAR(100) default '@@SYS_VAR_TOTAL_INSTANCE' NOT NULL,
	`TAS_MI_COMPLETE_VARIABLE` VARCHAR(100) default '@@SYS_VAR_TOTAL_INSTANCES_COMPLETE' NOT NULL,
	`TAS_ASSIGN_LOCATION` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_ASSIGN_LOCATION_ADHOC` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_TRANSFER_FLY` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_LAST_ASSIGNED` VARCHAR(32) default '0' NOT NULL,
	`TAS_USER` VARCHAR(32) default '0' NOT NULL,
	`TAS_CAN_UPLOAD` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_VIEW_UPLOAD` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_VIEW_ADDITIONAL_DOCUMENTATION` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_CAN_CANCEL` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_OWNER_APP` VARCHAR(32) default '' NOT NULL,
	`STG_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_CAN_PAUSE` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_CAN_SEND_MESSAGE` VARCHAR(20) default 'TRUE' NOT NULL,
	`TAS_CAN_DELETE_DOCS` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_SELF_SERVICE` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_START` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_TO_LAST_USER` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_SEND_LAST_EMAIL` VARCHAR(20) default 'TRUE' NOT NULL,
	`TAS_DERIVATION` VARCHAR(100) default 'NORMAL' NOT NULL,
	`TAS_POSX` INTEGER default 0 NOT NULL,
	`TAS_POSY` INTEGER default 0 NOT NULL,
	`TAS_WIDTH` INTEGER default 110 NOT NULL,
	`TAS_HEIGHT` INTEGER default 60 NOT NULL,
	`TAS_COLOR` VARCHAR(32) default '' NOT NULL,
	`TAS_EVN_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_BOUNDARY` VARCHAR(32) default '' NOT NULL,
	`TAS_DERIVATION_SCREEN_TPL` VARCHAR(128) default '',
	`TAS_SELFSERVICE_TIMEOUT` INTEGER default 0,
	`TAS_SELFSERVICE_TIME` INTEGER default 0,
	`TAS_SELFSERVICE_TIME_UNIT` VARCHAR(15) default '',
	`TAS_SELFSERVICE_TRIGGER_UID` VARCHAR(32) default '',
	`TAS_SELFSERVICE_EXECUTION` VARCHAR(15) default 'EVERY_TIME',
	`TAS_NOT_EMAIL_FROM_FORMAT` INTEGER default 0,
	`TAS_OFFLINE` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_EMAIL_SERVER_UID` VARCHAR(32) default '',
	`TAS_AUTO_ROOT` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_RECEIVE_SERVER_UID` VARCHAR(32) default '',
	`TAS_RECEIVE_LAST_EMAIL` VARCHAR(20) default 'FALSE' NOT NULL,
	`TAS_RECEIVE_EMAIL_FROM_FORMAT` INTEGER default 0,
	`TAS_RECEIVE_MESSAGE_TYPE` VARCHAR(20) default 'text' NOT NULL,
	`TAS_RECEIVE_MESSAGE_TEMPLATE` VARCHAR(100) default 'alert_message.html' NOT NULL,
	`TAS_RECEIVE_SUBJECT_MESSAGE` MEDIUMTEXT,
	`TAS_RECEIVE_MESSAGE` MEDIUMTEXT,
	PRIMARY KEY (`TAS_UID`),
	UNIQUE KEY `INDEX_TAS_ID` (`TAS_ID`),
	KEY `indexTasUid`(`TAS_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Task of workflow';
#-----------------------------------------------------------------------------
#-- TASK_USER
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `TASK_USER`;


CREATE TABLE `TASK_USER`
(
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`TU_TYPE` INTEGER default 1 NOT NULL,
	`TU_RELATION` INTEGER default 0 NOT NULL,
	PRIMARY KEY (`TAS_UID`,`USR_UID`,`TU_TYPE`,`TU_RELATION`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- TRANSLATION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `TRANSLATION`;


CREATE TABLE `TRANSLATION`
(
	`TRN_CATEGORY` VARCHAR(100) default '' NOT NULL,
	`TRN_ID` VARCHAR(100) default '' NOT NULL,
	`TRN_LANG` VARCHAR(10) default 'en' NOT NULL,
	`TRN_VALUE` MEDIUMTEXT  NOT NULL,
	`TRN_UPDATE_DATE` DATE,
	PRIMARY KEY (`TRN_CATEGORY`,`TRN_ID`,`TRN_LANG`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- TRIGGERS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `TRIGGERS`;


CREATE TABLE `TRIGGERS`
(
	`TRI_UID` VARCHAR(32) default '' NOT NULL,
	`TRI_TITLE` MEDIUMTEXT  NOT NULL,
	`TRI_DESCRIPTION` MEDIUMTEXT,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`TRI_TYPE` VARCHAR(20) default 'SCRIPT' NOT NULL,
	`TRI_WEBBOT` MEDIUMTEXT  NOT NULL,
	`TRI_PARAM` MEDIUMTEXT,
	PRIMARY KEY (`TRI_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- USERS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `USERS`;


CREATE TABLE `USERS`
(
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`USR_ID` INTEGER  NOT NULL AUTO_INCREMENT,
	`USR_USERNAME` VARCHAR(100) default '' NOT NULL,
	`USR_PASSWORD` VARCHAR(128) default '' NOT NULL,
	`USR_FIRSTNAME` VARCHAR(50) default '' NOT NULL,
	`USR_LASTNAME` VARCHAR(50) default '' NOT NULL,
	`USR_EMAIL` VARCHAR(100) default '' NOT NULL,
	`USR_DUE_DATE` DATE  NOT NULL,
	`USR_CREATE_DATE` DATETIME  NOT NULL,
	`USR_UPDATE_DATE` DATETIME  NOT NULL,
	`USR_STATUS` VARCHAR(32) default 'ACTIVE' NOT NULL,
	`USR_COUNTRY` VARCHAR(3) default '' NOT NULL,
	`USR_CITY` VARCHAR(3) default '' NOT NULL,
	`USR_LOCATION` VARCHAR(3) default '' NOT NULL,
	`USR_ADDRESS` VARCHAR(255) default '' NOT NULL,
	`USR_PHONE` VARCHAR(24) default '' NOT NULL,
	`USR_FAX` VARCHAR(24) default '' NOT NULL,
	`USR_CELLULAR` VARCHAR(24) default '' NOT NULL,
	`USR_ZIP_CODE` VARCHAR(16) default '' NOT NULL,
	`DEP_UID` VARCHAR(32) default '' NOT NULL,
	`USR_POSITION` VARCHAR(100) default '' NOT NULL,
	`USR_RESUME` VARCHAR(100) default '' NOT NULL,
	`USR_BIRTHDAY` DATE,
	`USR_ROLE` VARCHAR(32) default 'PROCESSMAKER_ADMIN',
	`USR_REPORTS_TO` VARCHAR(32) default '',
	`USR_REPLACED_BY` VARCHAR(32) default '',
	`USR_UX` VARCHAR(128) default 'NORMAL',
	`USR_COST_BY_HOUR` DECIMAL(7,2) default 0,
	`USR_UNIT_COST` VARCHAR(50) default '',
	`USR_PMDRIVE_FOLDER_UID` VARCHAR(32) default '',
	`USR_BOOKMARK_START_CASES` MEDIUMTEXT,
	`USR_TIME_ZONE` VARCHAR(100) default '',
	`USR_DEFAULT_LANG` VARCHAR(10) default '',
	`USR_LAST_LOGIN` DATETIME,
	PRIMARY KEY (`USR_UID`),
	UNIQUE KEY `INDEX_USR_ID` (`USR_ID`),
	KEY `indexUsrUid`(`USR_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Users';
#-----------------------------------------------------------------------------
#-- APP_THREAD
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_THREAD`;


CREATE TABLE `APP_THREAD`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`APP_THREAD_INDEX` INTEGER default 0 NOT NULL,
	`APP_THREAD_PARENT` INTEGER default 0 NOT NULL,
	`APP_THREAD_STATUS` VARCHAR(32) default 'OPEN' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	PRIMARY KEY (`APP_UID`,`APP_THREAD_INDEX`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='APP_THREAD';
#-----------------------------------------------------------------------------
#-- APP_DELAY
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_DELAY`;


CREATE TABLE `APP_DELAY`
(
	`APP_DELAY_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '0' NOT NULL,
	`APP_UID` VARCHAR(32) default '0' NOT NULL,
	`APP_NUMBER` INTEGER default 0,
	`APP_THREAD_INDEX` INTEGER default 0 NOT NULL,
	`APP_DEL_INDEX` INTEGER default 0 NOT NULL,
	`APP_TYPE` VARCHAR(20) default '0' NOT NULL,
	`APP_STATUS` VARCHAR(20) default '0' NOT NULL,
	`APP_NEXT_TASK` VARCHAR(32) default '0',
	`APP_DELEGATION_USER` VARCHAR(32) default '0',
	`APP_ENABLE_ACTION_USER` VARCHAR(32) default '0' NOT NULL,
	`APP_ENABLE_ACTION_DATE` DATETIME  NOT NULL,
	`APP_DISABLE_ACTION_USER` VARCHAR(32) default '0',
	`APP_DISABLE_ACTION_DATE` DATETIME,
	`APP_AUTOMATIC_DISABLED_DATE` DATETIME,
	`APP_DELEGATION_USER_ID` INTEGER default 0,
	`PRO_ID` INTEGER default 0,
	PRIMARY KEY (`APP_DELAY_UID`),
	KEY `INDEX_APP_NUMBER`(`APP_NUMBER`),
	KEY `INDEX_USR_ID`(`APP_DELEGATION_USER_ID`),
	KEY `INDEX_PRO_ID`(`PRO_ID`),
	KEY `indexAppDelay`(`PRO_UID`, `APP_UID`, `APP_THREAD_INDEX`, `APP_DEL_INDEX`, `APP_NEXT_TASK`, `APP_DELEGATION_USER`, `APP_DISABLE_ACTION_USER`),
	KEY `indexAppUid`(`APP_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='APP_DELAY';
#-----------------------------------------------------------------------------
#-- PROCESS_USER
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `PROCESS_USER`;


CREATE TABLE `PROCESS_USER`
(
	`PU_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`PU_TYPE` VARCHAR(20) default '' NOT NULL,
	PRIMARY KEY (`PU_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- SESSION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `SESSION`;


CREATE TABLE `SESSION`
(
	`SES_UID` VARCHAR(32) default '' NOT NULL,
	`SES_STATUS` VARCHAR(16) default 'ACTIVE' NOT NULL,
	`USR_UID` VARCHAR(32) default 'ACTIVE' NOT NULL,
	`SES_REMOTE_IP` VARCHAR(32) default '0.0.0.0' NOT NULL,
	`SES_INIT_DATE` VARCHAR(19) default '' NOT NULL,
	`SES_DUE_DATE` VARCHAR(19) default '' NOT NULL,
	`SES_END_DATE` VARCHAR(19) default '' NOT NULL,
	PRIMARY KEY (`SES_UID`),
	KEY `indexSession`(`SES_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='SESSION';
#-----------------------------------------------------------------------------
#-- DB_SOURCE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `DB_SOURCE`;


CREATE TABLE `DB_SOURCE`
(
	`DBS_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '0' NOT NULL,
	`DBS_TYPE` VARCHAR(8) default '0' NOT NULL,
	`DBS_SERVER` VARCHAR(100) default '0' NOT NULL,
	`DBS_DATABASE_NAME` VARCHAR(100) default '0' NOT NULL,
	`DBS_USERNAME` VARCHAR(32) default '0' NOT NULL,
	`DBS_PASSWORD` VARCHAR(256) default '',
	`DBS_PORT` INTEGER default 0,
	`DBS_ENCODE` VARCHAR(32) default '',
	`DBS_CONNECTION_TYPE` VARCHAR(32) default 'NORMAL',
	`DBS_TNS` VARCHAR(256) default '',
	`DBS_DESCRIPTION` MEDIUMTEXT,
	PRIMARY KEY (`DBS_UID`,`PRO_UID`),
	KEY `indexDBSource`(`PRO_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='DB_SOURCE';
#-----------------------------------------------------------------------------
#-- STEP_SUPERVISOR
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `STEP_SUPERVISOR`;


CREATE TABLE `STEP_SUPERVISOR`
(
	`STEP_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '0' NOT NULL,
	`STEP_TYPE_OBJ` VARCHAR(20) default 'DYNAFORM' NOT NULL,
	`STEP_UID_OBJ` VARCHAR(32) default '0' NOT NULL,
	`STEP_POSITION` INTEGER default 0 NOT NULL,
	PRIMARY KEY (`STEP_UID`),
	KEY `indexStepSupervisor`(`PRO_UID`, `STEP_TYPE_OBJ`, `STEP_UID_OBJ`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='STEP_SUPERVISOR';
#-----------------------------------------------------------------------------
#-- OBJECT_PERMISSION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `OBJECT_PERMISSION`;


CREATE TABLE `OBJECT_PERMISSION`
(
	`OP_UID` VARCHAR(32) default '0' NOT NULL,
	`PRO_UID` VARCHAR(32) default '0' NOT NULL,
	`TAS_UID` VARCHAR(32) default '0' NOT NULL,
	`USR_UID` VARCHAR(32) default '0' NOT NULL,
	`OP_USER_RELATION` INTEGER default 0 NOT NULL,
	`OP_TASK_SOURCE` VARCHAR(32) default '0',
	`OP_PARTICIPATE` INTEGER default 0 NOT NULL,
	`OP_OBJ_TYPE` VARCHAR(15) default '0' NOT NULL,
	`OP_OBJ_UID` VARCHAR(32) default '0' NOT NULL,
	`OP_ACTION` VARCHAR(10) default '0' NOT NULL,
	`OP_CASE_STATUS` VARCHAR(10) default '0',
	PRIMARY KEY (`OP_UID`),
	KEY `indexObjctPermission`(`PRO_UID`, `TAS_UID`, `USR_UID`, `OP_TASK_SOURCE`, `OP_OBJ_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='OBJECT_PERMISSION';
#-----------------------------------------------------------------------------
#-- CASE_TRACKER
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `CASE_TRACKER`;


CREATE TABLE `CASE_TRACKER`
(
	`PRO_UID` VARCHAR(32) default '0' NOT NULL,
	`CT_MAP_TYPE` VARCHAR(10) default '0' NOT NULL,
	`CT_DERIVATION_HISTORY` INTEGER default 0 NOT NULL,
	`CT_MESSAGE_HISTORY` INTEGER default 0 NOT NULL,
	PRIMARY KEY (`PRO_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='CASE_TRACKER';
#-----------------------------------------------------------------------------
#-- CASE_TRACKER_OBJECT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `CASE_TRACKER_OBJECT`;


CREATE TABLE `CASE_TRACKER_OBJECT`
(
	`CTO_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '0' NOT NULL,
	`CTO_TYPE_OBJ` VARCHAR(20) default 'DYNAFORM' NOT NULL,
	`CTO_UID_OBJ` VARCHAR(32) default '0' NOT NULL,
	`CTO_CONDITION` MEDIUMTEXT  NOT NULL,
	`CTO_POSITION` INTEGER default 0 NOT NULL,
	PRIMARY KEY (`CTO_UID`),
	KEY `indexCaseTrackerObject`(`PRO_UID`, `CTO_UID_OBJ`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- CASE_CONSOLIDATED
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `CASE_CONSOLIDATED`;


CREATE TABLE `CASE_CONSOLIDATED`
(
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`DYN_UID` VARCHAR(32) default '' NOT NULL,
	`REP_TAB_UID` VARCHAR(32) default '' NOT NULL,
	`CON_STATUS` VARCHAR(20) default 'ACTIVE' NOT NULL,
	PRIMARY KEY (`TAS_UID`),
	KEY `indexConStatus`(`CON_STATUS`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- STAGE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `STAGE`;


CREATE TABLE `STAGE`
(
	`STG_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`STG_POSX` INTEGER default 0 NOT NULL,
	`STG_POSY` INTEGER default 0 NOT NULL,
	`STG_INDEX` INTEGER default 0 NOT NULL,
	PRIMARY KEY (`STG_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- SUB_PROCESS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `SUB_PROCESS`;


CREATE TABLE `SUB_PROCESS`
(
	`SP_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_PARENT` VARCHAR(32) default '' NOT NULL,
	`TAS_PARENT` VARCHAR(32) default '' NOT NULL,
	`SP_TYPE` VARCHAR(20) default '' NOT NULL,
	`SP_SYNCHRONOUS` INTEGER default 0 NOT NULL,
	`SP_SYNCHRONOUS_TYPE` VARCHAR(20) default '' NOT NULL,
	`SP_SYNCHRONOUS_WAIT` INTEGER default 0 NOT NULL,
	`SP_VARIABLES_OUT` MEDIUMTEXT  NOT NULL,
	`SP_VARIABLES_IN` MEDIUMTEXT,
	`SP_GRID_IN` VARCHAR(50) default '' NOT NULL,
	PRIMARY KEY (`SP_UID`),
	KEY `indexSubProcess`(`PRO_UID`, `PRO_PARENT`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- SUB_APPLICATION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `SUB_APPLICATION`;


CREATE TABLE `SUB_APPLICATION`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`APP_PARENT` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX_PARENT` INTEGER default 0 NOT NULL,
	`DEL_THREAD_PARENT` INTEGER default 0 NOT NULL,
	`SA_STATUS` VARCHAR(32) default '' NOT NULL,
	`SA_VALUES_OUT` MEDIUMTEXT  NOT NULL,
	`SA_VALUES_IN` MEDIUMTEXT,
	`SA_INIT_DATE` DATETIME,
	`SA_FINISH_DATE` DATETIME,
	PRIMARY KEY (`APP_UID`,`APP_PARENT`,`DEL_INDEX_PARENT`,`DEL_THREAD_PARENT`),
	KEY `indexParent`(`APP_PARENT`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- LOGIN_LOG
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LOGIN_LOG`;


CREATE TABLE `LOGIN_LOG`
(
	`LOG_ID` INTEGER  NOT NULL AUTO_INCREMENT,
	`LOG_UID` VARCHAR(32) default '' NOT NULL,
	`LOG_STATUS` VARCHAR(100) default '' NOT NULL,
	`LOG_IP` VARCHAR(15) default '' NOT NULL,
	`LOG_SID` VARCHAR(100) default '' NOT NULL,
	`LOG_INIT_DATE` DATETIME,
	`LOG_END_DATE` DATETIME,
	`LOG_CLIENT_HOSTNAME` VARCHAR(100) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	PRIMARY KEY (`LOG_ID`),
	KEY `indexLoginLogSelect`(`LOG_SID`, `USR_UID`, `LOG_STATUS`, `LOG_END_DATE`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- USERS_PROPERTIES
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `USERS_PROPERTIES`;


CREATE TABLE `USERS_PROPERTIES`
(
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`USR_LAST_UPDATE_DATE` DATETIME,
	`USR_LOGGED_NEXT_TIME` INTEGER default 0,
	`USR_PASSWORD_HISTORY` MEDIUMTEXT,
	`USR_SETTING_DESIGNER` MEDIUMTEXT,
	`PMDYNAFORM_FIRST_TIME` CHAR(1) default '0',
	PRIMARY KEY (`USR_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- ADDITIONAL_TABLES
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `ADDITIONAL_TABLES`;


CREATE TABLE `ADDITIONAL_TABLES`
(
	`ADD_TAB_UID` VARCHAR(32) default '' NOT NULL,
	`ADD_TAB_ID` INTEGER  NOT NULL AUTO_INCREMENT,
	`ADD_TAB_NAME` VARCHAR(60) default '' NOT NULL,
	`ADD_TAB_DESCRIPTION` MEDIUMTEXT,
	`ADD_TAB_PLG_UID` VARCHAR(32) default '',
	`DBS_UID` VARCHAR(32) default '',
	`PRO_UID` VARCHAR(32) default '',
	`PRO_ID` INTEGER,
	`ADD_TAB_TYPE` VARCHAR(32) default '',
	`ADD_TAB_GRID` VARCHAR(256) default '',
	`ADD_TAB_TAG` VARCHAR(256) default '',
	PRIMARY KEY (`ADD_TAB_UID`),
  UNIQUE KEY `INDEX_ADD_TAB_ID` (`ADD_TAB_ID`),
	KEY `indexAdditionalProcess`(`PRO_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- FIELDS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `FIELDS`;


CREATE TABLE `FIELDS`
(
	`FLD_UID` VARCHAR(32) default '' NOT NULL,
	`FLD_ID` INTEGER  NOT NULL AUTO_INCREMENT,
	`ADD_TAB_UID` VARCHAR(32) default '' NOT NULL,
	`ADD_TAB_ID` INTEGER,
	`FLD_NAME` VARCHAR(60) default '' NOT NULL,
	`FLD_DYN_NAME` VARCHAR(128) default '',
	`FLD_DYN_UID` VARCHAR(128) default '',
	`FLD_FILTER` TINYINT default 0,
	`VAR_ID` INTEGER NOT NULL,
	PRIMARY KEY (`FLD_UID`),
  UNIQUE KEY `INDEX_FLD_ID` (`FLD_ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- SHADOW_TABLE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `SHADOW_TABLE`;


CREATE TABLE `SHADOW_TABLE`
(
	`SHD_UID` VARCHAR(32) default '' NOT NULL,
	`ADD_TAB_UID` VARCHAR(32) default '' NOT NULL,
	`SHD_ACTION` VARCHAR(10) default '' NOT NULL,
	`SHD_DETAILS` MEDIUMTEXT  NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`SHD_DATE` DATETIME,
	PRIMARY KEY (`SHD_UID`),
	KEY `indexShadowTable`(`SHD_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- EVENT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `EVENT`;


CREATE TABLE `EVENT`
(
	`EVN_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`EVN_STATUS` VARCHAR(16) default 'OPEN' NOT NULL,
	`EVN_WHEN_OCCURS` VARCHAR(32) default 'SINGLE',
	`EVN_RELATED_TO` VARCHAR(16) default 'SINGLE',
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`EVN_TAS_UID_FROM` VARCHAR(32) default '',
	`EVN_TAS_UID_TO` VARCHAR(32) default '',
	`EVN_TAS_ESTIMATED_DURATION` DOUBLE default 0,
	`EVN_TIME_UNIT` VARCHAR(10) default 'DAYS' NOT NULL,
	`EVN_WHEN` DOUBLE default 0 NOT NULL,
	`EVN_MAX_ATTEMPTS` TINYINT default 3 NOT NULL,
	`EVN_ACTION` VARCHAR(50) default '' NOT NULL,
	`EVN_CONDITIONS` MEDIUMTEXT,
	`EVN_ACTION_PARAMETERS` MEDIUMTEXT,
	`TRI_UID` VARCHAR(32) default '',
	`EVN_POSX` INTEGER default 0 NOT NULL,
	`EVN_POSY` INTEGER default 0 NOT NULL,
	`EVN_TYPE` VARCHAR(32) default '',
	`TAS_EVN_UID` VARCHAR(32) default '',
	PRIMARY KEY (`EVN_UID`),
	KEY `indexEventTable`(`EVN_UID`),
	KEY `indexStatusActionProcess`(`EVN_STATUS`, `EVN_ACTION`, `PRO_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- GATEWAY
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `GATEWAY`;


CREATE TABLE `GATEWAY`
(
	`GAT_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`GAT_NEXT_TASK` VARCHAR(32) default '' NOT NULL,
	`GAT_X` INTEGER default 0 NOT NULL,
	`GAT_Y` INTEGER default 0 NOT NULL,
	`GAT_TYPE` VARCHAR(32) default '' NOT NULL,
	PRIMARY KEY (`GAT_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- APP_EVENT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_EVENT`;


CREATE TABLE `APP_EVENT`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`EVN_UID` VARCHAR(32) default '' NOT NULL,
	`APP_EVN_ACTION_DATE` DATETIME  NOT NULL,
	`APP_EVN_ATTEMPTS` TINYINT default 0 NOT NULL,
	`APP_EVN_LAST_EXECUTION_DATE` DATETIME,
	`APP_EVN_STATUS` VARCHAR(32) default 'OPEN' NOT NULL,
	PRIMARY KEY (`APP_UID`,`DEL_INDEX`,`EVN_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- APP_CACHE_VIEW
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_CACHE_VIEW`;


CREATE TABLE `APP_CACHE_VIEW`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`DEL_LAST_INDEX` INTEGER default 0 NOT NULL,
	`APP_NUMBER` INTEGER default 0 NOT NULL,
	`APP_STATUS` VARCHAR(32) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`PREVIOUS_USR_UID` VARCHAR(32) default '',
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_DELEGATE_DATE` DATETIME  NOT NULL,
	`DEL_INIT_DATE` DATETIME,
	`DEL_FINISH_DATE` DATETIME,
	`DEL_TASK_DUE_DATE` DATETIME,
	`DEL_RISK_DATE` DATETIME,
	`DEL_THREAD_STATUS` VARCHAR(32) default 'OPEN',
	`APP_THREAD_STATUS` VARCHAR(32) default 'OPEN',
	`APP_TITLE` VARCHAR(255) default '' NOT NULL,
	`APP_PRO_TITLE` VARCHAR(255) default '' NOT NULL,
	`APP_TAS_TITLE` VARCHAR(255) default '' NOT NULL,
	`APP_CURRENT_USER` VARCHAR(128) default '',
	`APP_DEL_PREVIOUS_USER` VARCHAR(128) default '',
	`DEL_PRIORITY` VARCHAR(32) default '3' NOT NULL,
	`DEL_DURATION` DOUBLE default 0,
	`DEL_QUEUE_DURATION` DOUBLE default 0,
	`DEL_DELAY_DURATION` DOUBLE default 0,
	`DEL_STARTED` TINYINT default 0 NOT NULL,
	`DEL_FINISHED` TINYINT default 0 NOT NULL,
	`DEL_DELAYED` TINYINT default 0 NOT NULL,
	`APP_CREATE_DATE` DATETIME  NOT NULL,
	`APP_FINISH_DATE` DATETIME,
	`APP_UPDATE_DATE` DATETIME  NOT NULL,
	`APP_OVERDUE_PERCENTAGE` DOUBLE  NOT NULL,
	PRIMARY KEY (`APP_UID`,`DEL_INDEX`),
	KEY `indexUsrUidThreadStatusAppStatus`(`USR_UID`, `DEL_THREAD_STATUS`, `APP_STATUS`),
	KEY `indexAppUid`(`APP_UID`),
	KEY `indexTasUid`(`TAS_UID`),
	KEY `indexUsrUid`(`USR_UID`),
	KEY `indexPrevUsrUid`(`PREVIOUS_USR_UID`),
	KEY `indexProUid`(`PRO_UID`),
	KEY `indexAppNumber`(`APP_NUMBER`),
	KEY `protitle`(`APP_PRO_TITLE`),
	KEY `appupdatedate`(`APP_UPDATE_DATE`),
	KEY `tastitle`(`APP_TAS_TITLE`),
	KEY `taskUid`(`TAS_UID`),
	KEY `indexAppUser`(`USR_UID`, `APP_STATUS`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Application cache view';
#-----------------------------------------------------------------------------
#-- DIM_TIME_DELEGATE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `DIM_TIME_DELEGATE`;


CREATE TABLE `DIM_TIME_DELEGATE`
(
	`TIME_ID` VARCHAR(10) default '' NOT NULL,
	`MONTH_ID` INTEGER default 0 NOT NULL,
	`QTR_ID` INTEGER default 0 NOT NULL,
	`YEAR_ID` INTEGER default 0 NOT NULL,
	`MONTH_NAME` VARCHAR(3) default '0' NOT NULL,
	`MONTH_DESC` VARCHAR(9) default '' NOT NULL,
	`QTR_NAME` VARCHAR(4) default '' NOT NULL,
	`QTR_DESC` VARCHAR(9) default '' NOT NULL,
	PRIMARY KEY (`TIME_ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='The application';
#-----------------------------------------------------------------------------
#-- DIM_TIME_COMPLETE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `DIM_TIME_COMPLETE`;


CREATE TABLE `DIM_TIME_COMPLETE`
(
	`TIME_ID` VARCHAR(10) default '' NOT NULL,
	`MONTH_ID` INTEGER default 0 NOT NULL,
	`QTR_ID` INTEGER default 0 NOT NULL,
	`YEAR_ID` INTEGER default 0 NOT NULL,
	`MONTH_NAME` VARCHAR(3) default '0' NOT NULL,
	`MONTH_DESC` VARCHAR(9) default '' NOT NULL,
	`QTR_NAME` VARCHAR(4) default '' NOT NULL,
	`QTR_DESC` VARCHAR(9) default '' NOT NULL,
	PRIMARY KEY (`TIME_ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='The application';
#-----------------------------------------------------------------------------
#-- APP_HISTORY
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_HISTORY`;


CREATE TABLE `APP_HISTORY`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`DYN_UID` VARCHAR(32) default '' NOT NULL,
	`OBJ_TYPE` VARCHAR(20) default 'DYNAFORM' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`APP_STATUS` VARCHAR(100) default '' NOT NULL,
	`HISTORY_DATE` DATETIME,
	`HISTORY_DATA` MEDIUMTEXT  NOT NULL,
	KEY `indexAppHistory`(`APP_UID`, `TAS_UID`, `USR_UID`),
	KEY `indexDynUid`(`DYN_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='History table for Dynaforms';
#-----------------------------------------------------------------------------
#-- APP_FOLDER
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_FOLDER`;


CREATE TABLE `APP_FOLDER`
(
	`FOLDER_UID` VARCHAR(32) default '' NOT NULL,
	`FOLDER_PARENT_UID` VARCHAR(32) default '' NOT NULL,
	`FOLDER_NAME` MEDIUMTEXT  NOT NULL,
	`FOLDER_CREATE_DATE` DATETIME  NOT NULL,
	`FOLDER_UPDATE_DATE` DATETIME  NOT NULL,
	PRIMARY KEY (`FOLDER_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Folder System PM Documents';
#-----------------------------------------------------------------------------
#-- FIELD_CONDITION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `FIELD_CONDITION`;


CREATE TABLE `FIELD_CONDITION`
(
	`FCD_UID` VARCHAR(32) default '' NOT NULL,
	`FCD_FUNCTION` VARCHAR(50)  NOT NULL,
	`FCD_FIELDS` MEDIUMTEXT,
	`FCD_CONDITION` MEDIUMTEXT,
	`FCD_EVENTS` MEDIUMTEXT,
	`FCD_EVENT_OWNERS` MEDIUMTEXT,
	`FCD_STATUS` VARCHAR(10),
	`FCD_DYN_UID` VARCHAR(32)  NOT NULL,
	PRIMARY KEY (`FCD_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Conditions store to show or hide dynaform fields..';
#-----------------------------------------------------------------------------
#-- LOG_CASES_SCHEDULER
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LOG_CASES_SCHEDULER`;


CREATE TABLE `LOG_CASES_SCHEDULER`
(
	`LOG_CASE_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`USR_NAME` VARCHAR(32) default '' NOT NULL,
	`EXEC_DATE` DATE  NOT NULL,
	`EXEC_HOUR` VARCHAR(32) default '12:00' NOT NULL,
	`RESULT` VARCHAR(32) default 'SUCCESS' NOT NULL,
	`SCH_UID` VARCHAR(32) default 'OPEN' NOT NULL,
	`WS_CREATE_CASE_STATUS` MEDIUMTEXT  NOT NULL,
	`WS_ROUTE_CASE_STATUS` MEDIUMTEXT  NOT NULL,
	PRIMARY KEY (`LOG_CASE_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Cases Launched with Case Scheduler';
#-----------------------------------------------------------------------------
#-- CASE_SCHEDULER
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `CASE_SCHEDULER`;


CREATE TABLE `CASE_SCHEDULER`
(
	`SCH_UID` VARCHAR(32)  NOT NULL,
	`SCH_DEL_USER_NAME` VARCHAR(100)  NOT NULL,
	`SCH_DEL_USER_PASS` VARCHAR(100)  NOT NULL,
	`SCH_DEL_USER_UID` VARCHAR(100)  NOT NULL,
	`SCH_NAME` VARCHAR(100)  NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`SCH_TIME_NEXT_RUN` DATETIME  NOT NULL,
	`SCH_LAST_RUN_TIME` DATETIME,
	`SCH_STATE` VARCHAR(15) default 'ACTIVE' NOT NULL,
	`SCH_LAST_STATE` VARCHAR(60) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`SCH_OPTION` TINYINT default 0 NOT NULL,
	`SCH_START_TIME` DATETIME  NOT NULL,
	`SCH_START_DATE` DATETIME  NOT NULL,
	`SCH_DAYS_PERFORM_TASK` CHAR(5) default '' NOT NULL,
	`SCH_EVERY_DAYS` TINYINT default 0,
	`SCH_WEEK_DAYS` CHAR(14) default '0|0|0|0|0|0|0' NOT NULL,
	`SCH_START_DAY` CHAR(6) default '' NOT NULL,
	`SCH_MONTHS` CHAR(27) default '0|0|0|0|0|0|0|0|0|0|0|0' NOT NULL,
	`SCH_END_DATE` DATETIME,
	`SCH_REPEAT_EVERY` VARCHAR(15) default '' NOT NULL,
	`SCH_REPEAT_UNTIL` VARCHAR(15) default '' NOT NULL,
	`SCH_REPEAT_STOP_IF_RUNNING` TINYINT default 0,
	`SCH_EXECUTION_DATE` DATETIME,
	`CASE_SH_PLUGIN_UID` VARCHAR(100),
	PRIMARY KEY (`SCH_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Conditions store to show or hide dynaform fields..';
#-----------------------------------------------------------------------------
#-- CALENDAR_DEFINITION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `CALENDAR_DEFINITION`;


CREATE TABLE `CALENDAR_DEFINITION`
(
	`CALENDAR_UID` VARCHAR(32) default '' NOT NULL,
	`CALENDAR_NAME` VARCHAR(100) default '' NOT NULL,
	`CALENDAR_CREATE_DATE` DATETIME  NOT NULL,
	`CALENDAR_UPDATE_DATE` DATETIME,
	`CALENDAR_WORK_DAYS` VARCHAR(100) default '' NOT NULL,
	`CALENDAR_DESCRIPTION` MEDIUMTEXT  NOT NULL,
	`CALENDAR_STATUS` VARCHAR(8) default 'ACTIVE' NOT NULL,
	PRIMARY KEY (`CALENDAR_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Calendar Definition used by PM';
#-----------------------------------------------------------------------------
#-- CALENDAR_BUSINESS_HOURS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `CALENDAR_BUSINESS_HOURS`;


CREATE TABLE `CALENDAR_BUSINESS_HOURS`
(
	`CALENDAR_UID` VARCHAR(32) default '' NOT NULL,
	`CALENDAR_BUSINESS_DAY` VARCHAR(10) default '' NOT NULL,
	`CALENDAR_BUSINESS_START` VARCHAR(10) default '' NOT NULL,
	`CALENDAR_BUSINESS_END` VARCHAR(10) default '' NOT NULL,
	PRIMARY KEY (`CALENDAR_UID`,`CALENDAR_BUSINESS_DAY`,`CALENDAR_BUSINESS_START`,`CALENDAR_BUSINESS_END`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Calendar Business Hours';
#-----------------------------------------------------------------------------
#-- CALENDAR_HOLIDAYS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `CALENDAR_HOLIDAYS`;


CREATE TABLE `CALENDAR_HOLIDAYS`
(
	`CALENDAR_UID` VARCHAR(32) default '' NOT NULL,
	`CALENDAR_HOLIDAY_NAME` VARCHAR(100) default '' NOT NULL,
	`CALENDAR_HOLIDAY_START` DATETIME  NOT NULL,
	`CALENDAR_HOLIDAY_END` DATETIME  NOT NULL,
	PRIMARY KEY (`CALENDAR_UID`,`CALENDAR_HOLIDAY_NAME`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Calendar Holidays';
#-----------------------------------------------------------------------------
#-- CALENDAR_ASSIGNMENTS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `CALENDAR_ASSIGNMENTS`;


CREATE TABLE `CALENDAR_ASSIGNMENTS`
(
	`OBJECT_UID` VARCHAR(32) default '' NOT NULL,
	`CALENDAR_UID` VARCHAR(32) default '' NOT NULL,
	`OBJECT_TYPE` VARCHAR(100) default '' NOT NULL,
	PRIMARY KEY (`OBJECT_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Calendar Holidays';
#-----------------------------------------------------------------------------
#-- PROCESS_CATEGORY
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `PROCESS_CATEGORY`;


CREATE TABLE `PROCESS_CATEGORY`
(
	`CATEGORY_ID` INTEGER  NOT NULL AUTO_INCREMENT,
	`CATEGORY_UID` VARCHAR(32) default '' NOT NULL,
	`CATEGORY_NAME` VARCHAR(100) default '' NOT NULL,
	`CREATED_AT` DATETIME,
	`UPDATED_AT` DATETIME,
	PRIMARY KEY (`CATEGORY_ID`),
	UNIQUE KEY `UQ_CATEGORY_UID` (`CATEGORY_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Process categories';
#-----------------------------------------------------------------------------
#-- APP_NOTES
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_NOTES`;


CREATE TABLE `APP_NOTES`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`NOTE_DATE` DATETIME  NOT NULL,
	`NOTE_CONTENT` MEDIUMTEXT  NOT NULL,
	`NOTE_TYPE` VARCHAR(32) default 'USER' NOT NULL,
	`NOTE_AVAILABILITY` VARCHAR(32) default 'PUBLIC' NOT NULL,
	`NOTE_ORIGIN_OBJ` VARCHAR(32) default '',
	`NOTE_AFFECTED_OBJ1` VARCHAR(32) default '',
	`NOTE_AFFECTED_OBJ2` VARCHAR(32) default '' NOT NULL,
	`NOTE_RECIPIENTS` MEDIUMTEXT,
	KEY `indexAppNotesDate`(`APP_UID`, `NOTE_DATE`),
	KEY `indexAppNotesUser`(`APP_UID`, `USR_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Application Notes';
#-----------------------------------------------------------------------------
#-- DASHLET
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `DASHLET`;


CREATE TABLE `DASHLET`
(
	`DAS_UID` VARCHAR(32) default '' NOT NULL,
	`DAS_CLASS` VARCHAR(50) default '' NOT NULL,
	`DAS_TITLE` VARCHAR(255) default '' NOT NULL,
	`DAS_DESCRIPTION` MEDIUMTEXT,
	`DAS_VERSION` VARCHAR(10) default '1.0' NOT NULL,
	`DAS_CREATE_DATE` DATETIME  NOT NULL,
	`DAS_UPDATE_DATE` DATETIME,
	`DAS_STATUS` TINYINT default 1 NOT NULL,
	PRIMARY KEY (`DAS_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Dashblets definitions';
#-----------------------------------------------------------------------------
#-- DASHLET_INSTANCE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `DASHLET_INSTANCE`;


CREATE TABLE `DASHLET_INSTANCE`
(
	`DAS_INS_UID` VARCHAR(32) default '' NOT NULL,
	`DAS_UID` VARCHAR(32) default '' NOT NULL,
	`DAS_INS_OWNER_TYPE` VARCHAR(20) default '' NOT NULL,
	`DAS_INS_OWNER_UID` VARCHAR(32) default '',
	`DAS_INS_ADDITIONAL_PROPERTIES` MEDIUMTEXT,
	`DAS_INS_CREATE_DATE` DATETIME  NOT NULL,
	`DAS_INS_UPDATE_DATE` DATETIME,
	`DAS_INS_STATUS` TINYINT default 1 NOT NULL,
	PRIMARY KEY (`DAS_INS_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Dashblets definitions';
#-----------------------------------------------------------------------------
#-- APP_SOLR_QUEUE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_SOLR_QUEUE`;


CREATE TABLE `APP_SOLR_QUEUE`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`APP_CHANGE_DATE` DATETIME  NOT NULL,
	`APP_CHANGE_TRACE` VARCHAR(500)  NOT NULL,
	`APP_UPDATED` TINYINT default 1 NOT NULL,
	PRIMARY KEY (`APP_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='APP_SOLR_QUEUE';
#-----------------------------------------------------------------------------
#-- SEQUENCES
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `SEQUENCES`;


CREATE TABLE `SEQUENCES`
(
	`SEQ_NAME` VARCHAR(50) default '' NOT NULL,
	`SEQ_VALUE` INTEGER default 0 NOT NULL,
	PRIMARY KEY (`SEQ_NAME`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Sequences, Controls the numerical sequence of a table';
#-----------------------------------------------------------------------------
#-- SESSION_STORAGE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `SESSION_STORAGE`;


CREATE TABLE `SESSION_STORAGE`
(
	`ID` VARCHAR(128)  NOT NULL,
	`SET_TIME` VARCHAR(10)  NOT NULL,
	`DATA` MEDIUMTEXT  NOT NULL,
	`SESSION_KEY` VARCHAR(128)  NOT NULL,
	`CLIENT_ADDRESS` VARCHAR(32) default '0.0.0.0',
	PRIMARY KEY (`ID`),
	KEY `indexSessionStorage`(`ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- PROCESS_FILES
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `PROCESS_FILES`;


CREATE TABLE `PROCESS_FILES`
(
	`PRF_ID` INTEGER  NOT NULL AUTO_INCREMENT,
	`PRF_UID` VARCHAR(32)  NOT NULL,
	`PRO_UID` VARCHAR(32)  NOT NULL,
	`USR_UID` VARCHAR(32)  NOT NULL,
	`PRF_UPDATE_USR_UID` VARCHAR(32)  NOT NULL,
	`PRF_PATH` VARCHAR(256) COLLATE utf8_bin default '' NOT NULL,
	`PRF_TYPE` VARCHAR(32) default '',
	`PRF_EDITABLE` TINYINT default 1,
	`PRF_DRIVE` VARCHAR(32)  NOT NULL,
	`PRF_PATH_FOR_CLIENT` VARCHAR(255) COLLATE utf8_bin  NOT NULL,
	`PRF_CREATE_DATE` DATETIME  NOT NULL,
	`PRF_UPDATE_DATE` DATETIME,
	PRIMARY KEY (`PRF_ID`),
	UNIQUE KEY `UQ_PRO_UID_PRF_PATH_FOR_CLIENT` (`PRO_UID`, `PRF_PATH_FOR_CLIENT`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Application files metadata';
#-----------------------------------------------------------------------------
#-- WEB_ENTRY
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `WEB_ENTRY`;


CREATE TABLE `WEB_ENTRY`
(
	`WE_UID` VARCHAR(32)  NOT NULL,
	`PRO_UID` VARCHAR(32)  NOT NULL,
	`TAS_UID` VARCHAR(32)  NOT NULL,
	`DYN_UID` VARCHAR(32),
	`USR_UID` VARCHAR(32),
	`WE_METHOD` VARCHAR(4) default 'HTML',
	`WE_INPUT_DOCUMENT_ACCESS` INTEGER default 0,
	`WE_DATA` MEDIUMTEXT,
	`WE_CREATE_USR_UID` VARCHAR(32) default '' NOT NULL,
	`WE_UPDATE_USR_UID` VARCHAR(32) default '',
	`WE_CREATE_DATE` DATETIME  NOT NULL,
	`WE_UPDATE_DATE` DATETIME,
	`WE_TYPE` VARCHAR(8) default 'SINGLE' NOT NULL,
	`WE_CUSTOM_TITLE` MEDIUMTEXT,
	`WE_AUTHENTICATION` VARCHAR(14) default 'ANONYMOUS' NOT NULL,
	`WE_HIDE_INFORMATION_BAR` CHAR(1) default '1',
	`WE_CALLBACK` VARCHAR(13) default 'PROCESSMAKER' NOT NULL,
	`WE_CALLBACK_URL` MEDIUMTEXT,
	`WE_LINK_GENERATION` VARCHAR(8) default 'DEFAULT' NOT NULL,
	`WE_LINK_SKIN` VARCHAR(255),
	`WE_LINK_LANGUAGE` VARCHAR(255),
	`WE_LINK_DOMAIN` MEDIUMTEXT,
	`WE_SHOW_IN_NEW_CASE` CHAR(1) default '1',
	PRIMARY KEY (`WE_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- OAUTH_ACCESS_TOKENS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `OAUTH_ACCESS_TOKENS`;


CREATE TABLE `OAUTH_ACCESS_TOKENS`
(
	`ACCESS_TOKEN` VARCHAR(255)  NOT NULL,
	`CLIENT_ID` VARCHAR(80)  NOT NULL,
	`USER_ID` VARCHAR(32),
	`EXPIRES` DATETIME  NOT NULL,
	`SCOPE` VARCHAR(2000),
	PRIMARY KEY (`ACCESS_TOKEN`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- OAUTH_AUTHORIZATION_CODES
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `OAUTH_AUTHORIZATION_CODES`;


CREATE TABLE `OAUTH_AUTHORIZATION_CODES`
(
	`AUTHORIZATION_CODE` VARCHAR(255)  NOT NULL,
	`CLIENT_ID` VARCHAR(80)  NOT NULL,
	`USER_ID` VARCHAR(32),
	`REDIRECT_URI` VARCHAR(2000),
	`EXPIRES` DATETIME  NOT NULL,
	`SCOPE` VARCHAR(2000),
	PRIMARY KEY (`AUTHORIZATION_CODE`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- OAUTH_CLIENTS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `OAUTH_CLIENTS`;


CREATE TABLE `OAUTH_CLIENTS`
(
	`CLIENT_ID` VARCHAR(80)  NOT NULL,
	`CLIENT_SECRET` VARCHAR(80)  NOT NULL,
	`CLIENT_NAME` VARCHAR(256)  NOT NULL,
	`CLIENT_DESCRIPTION` VARCHAR(1024)  NOT NULL,
	`CLIENT_WEBSITE` VARCHAR(1024)  NOT NULL,
	`REDIRECT_URI` VARCHAR(2000)  NOT NULL,
	`USR_UID` VARCHAR(32)  NOT NULL,
	PRIMARY KEY (`CLIENT_ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- OAUTH_REFRESH_TOKENS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `OAUTH_REFRESH_TOKENS`;


CREATE TABLE `OAUTH_REFRESH_TOKENS`
(
	`REFRESH_TOKEN` VARCHAR(255)  NOT NULL,
	`ACCESS_TOKEN` VARCHAR(255)  NOT NULL,
	`EXPIRES` DATETIME  NOT NULL,
	PRIMARY KEY (`REFRESH_TOKEN`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- OAUTH_SCOPES
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `OAUTH_SCOPES`;


CREATE TABLE `OAUTH_SCOPES`
(
	`TYPE` VARCHAR(40)  NOT NULL,
	`SCOPE` VARCHAR(2000),
	`CLIENT_ID` VARCHAR(80)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- PMOAUTH_USER_ACCESS_TOKENS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `PMOAUTH_USER_ACCESS_TOKENS`;


CREATE TABLE `PMOAUTH_USER_ACCESS_TOKENS`
(
	`ACCESS_TOKEN` VARCHAR(40)  NOT NULL,
	`REFRESH_TOKEN` VARCHAR(40)  NOT NULL,
	`USER_ID` VARCHAR(32),
	`SESSION_ID` VARCHAR(64)  NOT NULL,
	`SESSION_NAME` VARCHAR(64)  NOT NULL,
	PRIMARY KEY (`ACCESS_TOKEN`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_PROJECT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_PROJECT`;


CREATE TABLE `BPMN_PROJECT`
(
	`PRJ_ID` INTEGER  NOT NULL AUTO_INCREMENT,
	`PRJ_UID` VARCHAR(32) default '' NOT NULL,
	`PRJ_NAME` VARCHAR(255) default '' NOT NULL,
	`PRJ_DESCRIPTION` VARCHAR(512),
	`PRJ_TARGET_NAMESPACE` MEDIUMTEXT,
	`PRJ_EXPRESION_LANGUAGE` MEDIUMTEXT,
	`PRJ_TYPE_LANGUAGE` MEDIUMTEXT,
	`PRJ_EXPORTER` MEDIUMTEXT,
	`PRJ_EXPORTER_VERSION` MEDIUMTEXT,
	`PRJ_CREATE_DATE` DATETIME  NOT NULL,
	`PRJ_UPDATE_DATE` DATETIME,
	`PRJ_AUTHOR` MEDIUMTEXT,
	`PRJ_AUTHOR_VERSION` MEDIUMTEXT,
	`PRJ_ORIGINAL_SOURCE` MEDIUMTEXT,
	PRIMARY KEY (`PRJ_ID`),
	KEY `BPMN_PROJECT_I_1`(`PRJ_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_PROCESS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_PROCESS`;


CREATE TABLE `BPMN_PROCESS`
(
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`DIA_UID` VARCHAR(32),
	`PRO_NAME` VARCHAR(255)  NOT NULL,
	`PRO_TYPE` VARCHAR(10) default 'NONE' NOT NULL,
	`PRO_IS_EXECUTABLE` TINYINT default 0 NOT NULL,
	`PRO_IS_CLOSED` TINYINT default 0 NOT NULL,
	`PRO_IS_SUBPROCESS` TINYINT default 0 NOT NULL,
	PRIMARY KEY (`PRO_UID`),
	KEY `BPMN_PROCESS_I_1`(`PRO_UID`),
	KEY `BPMN_PROCESS_I_2`(`PRJ_UID`),
	CONSTRAINT `fk_bpmn_process_project`
		FOREIGN KEY (`PRJ_UID`)
		REFERENCES `BPMN_PROJECT` (`PRJ_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_ACTIVITY
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_ACTIVITY`;


CREATE TABLE `BPMN_ACTIVITY`
(
	`ACT_UID` VARCHAR(32) default '' NOT NULL,
	`PRJ_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '',
	`ACT_NAME` VARCHAR(255)  NOT NULL,
	`ACT_TYPE` VARCHAR(30) default 'TASK' NOT NULL,
	`ACT_IS_FOR_COMPENSATION` TINYINT default 0,
	`ACT_START_QUANTITY` INTEGER default 1,
	`ACT_COMPLETION_QUANTITY` INTEGER default 1,
	`ACT_TASK_TYPE` VARCHAR(20) default 'EMPTY' NOT NULL,
	`ACT_IMPLEMENTATION` MEDIUMTEXT,
	`ACT_INSTANTIATE` TINYINT default 0,
	`ACT_SCRIPT_TYPE` VARCHAR(255),
	`ACT_SCRIPT` MEDIUMTEXT,
	`ACT_LOOP_TYPE` VARCHAR(20) default 'NONE' NOT NULL,
	`ACT_TEST_BEFORE` TINYINT default 0,
	`ACT_LOOP_MAXIMUM` INTEGER default 0,
	`ACT_LOOP_CONDITION` VARCHAR(100),
	`ACT_LOOP_CARDINALITY` INTEGER default 0,
	`ACT_LOOP_BEHAVIOR` VARCHAR(20) default 'NONE',
	`ACT_IS_ADHOC` TINYINT default 0,
	`ACT_IS_COLLAPSED` TINYINT default 1,
	`ACT_COMPLETION_CONDITION` VARCHAR(255),
	`ACT_ORDERING` VARCHAR(20) default 'PARALLEL',
	`ACT_CANCEL_REMAINING_INSTANCES` TINYINT default 1,
	`ACT_PROTOCOL` VARCHAR(255),
	`ACT_METHOD` VARCHAR(255),
	`ACT_IS_GLOBAL` TINYINT default 0,
	`ACT_REFERER` VARCHAR(32) default '',
	`ACT_DEFAULT_FLOW` VARCHAR(32) default '',
	`ACT_MASTER_DIAGRAM` VARCHAR(32) default '',
	PRIMARY KEY (`ACT_UID`),
	KEY `BPMN_ACTIVITY_I_1`(`ACT_UID`),
	KEY `BPMN_ACTIVITY_I_2`(`PRJ_UID`),
	KEY `BPMN_ACTIVITY_I_3`(`PRO_UID`),
	CONSTRAINT `fk_bpmn_activity_project`
		FOREIGN KEY (`PRJ_UID`)
		REFERENCES `BPMN_PROJECT` (`PRJ_UID`),
	CONSTRAINT `fk_bpmn_activity_process`
		FOREIGN KEY (`PRO_UID`)
		REFERENCES `BPMN_PROCESS` (`PRO_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_ARTIFACT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_ARTIFACT`;


CREATE TABLE `BPMN_ARTIFACT`
(
	`ART_UID` VARCHAR(32) default '' NOT NULL,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`PRO_UID` VARCHAR(32) default '',
	`ART_TYPE` VARCHAR(15)  NOT NULL,
	`ART_NAME` MEDIUMTEXT,
	`ART_CATEGORY_REF` VARCHAR(32),
	PRIMARY KEY (`ART_UID`),
	KEY `BPMN_ARTIFACT_I_1`(`ART_UID`),
	KEY `BPMN_ARTIFACT_I_2`(`PRJ_UID`),
	KEY `BPMN_ARTIFACT_I_3`(`PRO_UID`),
	CONSTRAINT `fk_bpmn_artifact_project`
		FOREIGN KEY (`PRJ_UID`)
		REFERENCES `BPMN_PROJECT` (`PRJ_UID`),
	CONSTRAINT `fk_bpmn_artifact_process`
		FOREIGN KEY (`PRO_UID`)
		REFERENCES `BPMN_PROCESS` (`PRO_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_DIAGRAM
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_DIAGRAM`;


CREATE TABLE `BPMN_DIAGRAM`
(
	`DIA_UID` VARCHAR(32) default '' NOT NULL,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`DIA_NAME` VARCHAR(255),
	`DIA_IS_CLOSABLE` TINYINT default 0,
	PRIMARY KEY (`DIA_UID`),
	KEY `BPMN_DIAGRAM_I_1`(`DIA_UID`),
	KEY `BPMN_DIAGRAM_I_2`(`PRJ_UID`),
	CONSTRAINT `fk_bpmn_diagram_project`
		FOREIGN KEY (`PRJ_UID`)
		REFERENCES `BPMN_PROJECT` (`PRJ_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_BOUND
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_BOUND`;


CREATE TABLE `BPMN_BOUND`
(
	`BOU_UID` VARCHAR(32) default '' NOT NULL,
	`PRJ_UID` VARCHAR(32) default '' NOT NULL,
	`DIA_UID` VARCHAR(32) default '' NOT NULL,
	`ELEMENT_UID` VARCHAR(32) default '',
	`BOU_ELEMENT` VARCHAR(32) default '' NOT NULL,
	`BOU_ELEMENT_TYPE` VARCHAR(32) default '' NOT NULL,
	`BOU_X` INTEGER default 0 NOT NULL,
	`BOU_Y` INTEGER default 0 NOT NULL,
	`BOU_WIDTH` INTEGER default 0 NOT NULL,
	`BOU_HEIGHT` INTEGER default 0 NOT NULL,
	`BOU_REL_POSITION` INTEGER default 0,
	`BOU_SIZE_IDENTICAL` INTEGER default 0,
	`BOU_CONTAINER` VARCHAR(30) default '',
	PRIMARY KEY (`BOU_UID`),
	KEY `BPMN_BOUND_I_1`(`BOU_UID`),
	KEY `BPMN_BOUND_I_2`(`PRJ_UID`),
	KEY `BPMN_BOUND_I_3`(`DIA_UID`),
	CONSTRAINT `fk_bpmn_bound_project`
		FOREIGN KEY (`PRJ_UID`)
		REFERENCES `BPMN_PROJECT` (`PRJ_UID`),
	CONSTRAINT `fk_bpmn_bound_diagram`
		FOREIGN KEY (`DIA_UID`)
		REFERENCES `BPMN_DIAGRAM` (`DIA_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_DATA
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_DATA`;


CREATE TABLE `BPMN_DATA`
(
	`DAT_UID` VARCHAR(32) default '' NOT NULL,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`PRO_UID` VARCHAR(32) default '',
	`DAT_NAME` VARCHAR(255),
	`DAT_TYPE` VARCHAR(20)  NOT NULL,
	`DAT_IS_COLLECTION` TINYINT default 0,
	`DAT_ITEM_KIND` VARCHAR(20) default 'INFORMATION' NOT NULL,
	`DAT_CAPACITY` INTEGER default 0,
	`DAT_IS_UNLIMITED` TINYINT default 0,
	`DAT_STATE` VARCHAR(255) default '',
	`DAT_IS_GLOBAL` TINYINT default 0,
	`DAT_OBJECT_REF` VARCHAR(32) default '',
	PRIMARY KEY (`DAT_UID`),
	KEY `BPMN_DATA_I_1`(`DAT_UID`),
	KEY `BPMN_DATA_I_2`(`PRJ_UID`),
	KEY `BPMN_DATA_I_3`(`PRO_UID`),
	CONSTRAINT `fk_bpmn_data_process`
		FOREIGN KEY (`PRO_UID`)
		REFERENCES `BPMN_PROCESS` (`PRO_UID`),
	CONSTRAINT `fk_bpmn_data_project`
		FOREIGN KEY (`PRJ_UID`)
		REFERENCES `BPMN_PROJECT` (`PRJ_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_EVENT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_EVENT`;


CREATE TABLE `BPMN_EVENT`
(
	`EVN_UID` VARCHAR(32) default '' NOT NULL,
	`PRJ_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '',
	`EVN_NAME` VARCHAR(255),
	`EVN_TYPE` VARCHAR(30) default '' NOT NULL,
	`EVN_MARKER` VARCHAR(30) default 'EMPTY' NOT NULL,
	`EVN_IS_INTERRUPTING` TINYINT default 1,
	`EVN_ATTACHED_TO` VARCHAR(32) default '',
	`EVN_CANCEL_ACTIVITY` TINYINT default 0,
	`EVN_ACTIVITY_REF` VARCHAR(32) default '',
	`EVN_WAIT_FOR_COMPLETION` TINYINT default 1,
	`EVN_ERROR_NAME` VARCHAR(255),
	`EVN_ERROR_CODE` VARCHAR(255),
	`EVN_ESCALATION_NAME` VARCHAR(255),
	`EVN_ESCALATION_CODE` VARCHAR(255),
	`EVN_CONDITION` VARCHAR(255),
	`EVN_MESSAGE` MEDIUMTEXT,
	`EVN_OPERATION_NAME` VARCHAR(255),
	`EVN_OPERATION_IMPLEMENTATION_REF` VARCHAR(255),
	`EVN_TIME_DATE` VARCHAR(255),
	`EVN_TIME_CYCLE` VARCHAR(255),
	`EVN_TIME_DURATION` VARCHAR(255),
	`EVN_BEHAVIOR` VARCHAR(20) default 'CATCH' NOT NULL,
	PRIMARY KEY (`EVN_UID`),
	KEY `BPMN_EVENT_I_1`(`EVN_UID`),
	KEY `BPMN_EVENT_I_2`(`PRJ_UID`),
	KEY `BPMN_EVENT_I_3`(`PRO_UID`),
	CONSTRAINT `fk_bpmn_event_project`
		FOREIGN KEY (`PRJ_UID`)
		REFERENCES `BPMN_PROJECT` (`PRJ_UID`),
	CONSTRAINT `fk_bpmn_event_process`
		FOREIGN KEY (`PRO_UID`)
		REFERENCES `BPMN_PROCESS` (`PRO_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_FLOW
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_FLOW`;


CREATE TABLE `BPMN_FLOW`
(
	`FLO_UID` VARCHAR(32) default '' NOT NULL,
	`PRJ_UID` VARCHAR(32) default '' NOT NULL,
	`DIA_UID` VARCHAR(32) default '' NOT NULL,
	`FLO_TYPE` VARCHAR(20) default '' NOT NULL,
	`FLO_NAME` VARCHAR(255) default '',
	`FLO_ELEMENT_ORIGIN` VARCHAR(32) default '' NOT NULL,
	`FLO_ELEMENT_ORIGIN_TYPE` VARCHAR(32) default '' NOT NULL,
	`FLO_ELEMENT_ORIGIN_PORT` INTEGER default 0 NOT NULL,
	`FLO_ELEMENT_DEST` VARCHAR(32) default '' NOT NULL,
	`FLO_ELEMENT_DEST_TYPE` VARCHAR(32) default '' NOT NULL,
	`FLO_ELEMENT_DEST_PORT` INTEGER default 0 NOT NULL,
	`FLO_IS_INMEDIATE` TINYINT,
	`FLO_CONDITION` VARCHAR(512),
	`FLO_X1` INTEGER default 0 NOT NULL,
	`FLO_Y1` INTEGER default 0 NOT NULL,
	`FLO_X2` INTEGER default 0 NOT NULL,
	`FLO_Y2` INTEGER default 0 NOT NULL,
	`FLO_STATE` MEDIUMTEXT,
	`FLO_POSITION` INTEGER default 0 NOT NULL,
	PRIMARY KEY (`FLO_UID`),
	KEY `BPMN_FLOW_I_1`(`FLO_UID`),
	KEY `BPMN_FLOW_I_2`(`PRJ_UID`),
	KEY `BPMN_FLOW_I_3`(`DIA_UID`),
	CONSTRAINT `fk_bpmn_flow_project`
		FOREIGN KEY (`PRJ_UID`)
		REFERENCES `BPMN_PROJECT` (`PRJ_UID`),
	CONSTRAINT `fk_bpmn_flow_diagram`
		FOREIGN KEY (`DIA_UID`)
		REFERENCES `BPMN_DIAGRAM` (`DIA_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_GATEWAY
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_GATEWAY`;


CREATE TABLE `BPMN_GATEWAY`
(
	`GAT_UID` VARCHAR(32) default '' NOT NULL,
	`PRJ_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '',
	`GAT_NAME` VARCHAR(255),
	`GAT_TYPE` VARCHAR(30) default '' NOT NULL,
	`GAT_DIRECTION` VARCHAR(30) default 'UNSPECIFIED',
	`GAT_INSTANTIATE` TINYINT default 0,
	`GAT_EVENT_GATEWAY_TYPE` VARCHAR(20) default 'NONE',
	`GAT_ACTIVATION_COUNT` INTEGER default 0,
	`GAT_WAITING_FOR_START` TINYINT default 1,
	`GAT_DEFAULT_FLOW` VARCHAR(32) default '',
	PRIMARY KEY (`GAT_UID`),
	KEY `BPMN_GATEWAY_I_1`(`GAT_UID`),
	KEY `BPMN_GATEWAY_I_2`(`PRJ_UID`),
	KEY `BPMN_GATEWAY_I_3`(`PRO_UID`),
	CONSTRAINT `fk_bpmn_gateway_project`
		FOREIGN KEY (`PRJ_UID`)
		REFERENCES `BPMN_PROJECT` (`PRJ_UID`),
	CONSTRAINT `fk_bpmn_gateway_process`
		FOREIGN KEY (`PRO_UID`)
		REFERENCES `BPMN_PROCESS` (`PRO_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_LANESET
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_LANESET`;


CREATE TABLE `BPMN_LANESET`
(
	`LNS_UID` VARCHAR(32) default '' NOT NULL,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`PRO_UID` VARCHAR(32),
	`LNS_NAME` VARCHAR(255),
	`LNS_PARENT_LANE` VARCHAR(32),
	`LNS_IS_HORIZONTAL` TINYINT default 1,
	`LNS_STATE` MEDIUMTEXT,
	PRIMARY KEY (`LNS_UID`),
	KEY `BPMN_LANESET_I_1`(`LNS_UID`),
	KEY `BPMN_LANESET_I_2`(`PRJ_UID`),
	KEY `BPMN_LANESET_I_3`(`PRO_UID`),
	CONSTRAINT `fk_bpmn_laneset_project`
		FOREIGN KEY (`PRJ_UID`)
		REFERENCES `BPMN_PROJECT` (`PRJ_UID`),
	CONSTRAINT `fk_bpmn_laneset_process`
		FOREIGN KEY (`PRO_UID`)
		REFERENCES `BPMN_PROCESS` (`PRO_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_LANE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_LANE`;


CREATE TABLE `BPMN_LANE`
(
	`LAN_UID` VARCHAR(32) default '' NOT NULL,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`LNS_UID` VARCHAR(32)  NOT NULL,
	`LAN_NAME` VARCHAR(255),
	`LAN_CHILD_LANESET` VARCHAR(32),
	`LAN_IS_HORIZONTAL` TINYINT default 1,
	PRIMARY KEY (`LAN_UID`),
	KEY `BPMN_LANE_I_1`(`LAN_UID`),
	KEY `BPMN_LANE_I_2`(`PRJ_UID`),
	KEY `BPMN_LANE_I_3`(`LNS_UID`),
	CONSTRAINT `fk_bpmn_lane_project`
		FOREIGN KEY (`PRJ_UID`)
		REFERENCES `BPMN_PROJECT` (`PRJ_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_PARTICIPANT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_PARTICIPANT`;


CREATE TABLE `BPMN_PARTICIPANT`
(
	`PAR_UID` VARCHAR(32) default '' NOT NULL,
	`PRJ_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '',
	`LNS_UID` VARCHAR(32) default '',
	`PAR_NAME` VARCHAR(255) default '' NOT NULL,
	`PAR_MINIMUM` INTEGER default 0,
	`PAR_MAXIMUM` INTEGER default 1,
	`PAR_NUM_PARTICIPANTS` INTEGER default 1,
	`PAR_IS_HORIZONTAL` TINYINT default 1 NOT NULL,
	PRIMARY KEY (`PAR_UID`),
	KEY `BPMN_PARTICIPANT_I_1`(`PAR_UID`),
	KEY `BPMN_PARTICIPANT_I_2`(`PRJ_UID`),
	CONSTRAINT `fk_bpmn_participant_project`
		FOREIGN KEY (`PRJ_UID`)
		REFERENCES `BPMN_PROJECT` (`PRJ_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_EXTENSION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_EXTENSION`;


CREATE TABLE `BPMN_EXTENSION`
(
	`EXT_UID` VARCHAR(32)  NOT NULL,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`EXT_ELEMENT` VARCHAR(32)  NOT NULL,
	`EXT_ELEMENT_TYPE` VARCHAR(45)  NOT NULL,
	`EXT_EXTENSION` MEDIUMTEXT,
	PRIMARY KEY (`EXT_UID`),
	KEY `BPMN_EXTENSION_I_1`(`EXT_UID`),
	KEY `BPMN_EXTENSION_I_2`(`PRJ_UID`),
	CONSTRAINT `fk_bpmn_extension_project`
		FOREIGN KEY (`PRJ_UID`)
		REFERENCES `BPMN_PROJECT` (`PRJ_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- BPMN_DOCUMENTATION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `BPMN_DOCUMENTATION`;


CREATE TABLE `BPMN_DOCUMENTATION`
(
	`DOC_UID` VARCHAR(32)  NOT NULL,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`DOC_ELEMENT` VARCHAR(32)  NOT NULL,
	`DOC_ELEMENT_TYPE` VARCHAR(45)  NOT NULL,
	`DOC_DOCUMENTATION` MEDIUMTEXT,
	PRIMARY KEY (`DOC_UID`),
	KEY `BPMN_DOCUMENTATION_I_1`(`DOC_UID`),
	KEY `BPMN_DOCUMENTATION_I_2`(`PRJ_UID`),
	CONSTRAINT `fk_bpmn_documentation_project`
		FOREIGN KEY (`PRJ_UID`)
		REFERENCES `BPMN_PROJECT` (`PRJ_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- PROCESS_VARIABLES
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `PROCESS_VARIABLES`;


CREATE TABLE `PROCESS_VARIABLES`
(
	`VAR_UID` VARCHAR(32)  NOT NULL,
	`VAR_ID` INTEGER  NOT NULL AUTO_INCREMENT,
	`PRO_ID` INTEGER,
	`VAR_NAME` VARCHAR(255) default '',
	`VAR_FIELD_TYPE` VARCHAR(32) default '',
	`VAR_FIELD_SIZE` INTEGER,
	`VAR_LABEL` VARCHAR(255) default '',
	`VAR_DBCONNECTION` VARCHAR(32) default 'workflow',
	`VAR_SQL` MEDIUMTEXT,
	`VAR_NULL` TINYINT(32) default 0,
	`VAR_DEFAULT` VARCHAR(32) default '',
	`VAR_ACCEPTED_VALUES` MEDIUMTEXT,
	`INP_DOC_UID` VARCHAR(32) default '',
	PRIMARY KEY (`VAR_UID`),
  UNIQUE KEY `indexVarId` (`VAR_ID`),
  UNIQUE KEY `uniqueVariableName` (`PRO_ID`, `VAR_NAME`(150))
)ENGINE=InnoDB ;
#-----------------------------------------------------------------------------
#-- APP_TIMEOUT_ACTION_EXECUTED
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_TIMEOUT_ACTION_EXECUTED`;


CREATE TABLE `APP_TIMEOUT_ACTION_EXECUTED`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`EXECUTION_DATE` DATETIME,
	PRIMARY KEY (`APP_UID`)
)ENGINE=InnoDB ;
#-----------------------------------------------------------------------------
#-- ADDONS_STORE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `ADDONS_STORE`;


CREATE TABLE `ADDONS_STORE`
(
	`STORE_ID` VARCHAR(32)  NOT NULL,
	`STORE_VERSION` INTEGER,
	`STORE_LOCATION` VARCHAR(2048)  NOT NULL,
	`STORE_TYPE` VARCHAR(255)  NOT NULL,
	`STORE_LAST_UPDATED` DATETIME,
	PRIMARY KEY (`STORE_ID`)
)ENGINE=InnoDB ;
#-----------------------------------------------------------------------------
#-- ADDONS_MANAGER
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `ADDONS_MANAGER`;


CREATE TABLE `ADDONS_MANAGER`
(
	`ADDON_ID` VARCHAR(100)  NOT NULL,
	`STORE_ID` VARCHAR(32)  NOT NULL,
	`ADDON_NAME` VARCHAR(255)  NOT NULL,
	`ADDON_NICK` VARCHAR(255)  NOT NULL,
	`ADDON_DOWNLOAD_FILENAME` VARCHAR(1024),
	`ADDON_DESCRIPTION` VARCHAR(2048),
	`ADDON_STATE` VARCHAR(255)  NOT NULL,
	`ADDON_STATE_CHANGED` DATETIME,
	`ADDON_STATUS` VARCHAR(255)  NOT NULL,
	`ADDON_VERSION` VARCHAR(255)  NOT NULL,
	`ADDON_TYPE` VARCHAR(255)  NOT NULL,
	`ADDON_PUBLISHER` VARCHAR(255),
	`ADDON_RELEASE_DATE` DATETIME,
	`ADDON_RELEASE_TYPE` VARCHAR(255),
	`ADDON_RELEASE_NOTES` VARCHAR(255),
	`ADDON_DOWNLOAD_URL` VARCHAR(2048),
	`ADDON_DOWNLOAD_PROGRESS` FLOAT,
	`ADDON_DOWNLOAD_MD5` VARCHAR(32),
	PRIMARY KEY (`ADDON_ID`,`STORE_ID`),
	KEY `indexAddonsType`(`ADDON_TYPE`)
)ENGINE=InnoDB ;
#-----------------------------------------------------------------------------
#-- LICENSE_MANAGER
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LICENSE_MANAGER`;


CREATE TABLE `LICENSE_MANAGER`
(
	`LICENSE_UID` VARCHAR(32)  NOT NULL,
	`LICENSE_USER` VARCHAR(150) default '0' NOT NULL,
	`LICENSE_START` INTEGER default 0 NOT NULL,
	`LICENSE_END` INTEGER default 0 NOT NULL,
	`LICENSE_SPAN` INTEGER default 0 NOT NULL,
	`LICENSE_STATUS` VARCHAR(100) default '' NOT NULL,
	`LICENSE_DATA` MEDIUMTEXT  NOT NULL,
	`LICENSE_PATH` VARCHAR(255) default '0' NOT NULL,
	`LICENSE_WORKSPACE` VARCHAR(32) default '0' NOT NULL,
	`LICENSE_TYPE` VARCHAR(32) default '0' NOT NULL,
	PRIMARY KEY (`LICENSE_UID`)
)ENGINE=InnoDB ;
#-----------------------------------------------------------------------------
#-- APP_ASSIGN_SELF_SERVICE_VALUE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_ASSIGN_SELF_SERVICE_VALUE`;


CREATE TABLE `APP_ASSIGN_SELF_SERVICE_VALUE`
(
	`ID` INTEGER  NOT NULL AUTO_INCREMENT,
	`APP_UID` VARCHAR(32)  NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`PRO_UID` VARCHAR(32)  NOT NULL,
	`TAS_UID` VARCHAR(32)  NOT NULL,
	`GRP_UID` MEDIUMTEXT  NOT NULL,
	PRIMARY KEY (`ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- APP_ASSIGN_SELF_SERVICE_VALUE_GROUP
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `APP_ASSIGN_SELF_SERVICE_VALUE_GROUP`;


CREATE TABLE `APP_ASSIGN_SELF_SERVICE_VALUE_GROUP`
(
	`ID` INTEGER default 0 NOT NULL,
	`GRP_UID` VARCHAR(32)  NOT NULL,
	KEY `indexId`(`ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- LIST_INBOX
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LIST_INBOX`;


CREATE TABLE `LIST_INBOX`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`APP_NUMBER` INTEGER default 0 NOT NULL,
	`APP_STATUS` VARCHAR(32) default '0',
	`APP_TITLE` MEDIUMTEXT,
	`APP_PRO_TITLE` MEDIUMTEXT,
	`APP_TAS_TITLE` MEDIUMTEXT,
	`APP_UPDATE_DATE` DATETIME,
	`DEL_PREVIOUS_USR_UID` VARCHAR(32) default '',
	`DEL_PREVIOUS_USR_USERNAME` VARCHAR(100) default '',
	`DEL_PREVIOUS_USR_FIRSTNAME` VARCHAR(50) default '',
	`DEL_PREVIOUS_USR_LASTNAME` VARCHAR(50) default '',
	`DEL_DELEGATE_DATE` DATETIME  NOT NULL,
	`DEL_INIT_DATE` DATETIME,
	`DEL_DUE_DATE` DATETIME,
	`DEL_RISK_DATE` DATETIME,
	`DEL_PRIORITY` VARCHAR(32) default '3' NOT NULL,
	`PRO_ID` INTEGER default 0,
	`USR_ID` INTEGER default 0,
	`TAS_ID` INTEGER default 0,
	`APP_STATUS_ID` TINYINT default 0,
	PRIMARY KEY (`APP_UID`,`DEL_INDEX`),
	KEY `indexUser`(`USR_UID`),
	KEY `indexInboxUser`(`USR_UID`, `DEL_DELEGATE_DATE`),
	KEY `indexInboxUserStatusUpdateDate`(`USR_UID`, `APP_STATUS`, `APP_UPDATE_DATE`),
	KEY `INDEX_PRO_ID`(`PRO_ID`),
	KEY `INDEX_USR_ID`(`USR_ID`),
	KEY `INDEX_TAS_ID`(`TAS_ID`),
	KEY `INDEX_APP_STATUS_ID`(`APP_STATUS_ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Inbox list';
#-----------------------------------------------------------------------------
#-- LIST_PARTICIPATED_HISTORY
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LIST_PARTICIPATED_HISTORY`;


CREATE TABLE `LIST_PARTICIPATED_HISTORY`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`APP_NUMBER` INTEGER default 0 NOT NULL,
	`APP_TITLE` MEDIUMTEXT,
	`APP_PRO_TITLE` MEDIUMTEXT,
	`APP_TAS_TITLE` MEDIUMTEXT,
	`DEL_PREVIOUS_USR_UID` VARCHAR(32) default '',
	`DEL_PREVIOUS_USR_USERNAME` VARCHAR(100) default '',
	`DEL_PREVIOUS_USR_FIRSTNAME` VARCHAR(50) default '',
	`DEL_PREVIOUS_USR_LASTNAME` VARCHAR(50) default '',
	`DEL_CURRENT_USR_USERNAME` VARCHAR(100) default '',
	`DEL_CURRENT_USR_FIRSTNAME` VARCHAR(50) default '',
	`DEL_CURRENT_USR_LASTNAME` VARCHAR(50) default '',
	`DEL_DELEGATE_DATE` DATETIME  NOT NULL,
	`DEL_INIT_DATE` DATETIME,
	`DEL_DUE_DATE` DATETIME,
	`DEL_PRIORITY` VARCHAR(32) default '3' NOT NULL,
	`PRO_ID` INTEGER default 0,
	`USR_ID` INTEGER default 0,
	`TAS_ID` INTEGER default 0,
	PRIMARY KEY (`APP_UID`,`DEL_INDEX`),
	KEY `indexInboxUser`(`USR_UID`, `DEL_DELEGATE_DATE`),
	KEY `INDEX_PRO_ID`(`PRO_ID`),
	KEY `INDEX_USR_ID`(`USR_ID`),
	KEY `INDEX_TAS_ID`(`TAS_ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Participated history list';
#-----------------------------------------------------------------------------
#-- LIST_PARTICIPATED_LAST
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LIST_PARTICIPATED_LAST`;


CREATE TABLE `LIST_PARTICIPATED_LAST`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`APP_NUMBER` INTEGER default 0 NOT NULL,
	`APP_TITLE` MEDIUMTEXT,
	`APP_PRO_TITLE` MEDIUMTEXT,
	`APP_TAS_TITLE` MEDIUMTEXT,
	`APP_STATUS` VARCHAR(20) default '0',
	`DEL_PREVIOUS_USR_UID` VARCHAR(32) default '',
	`DEL_PREVIOUS_USR_USERNAME` VARCHAR(100) default '',
	`DEL_PREVIOUS_USR_FIRSTNAME` VARCHAR(50) default '',
	`DEL_PREVIOUS_USR_LASTNAME` VARCHAR(50) default '',
	`DEL_CURRENT_USR_USERNAME` VARCHAR(100) default '',
	`DEL_CURRENT_USR_FIRSTNAME` VARCHAR(50) default '',
	`DEL_CURRENT_USR_LASTNAME` VARCHAR(50) default '',
	`DEL_CURRENT_TAS_TITLE` VARCHAR(255) default '' NOT NULL,
	`DEL_DELEGATE_DATE` DATETIME  NOT NULL,
	`DEL_INIT_DATE` DATETIME,
	`DEL_DUE_DATE` DATETIME,
	`DEL_PRIORITY` VARCHAR(32) default '3' NOT NULL,
	`DEL_THREAD_STATUS` VARCHAR(32) default 'OPEN' NOT NULL,
	`PRO_ID` INTEGER default 0,
	`USR_ID` INTEGER default 0,
	`TAS_ID` INTEGER default 0,
	`APP_STATUS_ID` TINYINT default 0,
	PRIMARY KEY (`APP_UID`,`USR_UID`,`DEL_INDEX`),
	KEY `usrIndex`(`USR_UID`),
	KEY `delDelegateDate`(`DEL_DELEGATE_DATE`),
	KEY `INDEX_PRO_ID`(`PRO_ID`),
	KEY `INDEX_USR_ID`(`USR_ID`),
	KEY `INDEX_TAS_ID`(`TAS_ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Participated last list';
#-----------------------------------------------------------------------------
#-- LIST_COMPLETED
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LIST_COMPLETED`;


CREATE TABLE `LIST_COMPLETED`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`APP_NUMBER` INTEGER default 0 NOT NULL,
	`APP_TITLE` MEDIUMTEXT,
	`APP_PRO_TITLE` MEDIUMTEXT,
	`APP_TAS_TITLE` MEDIUMTEXT,
	`APP_CREATE_DATE` DATETIME,
	`APP_FINISH_DATE` DATETIME,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`DEL_PREVIOUS_USR_UID` VARCHAR(32) default '',
	`DEL_CURRENT_USR_USERNAME` VARCHAR(100) default '',
	`DEL_CURRENT_USR_FIRSTNAME` VARCHAR(50) default '',
	`DEL_CURRENT_USR_LASTNAME` VARCHAR(50) default '',
	`PRO_ID` INTEGER default 0,
	`USR_ID` INTEGER default 0,
	`TAS_ID` INTEGER default 0,
	PRIMARY KEY (`APP_UID`),
	KEY `INDEX_PRO_ID`(`PRO_ID`),
	KEY `INDEX_USR_ID`(`USR_ID`),
	KEY `INDEX_TAS_ID`(`TAS_ID`),
	KEY `usrListCompleted`(`USR_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Completed list';
#-----------------------------------------------------------------------------
#-- LIST_PAUSED
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LIST_PAUSED`;


CREATE TABLE `LIST_PAUSED`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`APP_NUMBER` INTEGER default 0 NOT NULL,
	`APP_TITLE` MEDIUMTEXT,
	`APP_PRO_TITLE` MEDIUMTEXT,
	`APP_TAS_TITLE` MEDIUMTEXT,
	`APP_PAUSED_DATE` DATETIME  NOT NULL,
	`APP_RESTART_DATE` DATETIME  NOT NULL,
	`DEL_PREVIOUS_USR_UID` VARCHAR(32) default '',
	`DEL_PREVIOUS_USR_USERNAME` VARCHAR(100) default '',
	`DEL_PREVIOUS_USR_FIRSTNAME` VARCHAR(50) default '',
	`DEL_PREVIOUS_USR_LASTNAME` VARCHAR(50) default '',
	`DEL_CURRENT_USR_USERNAME` VARCHAR(100) default '',
	`DEL_CURRENT_USR_FIRSTNAME` VARCHAR(50) default '',
	`DEL_CURRENT_USR_LASTNAME` VARCHAR(50) default '',
	`DEL_DELEGATE_DATE` DATETIME  NOT NULL,
	`DEL_INIT_DATE` DATETIME,
	`DEL_DUE_DATE` DATETIME,
	`DEL_PRIORITY` VARCHAR(32) default '3' NOT NULL,
	`PRO_ID` INTEGER default 0,
	`USR_ID` INTEGER default 0,
	`TAS_ID` INTEGER default 0,
	PRIMARY KEY (`APP_UID`,`DEL_INDEX`),
	KEY `INDEX_PRO_ID`(`PRO_ID`),
	KEY `INDEX_USR_ID`(`USR_ID`),
	KEY `INDEX_TAS_ID`(`TAS_ID`),
	KEY `indexPausedUser`(`USR_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Paused list';
#-----------------------------------------------------------------------------
#-- LIST_CANCELED
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LIST_CANCELED`;


CREATE TABLE `LIST_CANCELED`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`APP_NUMBER` INTEGER default 0 NOT NULL,
	`APP_TITLE` MEDIUMTEXT,
	`APP_PRO_TITLE` MEDIUMTEXT,
	`APP_TAS_TITLE` MEDIUMTEXT,
	`APP_CANCELED_DATE` DATETIME,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`DEL_PREVIOUS_USR_UID` VARCHAR(32) default '',
	`DEL_CURRENT_USR_USERNAME` VARCHAR(100) default '',
	`DEL_CURRENT_USR_FIRSTNAME` VARCHAR(50) default '',
	`DEL_CURRENT_USR_LASTNAME` VARCHAR(50) default '',
	`DEL_DELEGATE_DATE` DATETIME  NOT NULL,
	`DEL_INIT_DATE` DATETIME,
	`DEL_DUE_DATE` DATETIME,
	`DEL_PRIORITY` VARCHAR(32) default '3' NOT NULL,
	`PRO_ID` INTEGER default 0,
	`USR_ID` INTEGER default 0,
	`TAS_ID` INTEGER default 0,
	PRIMARY KEY (`APP_UID`),
	KEY `INDEX_PRO_ID`(`PRO_ID`),
	KEY `INDEX_USR_ID`(`USR_ID`),
	KEY `INDEX_TAS_ID`(`TAS_ID`),
	KEY `indexCanceledUser`(`USR_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Canceled list';
#-----------------------------------------------------------------------------
#-- LIST_MY_INBOX
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LIST_MY_INBOX`;


CREATE TABLE `LIST_MY_INBOX`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`APP_NUMBER` INTEGER default 0 NOT NULL,
	`APP_TITLE` MEDIUMTEXT,
	`APP_PRO_TITLE` MEDIUMTEXT,
	`APP_TAS_TITLE` MEDIUMTEXT,
	`APP_CREATE_DATE` DATETIME,
	`APP_UPDATE_DATE` DATETIME,
	`APP_FINISH_DATE` DATETIME,
	`APP_STATUS` VARCHAR(100) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`DEL_PREVIOUS_USR_UID` VARCHAR(32) default '',
	`DEL_PREVIOUS_USR_USERNAME` VARCHAR(100) default '',
	`DEL_PREVIOUS_USR_FIRSTNAME` VARCHAR(50) default '',
	`DEL_PREVIOUS_USR_LASTNAME` VARCHAR(50) default '',
	`DEL_CURRENT_USR_UID` VARCHAR(32) default '',
	`DEL_CURRENT_USR_USERNAME` VARCHAR(100) default '',
	`DEL_CURRENT_USR_FIRSTNAME` VARCHAR(50) default '',
	`DEL_CURRENT_USR_LASTNAME` VARCHAR(50) default '',
	`DEL_DELEGATE_DATE` DATETIME,
	`DEL_INIT_DATE` DATETIME,
	`DEL_DUE_DATE` DATETIME,
	`DEL_PRIORITY` VARCHAR(32) default '3' NOT NULL,
	`PRO_ID` INTEGER default 0,
	`USR_ID` INTEGER default 0,
	`TAS_ID` INTEGER default 0,
	`APP_STATUS_ID` TINYINT default 0,
	PRIMARY KEY (`APP_UID`),
	KEY `INDEX_PRO_ID`(`PRO_ID`),
	KEY `INDEX_USR_ID`(`USR_ID`),
	KEY `INDEX_TAS_ID`(`TAS_ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='My Inbox list';
#-----------------------------------------------------------------------------
#-- LIST_UNASSIGNED
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LIST_UNASSIGNED`;


CREATE TABLE `LIST_UNASSIGNED`
(
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`TAS_UID` VARCHAR(32) default '' NOT NULL,
	`PRO_UID` VARCHAR(32) default '' NOT NULL,
	`APP_NUMBER` INTEGER default 0 NOT NULL,
	`APP_TITLE` MEDIUMTEXT,
	`APP_PRO_TITLE` MEDIUMTEXT,
	`APP_TAS_TITLE` MEDIUMTEXT,
	`DEL_PREVIOUS_USR_USERNAME` VARCHAR(100) default '',
	`DEL_PREVIOUS_USR_FIRSTNAME` VARCHAR(50) default '',
	`DEL_PREVIOUS_USR_LASTNAME` VARCHAR(50) default '',
	`APP_UPDATE_DATE` DATETIME  NOT NULL,
	`DEL_PREVIOUS_USR_UID` VARCHAR(32) default '',
	`DEL_DELEGATE_DATE` DATETIME  NOT NULL,
	`DEL_DUE_DATE` DATETIME,
	`DEL_PRIORITY` VARCHAR(32) default '3' NOT NULL,
	`PRO_ID` INTEGER default 0,
	`TAS_ID` INTEGER default 0,
	PRIMARY KEY (`APP_UID`,`DEL_INDEX`),
	KEY `INDEX_PRO_ID`(`PRO_ID`),
	KEY `INDEX_TAS_ID`(`TAS_ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Unassigned list';
#-----------------------------------------------------------------------------
#-- LIST_UNASSIGNED_GROUP
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `LIST_UNASSIGNED_GROUP`;


CREATE TABLE `LIST_UNASSIGNED_GROUP`
(
	`UNA_UID` VARCHAR(32) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`TYPE` VARCHAR(255) default '' NOT NULL,
	`TYP_UID` VARCHAR(32) default '' NOT NULL,
	`USR_ID` INTEGER default 0,
	PRIMARY KEY (`UNA_UID`,`USR_UID`,`TYPE`),
	KEY `INDEX_USR_ID`(`USR_ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Unassiged list';
#-----------------------------------------------------------------------------
#-- MESSAGE_TYPE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `MESSAGE_TYPE`;


CREATE TABLE `MESSAGE_TYPE`
(
	`MSGT_UID` VARCHAR(32)  NOT NULL,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`MSGT_NAME` VARCHAR(512) default '',
	PRIMARY KEY (`MSGT_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- MESSAGE_TYPE_VARIABLE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `MESSAGE_TYPE_VARIABLE`;


CREATE TABLE `MESSAGE_TYPE_VARIABLE`
(
	`MSGTV_UID` VARCHAR(32)  NOT NULL,
	`MSGT_UID` VARCHAR(32)  NOT NULL,
	`MSGTV_NAME` VARCHAR(512) default '',
	`MSGTV_DEFAULT_VALUE` VARCHAR(512) default '',
	PRIMARY KEY (`MSGTV_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- EMAIL_SERVER
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `EMAIL_SERVER`;


CREATE TABLE `EMAIL_SERVER`
(
	`MESS_UID` VARCHAR(32) default '' NOT NULL,
	`MESS_ENGINE` VARCHAR(256) default '' NOT NULL,
	`MESS_SERVER` VARCHAR(256) default '' NOT NULL,
	`MESS_PORT` INTEGER default 0 NOT NULL,
	`MESS_RAUTH` INTEGER default 0 NOT NULL,
	`MESS_ACCOUNT` VARCHAR(256) default '' NOT NULL,
	`MESS_PASSWORD` VARCHAR(256) default '' NOT NULL,
	`MESS_FROM_MAIL` VARCHAR(256) default '',
	`MESS_FROM_NAME` VARCHAR(256) default '',
	`SMTPSECURE` VARCHAR(3) default 'No' NOT NULL,
	`MESS_TRY_SEND_INMEDIATLY` INTEGER default 0 NOT NULL,
	`MAIL_TO` VARCHAR(256) default '',
	`MESS_DEFAULT` INTEGER default 0 NOT NULL,
	PRIMARY KEY (`MESS_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- WEB_ENTRY_EVENT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `WEB_ENTRY_EVENT`;


CREATE TABLE `WEB_ENTRY_EVENT`
(
	`WEE_UID` VARCHAR(32)  NOT NULL,
	`WEE_TITLE` MEDIUMTEXT,
	`WEE_DESCRIPTION` MEDIUMTEXT,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`EVN_UID` VARCHAR(32)  NOT NULL,
	`ACT_UID` VARCHAR(32)  NOT NULL,
	`DYN_UID` VARCHAR(32),
	`USR_UID` VARCHAR(32),
	`WEE_STATUS` VARCHAR(10) default 'ENABLED' NOT NULL,
	`WEE_WE_UID` VARCHAR(32) default '' NOT NULL,
	`WEE_WE_TAS_UID` VARCHAR(32) default '' NOT NULL,
	PRIMARY KEY (`WEE_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- MESSAGE_EVENT_DEFINITION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `MESSAGE_EVENT_DEFINITION`;


CREATE TABLE `MESSAGE_EVENT_DEFINITION`
(
	`MSGED_UID` VARCHAR(32)  NOT NULL,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`EVN_UID` VARCHAR(32)  NOT NULL,
	`MSGT_UID` VARCHAR(32) default '' NOT NULL,
	`MSGED_USR_UID` VARCHAR(32) default '' NOT NULL,
	`MSGED_VARIABLES` MEDIUMTEXT  NOT NULL,
	`MSGED_CORRELATION` VARCHAR(512) default '' NOT NULL,
	PRIMARY KEY (`MSGED_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- MESSAGE_EVENT_RELATION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `MESSAGE_EVENT_RELATION`;


CREATE TABLE `MESSAGE_EVENT_RELATION`
(
	`MSGER_UID` VARCHAR(32)  NOT NULL,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`EVN_UID_THROW` VARCHAR(32)  NOT NULL,
	`EVN_UID_CATCH` VARCHAR(32)  NOT NULL,
	PRIMARY KEY (`MSGER_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- MESSAGE_APPLICATION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `MESSAGE_APPLICATION`;


CREATE TABLE `MESSAGE_APPLICATION`
(
	`MSGAPP_UID` VARCHAR(32)  NOT NULL,
	`APP_UID` VARCHAR(32)  NOT NULL,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`EVN_UID_THROW` VARCHAR(32)  NOT NULL,
	`EVN_UID_CATCH` VARCHAR(32)  NOT NULL,
	`MSGAPP_VARIABLES` MEDIUMTEXT  NOT NULL,
	`MSGAPP_CORRELATION` VARCHAR(512) default '' NOT NULL,
	`MSGAPP_THROW_DATE` DATETIME  NOT NULL,
	`MSGAPP_CATCH_DATE` DATETIME,
	`MSGAPP_STATUS` VARCHAR(25) default 'UNREAD' NOT NULL,
	PRIMARY KEY (`MSGAPP_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- ELEMENT_TASK_RELATION
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `ELEMENT_TASK_RELATION`;


CREATE TABLE `ELEMENT_TASK_RELATION`
(
	`ETR_UID` VARCHAR(32)  NOT NULL,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`ELEMENT_UID` VARCHAR(32)  NOT NULL,
	`ELEMENT_TYPE` VARCHAR(50) default '' NOT NULL,
	`TAS_UID` VARCHAR(32)  NOT NULL,
	PRIMARY KEY (`ETR_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- USR_REPORTING
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `USR_REPORTING`;


CREATE TABLE `USR_REPORTING`
(
	`USR_UID` VARCHAR(32)  NOT NULL,
	`TAS_UID` VARCHAR(32)  NOT NULL,
	`PRO_UID` VARCHAR(32)  NOT NULL,
	`MONTH` INTEGER default 0 NOT NULL,
	`YEAR` INTEGER default 0 NOT NULL,
	`TOTAL_QUEUE_TIME_BY_TASK` DECIMAL(7,2) default 0,
	`TOTAL_TIME_BY_TASK` DECIMAL(7,2) default 0,
	`TOTAL_CASES_IN` DECIMAL(7,2) default 0,
	`TOTAL_CASES_OUT` DECIMAL(7,2) default 0,
	`USER_HOUR_COST` DECIMAL(7,2) default 0,
	`AVG_TIME` DECIMAL(7,2) default 0,
	`SDV_TIME` DECIMAL(7,2) default 0,
	`CONFIGURED_TASK_TIME` DECIMAL(7,2) default 0,
	`TOTAL_CASES_OVERDUE` DECIMAL(7,2) default 0,
	`TOTAL_CASES_ON_TIME` DECIMAL(7,2) default 0,
	`PRO_COST` DECIMAL(7,2) default 0,
	`PRO_UNIT_COST` VARCHAR(50) default '',
	PRIMARY KEY (`USR_UID`,`TAS_UID`,`MONTH`,`YEAR`),
	KEY `indexReporting`(`USR_UID`, `TAS_UID`, `PRO_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Data calculated users by task';
#-----------------------------------------------------------------------------
#-- PRO_REPORTING
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `PRO_REPORTING`;


CREATE TABLE `PRO_REPORTING`
(
	`PRO_UID` VARCHAR(32)  NOT NULL,
	`MONTH` INTEGER default 0 NOT NULL,
	`YEAR` INTEGER default 0 NOT NULL,
	`AVG_TIME` DECIMAL(7,2) default 0,
	`SDV_TIME` DECIMAL(7,2) default 0,
	`TOTAL_CASES_IN` DECIMAL(7,2) default 0,
	`TOTAL_CASES_OUT` DECIMAL(7,2) default 0,
	`CONFIGURED_PROCESS_TIME` DECIMAL(7,2) default 0,
	`CONFIGURED_PROCESS_COST` DECIMAL(7,2) default 0,
	`TOTAL_CASES_OPEN` DECIMAL(7,2) default 0,
	`TOTAL_CASES_OVERDUE` DECIMAL(7,2) default 0,
	`TOTAL_CASES_ON_TIME` DECIMAL(7,2) default 0,
	`PRO_COST` DECIMAL(7,2) default 0,
	`PRO_UNIT_COST` VARCHAR(50) default '',
	PRIMARY KEY (`PRO_UID`,`MONTH`,`YEAR`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Data calculated by process';
#-----------------------------------------------------------------------------
#-- DASHBOARD
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `DASHBOARD`;


CREATE TABLE `DASHBOARD`
(
	`DAS_UID` VARCHAR(32) default '' NOT NULL,
	`DAS_TITLE` VARCHAR(255) default '' NOT NULL,
	`DAS_DESCRIPTION` MEDIUMTEXT,
	`DAS_CREATE_DATE` DATETIME  NOT NULL,
	`DAS_UPDATE_DATE` DATETIME,
	`DAS_STATUS` TINYINT default 1 NOT NULL,
	PRIMARY KEY (`DAS_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Dashboard definitions.';
#-----------------------------------------------------------------------------
#-- DASHBOARD_INDICATOR
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `DASHBOARD_INDICATOR`;


CREATE TABLE `DASHBOARD_INDICATOR`
(
	`DAS_IND_UID` VARCHAR(32) default '' NOT NULL,
	`DAS_UID` VARCHAR(32) default '' NOT NULL,
	`DAS_IND_TYPE` VARCHAR(32) default '' NOT NULL,
	`DAS_IND_TITLE` VARCHAR(255) default '' NOT NULL,
	`DAS_IND_GOAL` DECIMAL(7,2) default 0,
	`DAS_IND_DIRECTION` TINYINT default 2 NOT NULL,
	`DAS_UID_PROCESS` VARCHAR(32) default '' NOT NULL,
	`DAS_IND_FIRST_FIGURE` VARCHAR(32) default '',
	`DAS_IND_FIRST_FREQUENCY` VARCHAR(32) default '',
	`DAS_IND_SECOND_FIGURE` VARCHAR(32) default '',
	`DAS_IND_SECOND_FREQUENCY` VARCHAR(32) default '',
	`DAS_IND_CREATE_DATE` DATETIME  NOT NULL,
	`DAS_IND_UPDATE_DATE` DATETIME,
	`DAS_IND_STATUS` TINYINT default 1 NOT NULL,
	PRIMARY KEY (`DAS_IND_UID`),
	KEY `indexDashboard`(`DAS_UID`, `DAS_IND_TYPE`),
	CONSTRAINT `fk_dashboard_indicator_dashboard`
		FOREIGN KEY (`DAS_UID`)
		REFERENCES `DASHBOARD` (`DAS_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Dashboard Indicators definitions.';
#-----------------------------------------------------------------------------
#-- DASHBOARD_DAS_IND
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `DASHBOARD_DAS_IND`;


CREATE TABLE `DASHBOARD_DAS_IND`
(
	`DAS_UID` VARCHAR(32) default '' NOT NULL,
	`OWNER_UID` VARCHAR(32) default '' NOT NULL,
	`OWNER_TYPE` VARCHAR(15) default '' NOT NULL,
	PRIMARY KEY (`DAS_UID`,`OWNER_UID`),
	CONSTRAINT `fk_dashboard_indicator_dashboard_das_ind`
		FOREIGN KEY (`DAS_UID`)
		REFERENCES `DASHBOARD` (`DAS_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Dashboard definitions to user.';
#-----------------------------------------------------------------------------
#-- CATALOG
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `CATALOG`;


CREATE TABLE `CATALOG`
(
	`CAT_UID` VARCHAR(32) default '' NOT NULL,
	`CAT_LABEL_ID` VARCHAR(100) default '' NOT NULL,
	`CAT_TYPE` VARCHAR(100) default '' NOT NULL,
	`CAT_FLAG` VARCHAR(50) default '',
	`CAT_OBSERVATION` MEDIUMTEXT,
	`CAT_CREATE_DATE` DATETIME  NOT NULL,
	`CAT_UPDATE_DATE` DATETIME,
	PRIMARY KEY (`CAT_UID`,`CAT_TYPE`),
	KEY `indexType`(`CAT_TYPE`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Definitions catalog.';
#-----------------------------------------------------------------------------
#-- SCRIPT_TASK
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `SCRIPT_TASK`;


CREATE TABLE `SCRIPT_TASK`
(
	`SCRTAS_UID` VARCHAR(32) default '' NOT NULL,
	`PRJ_UID` VARCHAR(32) default '' NOT NULL,
	`ACT_UID` VARCHAR(32) default '' NOT NULL,
	`SCRTAS_OBJ_TYPE` VARCHAR(10) default 'TRIGGER' NOT NULL,
	`SCRTAS_OBJ_UID` VARCHAR(32) default '' NOT NULL,
	PRIMARY KEY (`SCRTAS_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- TIMER_EVENT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `TIMER_EVENT`;


CREATE TABLE `TIMER_EVENT`
(
	`TMREVN_UID` VARCHAR(32)  NOT NULL,
	`PRJ_UID` VARCHAR(32)  NOT NULL,
	`EVN_UID` VARCHAR(32)  NOT NULL,
	`TMREVN_OPTION` VARCHAR(50) default 'DAILY' NOT NULL,
	`TMREVN_START_DATE` DATE,
	`TMREVN_END_DATE` DATE,
	`TMREVN_DAY` VARCHAR(5) default '' NOT NULL,
	`TMREVN_HOUR` VARCHAR(5) default '' NOT NULL,
	`TMREVN_MINUTE` VARCHAR(5) default '' NOT NULL,
	`TMREVN_CONFIGURATION_DATA` MEDIUMTEXT  NOT NULL,
	`TMREVN_NEXT_RUN_DATE` DATETIME,
	`TMREVN_LAST_RUN_DATE` DATETIME,
	`TMREVN_LAST_EXECUTION_DATE` DATETIME,
	`TMREVN_STATUS` VARCHAR(25) default 'ACTIVE' NOT NULL,
	PRIMARY KEY (`TMREVN_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- EMAIL_EVENT
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `EMAIL_EVENT`;


CREATE TABLE `EMAIL_EVENT`
(
	`EMAIL_EVENT_ID` INTEGER  NOT NULL AUTO_INCREMENT,
	`EMAIL_EVENT_UID` VARCHAR(32)  NOT NULL,
	`PRJ_UID` VARCHAR(32) default '' NOT NULL,
	`EVN_UID` VARCHAR(32)  NOT NULL,
	`EMAIL_EVENT_FROM` VARCHAR(100) default '' NOT NULL,
	`EMAIL_EVENT_TO` MEDIUMTEXT  NOT NULL,
	`EMAIL_EVENT_SUBJECT` VARCHAR(255) default '',
	`PRF_UID` VARCHAR(32) default '',
	`EMAIL_SERVER_UID` VARCHAR(32) default '',
	`EMAIL_EVENT_CREATE` DATETIME  NOT NULL,
	`EMAIL_EVENT_UPDATE` DATETIME  NOT NULL,
	PRIMARY KEY (`EMAIL_EVENT_ID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
#-----------------------------------------------------------------------------
#-- NOTIFICATION_DEVICE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `NOTIFICATION_DEVICE`;


CREATE TABLE `NOTIFICATION_DEVICE`
(
	`DEV_UID` VARCHAR(32) default '' NOT NULL,
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`SYS_LANG` VARCHAR(10) default '',
	`DEV_REG_ID` VARCHAR(255) default '' NOT NULL,
	`DEV_TYPE` VARCHAR(50) default '' NOT NULL,
	`DEV_CREATE` DATETIME  NOT NULL,
	`DEV_UPDATE` DATETIME  NOT NULL,
	PRIMARY KEY (`DEV_UID`,`USR_UID`),
	KEY `indexUserNotification`(`USR_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Definitions Notification device.';
#-----------------------------------------------------------------------------
#-- GMAIL_RELABELING
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `GMAIL_RELABELING`;


CREATE TABLE `GMAIL_RELABELING`
(
	`LABELING_UID` VARCHAR(32)  NOT NULL,
	`CREATE_DATE` DATETIME  NOT NULL,
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	`CURRENT_LAST_INDEX` INTEGER default 0 NOT NULL,
	`UNASSIGNED` INTEGER default 0 NOT NULL,
	`STATUS` VARCHAR(32) default 'pending' NOT NULL,
	`MSG_ERROR` MEDIUMTEXT,
	PRIMARY KEY (`LABELING_UID`),
	KEY `indexStatus`(`STATUS`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Task for label relabaling';
#-----------------------------------------------------------------------------
#-- NOTIFICATION_QUEUE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `NOTIFICATION_QUEUE`;


CREATE TABLE `NOTIFICATION_QUEUE`
(
	`NOT_UID` VARCHAR(32)  NOT NULL,
	`DEV_TYPE` VARCHAR(50)  NOT NULL,
	`DEV_UID` MEDIUMTEXT  NOT NULL,
	`NOT_MSG` MEDIUMTEXT  NOT NULL,
	`NOT_DATA` MEDIUMTEXT  NOT NULL,
	`NOT_STATUS` VARCHAR(150)  NOT NULL,
	`NOT_SEND_DATE` DATETIME  NOT NULL,
	`APP_UID` VARCHAR(32) default '' NOT NULL,
	`DEL_INDEX` INTEGER default 0 NOT NULL,
	PRIMARY KEY (`NOT_UID`),
	KEY `indexNotStatus`(`NOT_STATUS`)
)ENGINE=InnoDB ;
#-----------------------------------------------------------------------------
#-- PLUGINS_REGISTRY
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `PLUGINS_REGISTRY`;


CREATE TABLE `PLUGINS_REGISTRY`
(
	`PR_UID` VARCHAR(32) default '' NOT NULL,
	`PLUGIN_NAMESPACE` VARCHAR(100)  NOT NULL,
	`PLUGIN_DESCRIPTION` MEDIUMTEXT,
	`PLUGIN_CLASS_NAME` VARCHAR(100)  NOT NULL,
	`PLUGIN_FRIENDLY_NAME` VARCHAR(150) default '',
	`PLUGIN_FILE` VARCHAR(250)  NOT NULL,
	`PLUGIN_FOLDER` VARCHAR(100)  NOT NULL,
	`PLUGIN_SETUP_PAGE` VARCHAR(100) default '',
	`PLUGIN_COMPANY_LOGO` VARCHAR(100) default '',
	`PLUGIN_WORKSPACES` VARCHAR(100) default '',
	`PLUGIN_VERSION` VARCHAR(50) default '',
	`PLUGIN_ENABLE` TINYINT default 0,
	`PLUGIN_PRIVATE` TINYINT default 0,
	`PLUGIN_MENUS` MEDIUMTEXT,
	`PLUGIN_FOLDERS` MEDIUMTEXT,
	`PLUGIN_TRIGGERS` MEDIUMTEXT,
	`PLUGIN_PM_FUNCTIONS` MEDIUMTEXT,
	`PLUGIN_REDIRECT_LOGIN` MEDIUMTEXT,
	`PLUGIN_STEPS` MEDIUMTEXT,
	`PLUGIN_CSS` MEDIUMTEXT,
	`PLUGIN_JS` MEDIUMTEXT,
	`PLUGIN_REST_SERVICE` MEDIUMTEXT,
	`PLUGIN_TASK_EXTENDED_PROPERTIES` MEDIUMTEXT,
	`PLUGIN_ATTRIBUTES` MEDIUMTEXT,
	PRIMARY KEY (`PR_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Details of plugins registry';
# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
