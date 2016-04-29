<?php

namespace Jitsu;

/**
 * Set whether the PHP script should run in debug mode or production mode,
 * overriding PHP's global error and exception handlers with more sensible
 * behavior.
 *
 * This function should be called at the very beginning of a PHP application's
 * entry point in order to bootstrap error and exception handling as early as
 * possible; otherwise, errors which occur beforehand may slip by unnoticed.
 *
 * If `$debug` is true, then the script's global error and exception handlers
 * will be overridden so that all errors are displayed, which is appropriate
 * for debugging in a development environment. If `$debug` is false, then all
 * errors will be suppressed and simply cause the script to exit silently,
 * which is appropriate for a production environment.
 *
 * In either case, errors will always be converted to `ErrorException`s which
 * can be caught and handled before reaching the global exception handler.
 *
 * Additionally, if `$debug` is false, then the default `X-Powered-By` header
 * will be removed.
 *
 * @param bool $debug Whether the script should run in debug mode; otherwise,
 *                    it will run in production mode.
 */
function bootstrap($debug = true) {
	overrideErrorHandlers($debug);
	if(!$debug) removePoweredByHeader();
}

/**
 * Override PHP's default error and exception handlers so that all errors are
 * either displayed or hidden.
 *
 * If `$debug` is true, then all unhandled exceptions, start-up errors, fatal
 * errors, etc. will be displayed when they are encountered. If `$debug` is
 * false, then all of them will be silenced.
 *
 * In either case, errors will always be converted to `ErrorException`s which
 * can be caught and handled before reaching the global exception handler.
 *
 * @param bool $debug Whether to display errors and exceptions or silence them.
 */
function overrideErrorHandlers($debug = true) {
	initErrorVisibility($debug);
	overrideErrorHandler();
	overrideFatalErrorHandler($debug);
	overrideExceptionHandler($debug);
}

/**
 * Configure whether to display PHP errors or silence them.
 *
 * Some of the settings affected here are redundant if the error handler is
 * overridden, but some of them pertain to errors which the error handler
 * does not receive, namely start-up errors and memory leaks.
 *
 * @param bool $debug Whether to display errors or silence them.
 */
function initErrorVisibility($debug = true) {

	/* Display startup errors which cannot be handled by the normal error
	 * handler. */
	ini_set('display_startup_errors', $debug);

	/* Display errors (redundant if the default error handler is
	 * overridden). */
	ini_set('display_errors', $debug);

	/* Report errors at all severity levels (redundant if the default
	 * error handler is overridden). */
	error_reporting($debug ? E_ALL : 0);

	/* Report detected memory leaks. */
	ini_set('report_memleaks', $debug);
}

/**
 * Override the global error handler so that all PHP errors are converted to
 * exceptions.
 *
 * The handler simply converts errors to `ErrorException`s whenever they are
 * encountered (except when the `@` error suppression operator is used).
 */
function overrideErrorHandler() {
	set_error_handler(function($code, $msg, $file, $line) {
		/* `error_reporting()` becomes `0` if the `@` operator was
		 * used. */
		if(error_reporting()) {
			throw new \ErrorException($msg, 0, $code, $file, $line);
		}
	});
}

/**
 * Override the global fatal error handler with more useful behavior.
 *
 * By design, PHP does not allow fatal errors to be handled. However, we can
 * register a shutdown function to print (or not print) information about the
 * error during the last gasps of the program, which is better than the default
 * behavior.
 *
 * Note that in either case, in order to silence the usual error output, the
 * default output for *all* PHP errors is disabled.
 *
 * @param bool $debug Whether to display fatal errors or silence them.
 */
function overrideFatalErrorHandler($debug = true) {
	ini_set('display_errors', false);
	if($debug) {
		register_shutdown_function(function() {
			$e = error_get_last();
			if($e) {
				$e += array(
					'line' => '?',
					'file' => '?'
				);
				echo ucwords(errorName($e['type'])), ': ', $e['message'], "\n";
				echo '  at ', $e['file'], ':', $e['line'], "\n";
			}
		});
	}
}

/**
 * Override the global exception handler with more useful behavior.
 *
 * If `$debug` is true, then unhandled exceptions will cause the script to
 * print a stack trace and then exit. If `$debug` is false, then the script
 * will exit silently.
 *
 * @param bool $debug Whether to display stack traces.
 */
function overrideExceptionHandler($debug = true) {
	if($debug) {
		set_exception_handler(function($e) {
			printException($e);
			exit(1);
		});
	} else {
		set_exception_handler(function() {
			exit(1);
		});
	}
}

/**
 * Pretty-print an exception and its stack trace.
 *
 * @param \Exception $e
 */
function printException($e) {
	echo get_class($e), ' [', $e->getCode(), ']: ', $e->getMessage(), "\n";
	foreach($e->getTrace() as $level) {
		$level += array(
			'class' => '',
			'type' => '',
			'function' => '?',
			'file' => '',
			'line' => ''
		);
		echo '  ', $level['class'], $level['type'], $level['function'], "\n";
		if($level['file'] !== '') {
			echo '    at ', $level['file'], ':', $level['line'], "\n";
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

/**
 * Configure the script not to send the default `X-Powered-By` header in the
 * HTTP response.
 */
function removePoweredByHeader() {
	header_remove('X-Powered-By');
}
