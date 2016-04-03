Jitsu Error Handling
--------------------

It's no secret that PHP's default error handling behavior is very, very bad,
and that its arbitrary distinction between "errors" and exceptions makes little
sense. This package defines a small number of functions which override PHP's
default error handlers at a global level with sensible behavior. Specifically,
these functions can be used to register a simple error handler which converts
all errors to `ErrorException`s, and also to register an exception handler
which always exits the script. You have the option of registering an exception
handler which either prints full stack traces before exiting (e.g. in
development mode) or exits silently (e.g. in production mode).

Note that this does not restrict you in any way from intercepting and handling
exceptions/errors before they reach the global handlers; this merely changes
how _unhandled_ exceptions and errors are reported (or not reported). This is
primarily useful when debugging, since by default errors do not halt the script
and can easily go unnoticed. You can and should wrap your code in a
`try`/`catch` block with application-specific error handling logic. This would
allow you, for instance, to display a custom 500 error page.

You should include this package and register the error handlers as the very
first commands of your script, in order to ensure that no errors triggered by
application logic beforehand can slip by. By design, this package is not
auto-loaded, as it should be included before auto-loading is registered.

Here's a quick development-mode example:

```php
<?php
require_once __DIR__ . '/vendor/jitsu/error/error.php';
\Jitsu\setScriptPrivacy(false); // turn on all error reporting for debugging
require __DIR__ . '/my_main_file.php';
```

Similarly, for production:

```php
<?php
require_once __DIR__ . '/vendor/jitsu/error/error.php';
\Jitsu\setScriptPrivacy(true); // if we somehow let an unhandled exception
                               // slip by, exit silently
require __DIR__ . '/my_main_file.php';
```

## API

Include the file `vendor/jitsu/error/error.php`.

### function Jitsu\setScriptPrivacy($value)

Set whether the current script should silence all errors.

Either turns on full error reporting or silences it completely and hides
the `X-Powered-By` header when run as a web app.

| Type | Parameter | Description |
|------|-----------|-------------|
| `bool` | **`$value`** | Whether the script should run in private mode. |

### function Jitsu\setErrorVisibility($value)

Activate or deactivate all error reporting.

In either case, converts errors to `ErrorException`s (see `handleErrors`).

| Type | Parameter | Description |
|------|-----------|-------------|
| `bool` | **`$value`** | Whether to report errors. |

### function Jitsu\handleErrors()

Register an error handler which converts errors to exceptions.

The handler simply converts errors to `ErrorException`s whenever an error
is encountered (except when the `@` operator is used).

### function Jitsu\handleFatalErrors($visible)

Register a pre-defined fatal error handler.

By design, PHP does not allow fatal errors to be handled. However, we can
register a shutdown function to print (or not print) information about the
error during the last gasps of the program, which is better than the default
behavior.

Note that in either case, in order to silence the usual error output, the
default output for *all* errors is disabled.

| Type | Parameter | Description |
|------|-----------|-------------|
| `bool` | **`$visible`** | Whether to report fatal errors or silence them. |

### function Jitsu\handleExceptions($visible)

Register a global exception handler which can pretty-print stack traces.

The exception handler always exits the script.

| Type | Parameter | Description |
|------|-----------|-------------|
| `bool` | **`$visible`** | Whether to report uncaught exceptions or exit silently. |

### function Jitsu\initErrorVisiblity($value)

Set whether errors should be made visible or silenced.

This is redundant if the default error handler is overridden.

| Type | Parameter | Description |
|------|-----------|-------------|
| `bool` | **`$value`** |  |

### function Jitsu\printStackTrace($e)

Pretty-print an exception and its stack trace.

| Type | Parameter | Description |
|------|-----------|-------------|
| `\Exception` | **`$e`** |  |

### function Jitsu\errorName($type)

Get a descriptive string for one of PHP's error constants.

| Type | Parameter | Description |
|------|-----------|-------------|
| `int` | **`$type`** | One of PHP's `E_` error constants. |

##### Return Value

`string|null`
