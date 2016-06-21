<?php
/**
 * Loader File for the core sources
 */

require_once 'core.php';
require_once 'hooks.php';
require_once 'validators.php';
require_once 'exceptions.php';

// Core
// require_once pathOf(LIBSDIR.'core/core.php');

// Important
// addAutoload('ConfigCore',						'core/ConfigCore.php');

// require_once pathOf(LIBSDIR.'core/hooks.php');
// require_once pathOf(LIBSDIR.'core/validators.php');

// addAutoload('UserException',					'core/userexception_class.php');
// addAutoload('NotFoundException',				'core/userexception_class.php');
// addAutoload('ForbiddenException',				'core/userexception_class.php');
// addAutoload('OperationCancelledException',		'core/userexception_class.php');
// addAutoload('SQLException',						'core/sqlexception_class.php');
// addAutoload('FormToken',						'core/FormToken');
// addAutoload('SlugGenerator',					'core/SlugGenerator');