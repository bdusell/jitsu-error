jitsu/error
-----------

This package is used to override PHP's default error and exception handlers
with more disciplined behavior, an absolutely essential measure which greatly
aids debugging during development and makes for more secure applications in
production.

This package is part of [Jitsu](https://github.com/bdusell/jitsu).

## Installation

Install this package with [Composer](https://getcomposer.org/):

```sh
composer require jitsu/error
```

## About

It's no secret that PHP's default error handling behavior is terrible, and that
its arbitrary distinction between "errors" and exceptions makes little sense.
This package defines a function, `Jitsu\bootstrap()`, which overrides PHP's
default error handlers at a global level with more sensible behavior.
Specifically, it registers a simple error handler which converts all errors to
`ErrorException`s which can be caught and handled, and also registers a global
exception handler which optionally prints a full stack trace before exiting.

Note that the overrides do not restrict you in any way from intercepting and
handling exceptions/errors before they reach the global handlers; this merely
changes how _unhandled_ exceptions and errors are reported (or not reported).
The global overrides are primarily useful for debugging, since by default
errors do not halt the script and can easily go unnoticed. However, they are
equally important when an application is deployed in a live environment, for
they ensure that stack traces and other sensitive information are not shown to
end users. After calling `Jitsu\bootstrap()`, you still can and should wrap
your code in a `try`/`catch` block with application-specific error handling
logic. This would allow you, for instance, to display a custom 500 error page.

The very first commands in your PHP script should be:

```php
<?php
require_once __DIR__ . '/vendor/jitsu/error/error.php';
\Jitsu\bootstrap(true); // Turns on all error reporting for debugging
```

It is important to make the call to `bootstrap()` as early as possible
in order to ensure that no errors beforehand can slip by. By design, this
package is not auto-loaded, since ideally it should be included even before
auto-loading is registered.

Note that `bootstrap()` has two modes: debug and production. In a production
environment, you would call `bootstrap` with an argument of `false` to indicate
that error reporting should be suppressed:

```php
<?php
require_once __DIR__ . '/vendor/jitsu/error/error.php';
\Jitsu\bootstrap(false); // Turns off all error reporting for production
```

## Advanced Usage

At this point, you may be wondering, "If I have to call
`bootstrap($run_in_debug_mode)` at the
beginning of my script, how do I configure the variable `$run_in_debug_mode`?"

One solution is to bite the bullet and read it from a configuration file,
database, etc. beforehand, hoping that all goes well, which may very well be
the case most of the time. However, you do run the risk of missing vital errors
which might occur when reading your application's configuration settings.

A safer solution is not to use a variable at all, but, through a clever build
process, to generate your application's entry point (`index.php`) with the
appropriate `true` or `false` constant as part of a pre-processing step.

Let's see how we might get this to work.

Suppose we have the following project structure:

```
deploy.sh
prepare.sh
build/
  dev/
  prod/
app/
  main.php
  composer.json
  vendor/
bootstrap/
  index-dev.php
  index-prod.php
```

**deploy.sh**

```sh
#/bin/sh
scp -r build/prod/. user@example.com:/var/www
```

**prepare.sh**

```sh
#/bin/sh
mkdir -p build/$1 && \
cd build/$1 && \
ln -sf ../bootstrap/index-$1.php index.php && \
ln -sf ../app .
```

**bootstrap/index-dev.php**

```php
<?php
require_once 'app/vendor/jitsu/error/error.php';
\Jitsu\bootstrap(true);
require 'app/main.php';
```

**bootstrap/index-prod.php**

```php
<?php
require_once 'app/vendor/jitsu/error/error.php';
\Jitsu\bootstrap(false);
require 'app/main.php';
```

**app/main.php**

```php
<?php
require __DIR__ . '/vendor/autoload.php';
$config = MyApp::readConfig();
try {
	// Do application-specific stuff
	MyApp::routeRequest($config);
} catch(\Exception $e) {
	echo "Oops! Something went wrong.\n\n";
	if($config->show_stack_traces) {
		Jitsu\printException($e);
	}
}
```

Here, we have a build deployment script, a build preparation script, a `build/`
directory that is not checked into version control, an `app/` directory
containing application code, and a `bootstrap/` directory containing multiple
versions of `index.php`. We have, in effect, a system which manages two
"builds" named `dev` and `prod`.

We can generate a build under `build/` by symlinking `index.php` to the
appropriate version and creating a symlink to the `app/` directory as well. In
this way, we have completely separate entry points for the two builds, with
distinct `index.php` files shimmed in, while minimizing the amount of code
duplicated in each. In the `dev` build, all debugging features are turned on;
in the `prod` build, all errors, stack traces, etc. are silenced.

In the shared `main.php` file, we safely read in configuration settings after
the error handlers have been set up. We also display any exceptions thrown
when handling the HTTP request, but only if the configuration settings we read
before indicate that we should show stack traces.

We would generate a build by running `./prepare.sh dev` or `./prepare.sh prod`.
We could then deploy the production build to some remote server by running
`./deploy.sh`.

Of course, this is only a simple example which could undergo many variations.

## Namespace

All functions are defined under the namespace `Jitsu`.

## API

Include the file `error.php`.

### Jitsu\\bootstrap($debug = true)

Set whether the PHP script should run in debug mode or production mode,
overriding PHP's global error and exception handlers with more sensible
behavior.

This function should be called at the very beginning of a PHP application's
entry point in order to bootstrap error and exception handling as early as
possible; otherwise, errors which occur beforehand may slip by unnoticed.

If `$debug` is true, then the script's global error and exception handlers
will be overridden so that all errors are displayed, which is appropriate
for debugging in a development environment. If `$debug` is false, then all
errors will be suppressed and simply cause the script to exit silently,
which is appropriate for a production environment.

In either case, errors will always be converted to `ErrorException`s which
can be caught and handled before reaching the global exception handler.

Additionally, if `$debug` is false, then the default `X-Powered-By` header
will be removed.

|   | Type | Description |
|---|------|-------------|
| **`$debug`** | `bool` | Whether the script should run in debug mode; otherwise, it will run in production mode. |

### Jitsu\\overrideErrorHandlers($debug = true)

Override PHP's default error and exception handlers so that all errors are
either displayed or hidden.

If `$debug` is true, then all unhandled exceptions, start-up errors, fatal
errors, etc. will be displayed when they are encountered. If `$debug` is
false, then all of them will be silenced.

In either case, errors will always be converted to `ErrorException`s which
can be caught and handled before reaching the global exception handler.

|   | Type | Description |
|---|------|-------------|
| **`$debug`** | `bool` | Whether to display errors and exceptions or silence them. |

### Jitsu\\initErrorVisibility($debug = true)

Configure whether to display PHP errors or silence them.

Some of the settings affected here are redundant if the error handler is
overridden, but some of them pertain to errors which the error handler
does not receive, namely start-up errors and memory leaks.

|   | Type | Description |
|---|------|-------------|
| **`$debug`** | `bool` | Whether to display errors or silence them. |

### Jitsu\\overrideErrorHandler()

Override the global error handler so that all PHP errors are converted to
exceptions.

The handler simply converts errors to `ErrorException`s whenever they are
encountered (except when the `@` error suppression operator is used).

### Jitsu\\overrideFatalErrorHandler($debug = true)

Override the global fatal error handler with more useful behavior.

By design, PHP does not allow fatal errors to be handled. However, we can
register a shutdown function to print (or not print) information about the
error during the last gasps of the program, which is better than the default
behavior.

Note that in either case, in order to silence the usual error output, the
default output for *all* PHP errors is disabled.

|   | Type | Description |
|---|------|-------------|
| **`$debug`** | `bool` | Whether to display fatal errors or silence them. |

### Jitsu\\overrideExceptionHandler($debug = true)

Override the global exception handler with more useful behavior.

If `$debug` is true, then unhandled exceptions will cause the script to
print a stack trace and then exit. If `$debug` is false, then the script
will exit silently.

|   | Type | Description |
|---|------|-------------|
| **`$debug`** | `bool` | Whether to display stack traces. |

### Jitsu\\printException($e)

Pretty-print an exception and its stack trace.

|   | Type |
|---|------|
| **`$e`** | `\Exception` |

### Jitsu\\errorName($type)

Get a descriptive string for one of PHP's error constants.

|   | Type | Description |
|---|------|-------------|
| **`$type`** | `int` | One of PHP's `E_` error constants. |
| returns | `string|null` |  |

### Jitsu\\removePoweredByHeader()

Configure the script not to send the default `X-Powered-By` header in the
HTTP response.

