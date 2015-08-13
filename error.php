<?php

namespace Jitsu;

/**
 * Set whether the current script should silence all errors.
 *
 * Either turns on full error reporting or silences it completely and hides
 * the `X-Powered-By` header when run as a web app.
 *
 * @param bool $value Whether the script should run in private mode.
 */
function setScriptPrivacy($value) {
	setErrorVisibility($value);
	if($value) {
		header_remove('X-Powered-By');
	}
}

/* Activate or deactivate all error reporting. In either case, convert errors
 * to `ErrorException`s (see `handleErrors`). */
function setErrorVisibility($value) {
	initErrorVisiblity($value);
	handleExceptions($value);
	handleErrors();
	handleFatalErrors($value);
}

/* Set up jitsu-style error handling. The handler simply converts errors to
 * `ErrorException`s whenever an error is encountered (except when the `@`
 * operator was used). */
function handleErrors() {
	set_error_handler(function($code, $msg, $file, $line) {
		/* `error_reporting()` becomes `0` if the `@` operator was
		 * used. */
		if(error_reporting()) {
			throw new \ErrorException($msg, 0, $code, $file, $line);
		}
	});
}

/* Set up jitsu-style fatal error handling. Depending on the `$visible`
 * argument, the handler either prints the error or silences it. Note that in
 * either case, in order to silence the usual error output, the default output
 * for *all* errors is disabled. */
function handleFatalErrors($visible) {
	ini_set('display_errors', false);
	if($visible) {
		register_shutdown_function(function() {
			$e = error_get_last();
			if($e) {
				$e += array(
					'line' => '?',
					'file' => '?'
				);
				extract($e);
				echo ucwords(errorName($type)), ': ', $message, "\n";
				echo '  at ', $file, ':', $line, "\n";
			}
		});
	}
}

/* Set up jitsu-style exception handling. Depending on the `$visible`
 * argument, the handler either prints a stack trace and exits or silently
 * exits. */
function handleExceptions($visible) {
	if($visible) {
		set_exception_handler(function($e) {
			printStackTrace($e);
			exit(1);
		});
	} else {
		set_exception_handler(function() {
			exit(1);
		});
	}
}

/* Set whether errors should be made visible or silenced (redundant if the
 * default error handler is overridden). */
function initErrorVisiblity($value) {

	$value = (bool) $value;

	/* Display startup errors which cannot be handled by the normal error
	 * handler. */
	ini_set('display_startup_errors', $value);

	/* Display errors (redundant if the default error handler is
	 * overridden). */
	ini_set('display_errors', $value);

	/* Report errors at all severity levels (redundant if the default
	 * error handler is overridden). */
	error_reporting($value ? E_ALL : 0);

	/* Report detected memory leaks. */
	ini_set('report_memleaks', $value);
}

/* Print a jitsu-style stack trace of an exception. */
function printStackTrace($e) {
	echo get_class($e), ' [', $e->getCode(), ']: ', $e->getMessage(), "\n";
	foreach($e->getTrace() as $level) {
		$level += array(
			'class' => '',
			'type' => '',
			'function' => '?',
			'file' => '',
			'line' => ''
		);
		extract($level);
		echo '  ', $class, $type, $function, "\n";
		if($file !== '') {
			echo '    at ', $file, ':', $line, "\n";
		}
	}
}

/* Get a descriptive string for one of PHP's error constants. */
function errorName($type) {
	static $map = array(
		E_ERROR => 'fatal error',
		E_WARNING => 'warning',
		E_PARSE => 'parsing error',
		E_NOTICE => 'notice',
		E_CORE_ERROR => 'startup error',
		E_CORE_WARNING => 'startup warning',
		E_COMPILE_ERROR => 'compilation error',
		E_COMPILE_WARNING => 'compilation warning',
		E_USER_ERROR => 'user-generated error',
		E_USER_WARNING => 'user-generated warning',
		E_USER_NOTICE => 'user-generated notice',
		E_RECOVERABLE_ERROR => 'error',
		E_DEPRECATED => 'deprecation notice',
		E_USER_DEPRECATED => 'user-generated deprecation notice'
	);
	return isset($map[$type]) ? $map[$type] : null;
}
