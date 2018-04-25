
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

#-----------------------------------------------------------------------------
#-- RBAC_PERMISSIONS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `RBAC_PERMISSIONS`;


CREATE TABLE `RBAC_PERMISSIONS`
(
	`PER_UID` VARCHAR(32) default '' NOT NULL,
	`PER_CODE` VARCHAR(64) default '' NOT NULL,
	`PER_CREATE_DATE` DATETIME,
	`PER_UPDATE_DATE` DATETIME,
	`PER_STATUS` INTEGER default 1 NOT NULL,
	`PER_SYSTEM` VARCHAR(32) default '00000000000000000000000000000002' NOT NULL,
	PRIMARY KEY (`PER_UID`),
	KEY `indexPermissionsCode`(`PER_CODE`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Permissions';
#-----------------------------------------------------------------------------
#-- RBAC_ROLES
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `RBAC_ROLES`;


CREATE TABLE `RBAC_ROLES`
(
	`ROL_UID` VARCHAR(32) default '' NOT NULL,
	`ROL_PARENT` VARCHAR(32) default '' NOT NULL,
	`ROL_SYSTEM` VARCHAR(32) default '' NOT NULL,
	`ROL_CODE` VARCHAR(32) default '' NOT NULL,
	`ROL_CREATE_DATE` DATETIME,
	`ROL_UPDATE_DATE` DATETIME,
	`ROL_STATUS` INTEGER default 1 NOT NULL,
	PRIMARY KEY (`ROL_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Roles';
#-----------------------------------------------------------------------------
#-- RBAC_ROLES_PERMISSIONS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `RBAC_ROLES_PERMISSIONS`;


CREATE TABLE `RBAC_ROLES_PERMISSIONS`
(
	`ROL_UID` VARCHAR(32) default '' NOT NULL,
	`PER_UID` VARCHAR(32) default '' NOT NULL,
	PRIMARY KEY (`ROL_UID`,`PER_UID`),
	KEY `indexRolesPermissions`(`ROL_UID`, `PER_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Permissions of the roles';
#-----------------------------------------------------------------------------
#-- RBAC_SYSTEMS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `RBAC_SYSTEMS`;


CREATE TABLE `RBAC_SYSTEMS`
(
	`SYS_UID` VARCHAR(32) default '' NOT NULL,
	`SYS_CODE` VARCHAR(32) default '' NOT NULL,
	`SYS_CREATE_DATE` DATETIME,
	`SYS_UPDATE_DATE` DATETIME,
	`SYS_STATUS` INTEGER default 0 NOT NULL,
	PRIMARY KEY (`SYS_UID`),
	KEY `indexSystemCode`(`SYS_CODE`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Systems';
#-----------------------------------------------------------------------------
#-- RBAC_USERS
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `RBAC_USERS`;


CREATE TABLE `RBAC_USERS`
(
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`USR_USERNAME` VARCHAR(100) default '' NOT NULL,
	`USR_PASSWORD` VARCHAR(128) default '' NOT NULL,
	`USR_FIRSTNAME` VARCHAR(50) default '' NOT NULL,
	`USR_LASTNAME` VARCHAR(50) default '' NOT NULL,
	`USR_EMAIL` VARCHAR(100) default '' NOT NULL,
	`USR_DUE_DATE` DATE  NOT NULL,
	`USR_CREATE_DATE` DATETIME,
	`USR_UPDATE_DATE` DATETIME,
	`USR_STATUS` INTEGER default 1 NOT NULL,
	`USR_AUTH_TYPE` VARCHAR(32) default '' NOT NULL,
	`UID_AUTH_SOURCE` VARCHAR(32) default '' NOT NULL,
	`USR_AUTH_USER_DN` VARCHAR(255) default '' NOT NULL,
	`USR_AUTH_SUPERVISOR_DN` VARCHAR(255) default '' NOT NULL,
	PRIMARY KEY (`USR_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Users';
#-----------------------------------------------------------------------------
#-- RBAC_USERS_ROLES
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `RBAC_USERS_ROLES`;


CREATE TABLE `RBAC_USERS_ROLES`
(
	`USR_UID` VARCHAR(32) default '' NOT NULL,
	`ROL_UID` VARCHAR(32) default '' NOT NULL,
	PRIMARY KEY (`USR_UID`,`ROL_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8' COMMENT='Roles of the users';
#-----------------------------------------------------------------------------
#-- AUTHENTICATION_SOURCE
#-----------------------------------------------------------------------------

DROP TABLE IF EXISTS `RBAC_AUTHENTICATION_SOURCE`;


CREATE TABLE `RBAC_AUTHENTICATION_SOURCE`
(
	`AUTH_SOURCE_UID` VARCHAR(32) default '' NOT NULL,
	`AUTH_SOURCE_NAME` VARCHAR(50) default '' NOT NULL,
	`AUTH_SOURCE_PROVIDER` VARCHAR(20) default '' NOT NULL,
	`AUTH_SOURCE_SERVER_NAME` VARCHAR(50) default '' NOT NULL,
	`AUTH_SOURCE_PORT` INTEGER default 389,
	`AUTH_SOURCE_ENABLED_TLS` INTEGER default 0,
	`AUTH_SOURCE_VERSION` VARCHAR(16) default '3' NOT NULL,
	`AUTH_SOURCE_BASE_DN` VARCHAR(128) default '' NOT NULL,
	`AUTH_ANONYMOUS` INTEGER default 0,
	`AUTH_SOURCE_SEARCH_USER` VARCHAR(128) default '' NOT NULL,
	`AUTH_SOURCE_PASSWORD` VARCHAR(150) default '' NOT NULL,
	`AUTH_SOURCE_ATTRIBUTES` VARCHAR(255) default '' NOT NULL,
	`AUTH_SOURCE_OBJECT_CLASSES` VARCHAR(255) default '' NOT NULL,
	`AUTH_SOURCE_DATA` MEDIUMTEXT,
	PRIMARY KEY (`AUTH_SOURCE_UID`)
)ENGINE=InnoDB  DEFAULT CHARSET='utf8';
# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
