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

/**
 * Activate or deactivate all error reporting.
 *
 * In either case, converts errors to `ErrorException`s (see `handleErrors`).
 *
 * @param bool $value Whether to report errors.
 */
function setErrorVisibility($value) {
	initErrorVisiblity($value);
	handleExceptions($value);
	handleErrors();
	handleFatalErrors($value);
}

/**
 * Register an error handler which converts errors to exceptions.
 *
 * The handler simply converts errors to `ErrorException`s whenever an error
 * is encountered (except when the `@` operator is used).
 */
function handleErrors() {
	set_error_handler(function($code, $msg, $file, $line) {
		/* `error_reporting()` becomes `0` if the `@` operator was
		 * used. */
		if(error_reporting()) {
			throw new \ErrorException($msg, 0, $code, $file, $line);
		}
	});
}

/**
 * Register a pre-defined fatal error handler.
 *
 * By design, PHP does not allow fatal errors to be handled. However, we can
 * register a shutdown function to print (or not print) information about the
 * error during the last gasps of the program, which is better than the default
 * behavior.
 *
 * Note that in either case, in order to silence the usual error output, the
 * default output for *all* errors is disabled.
 *
 * @param bool $visible Whether to report fatal errors or silence them.
 */
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

/**
 * Register a global exception handler which can pretty-print stack traces.
 *
 * The exception handler always exits the script.
 *
 * @param bool $visible Whether to report uncaught exceptions or exit silently.
 */
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

/**
 * Set whether errors should be made visible or silenced.
 *
 * This is redundant if the default error handler is overridden.
 *
 * @param bool $value
 */
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

/**
 * Pretty-print an exception and its stack trace.
 *
 * @param \Exception $e
 */
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

/**
 * Get a descriptive string for one of PHP's error constants.
 *
 * @param int $type One of PHP's `E_` error constants.
 * @return string|null
 */
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
