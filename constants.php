<?php
/*
 * File to declare constants for IDE
 * Because we are using defifn to declare them in app
 */

const LOGFILE_SYSTEM = 'system.log';
const LOGFILE_NOT_FOUND = 'not-found.log';
const LOGFILE_DEBUG = 'debug.log';
const LOGFILE_HACK = 'hack.log';
const LOGFILE_SQL = 'sql.log';

const DOMAIN_CACHE = 'cache';
const DOMAIN_LOGS = 'logs';
const DOMAIN_TEST = 'test';

/**
 * @deprecated Use DEBUG_ENABLED and ApplicationKernel::isDebugEnabled()
 */
const DEV_VERSION = true;
const DEBUG_ENABLED = true;
