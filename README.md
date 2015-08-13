Jitsu Error Handling
--------------------

PHP's default error handling is trash. This package defines a small number of
functions which set up sane error handling behavior at a global level. They
give you the option of either reporting or silencing all possible errors.

Include the file `errors.php` to gain access to these functions. Activating
error reporting is useful when developing a project; silencing error reporting
is essential for production.

PHP errors are always converted to `ErrorException`s, and the script exits
immediately when an exception goes unhandled. When error reporting is on, the
stack trace is also printed.

Here's a quick example:

```php
<?php
include __DIR__ . '/vendor/jitsu/error/error.php';
\Jitsu\setScriptPrivacy(false); // turn on all error reporting
```
