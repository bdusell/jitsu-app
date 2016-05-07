jitsu/app
---------

This package implements a simple but powerful architecture for developing
modular, testable web applications in PHP. It consists primarily of an HTTP
request router, several built-in request handler types, and a general-purpose
configuration mechanism which is used to drive some of the router's behavior.

Using the routing mechanism implemented here gives you complete control over
URL routing (it is possible to hide the `.php` suffix from URLs) and an easy
way to propagate configuration settings from configuration files to request
handlers.

Moreover, where traditional PHP scripts access HTTP request data through global
variables such as `$_GET`, `$_COOKIE`, etc., an instance of `Application` is
fed _objects_ which represent the HTTP request and response. It is through these
objects that the application interacts with the outside world, making it
possible to stub them out during unit testing.

This module is thus designed to solve two problems: the need for a less
primitive way of routing HTTP requests, and the need to design applications in
a way that is amenable to configuration and testing.

This package is part of [Jitsu](https://github.com/bdusell/jitsu).

## Installation

Install this package with [Composer](https://getcomposer.org/):

```sh
composer require jitsu/app
```

## About

You're probably familiar with the usual URL routing mechanism in PHP: a URL
ending in `.php` is mapped to a PHP script on the server of the same name.
While this is fine for beginner projects or private tools, or whenever you're
just not self-conscious about using URLs ending in `.php`, using this default
setting has a number of problems:

* Using separate scripts for each page requires boilerplate code in every file
  for common tasks, such as setting up auto-loading, including libraries,
  performing authentication, printing headers and footers, etc. There is
  no single point of entry where these basic tasks can be handled.
* While you can use an `.htaccess` file to customize the URLs a bit, it is
  not straightforward to actually erase the `.php`-style URLs.
* It makes the layout of the project's source code visible to outsiders, which
  may not be desirable.
* There are many other interesting properties of a request besides its URL
  which might inform routing, such as its method or its `Accept` header.

This package implements an alternative routing mechanism which can be used in
place of URL rewriting. Given a request, the router dispatches it by querying a
list of handlers and handing off control to the first one which claims
responsibility. By configuring your web server to direct all requests to a
single PHP script which invokes this router (which can be achieved with a simple
command in an `.htaccess` file), you can use this package to get
rid of the unsightly `.php` suffix which normally appears on all URLs.

An imporant design feature of the router is that it interacts with the HTTP
request and response through an abstract, object-oriented interface, making it
possible to stub out these objects when testing your application. Using
dependency injection, you can send a simulated HTTP request to your application
and capture its response in test, provided it is designed around these
object-oriented interfaces. The request and response interfaces are found in
[jitsu/http](https://github.com/bdusell/jitsu-http).

Another integral feature of the router is its use of a configuration mechanism
which allows routes to be hosted at an arbitrary external mount point. For
example, by setting

```php
$config->path = 'api/';
```

in a configuration file, all requests will be routed relative to the external
path `api/`. So if your domain is `www.example.com/`, a request to
`http://www.example.com/api/test/path` will map to the `test/path` route.

All handlers accept a single `$data` argument, which is an instance of
`stdObject` that can be used to pass data from handler to handler. Through
this object, handlers can access the request and response objects via
`$data->request` and `$data->response`, as well as the configuration object
through `$data->config`. Handlers can assign properties to `$data` in order to
communicate with downstream handlers. Similarly, you can set any additional
properties you like in your configuration file in order to inform the behavior
of your handlers.

```php
$config->output_buffering = true;
$config->show_stack_traces = false;
$config->title = 'My Example Website';
$config->log_requests = true;
```

This package also includes an executable script, `jitsu-config-template`, which
can be used to inject configuration settings into file templates written in
PHP. This tool can be used to generate an `.htaccess`, `robots.txt`, etc. file
during a pre-processing step using properties defined in your site's
configuration files. The script simply enables you to execute PHP files with the
variable `$config` set to the configuration settings loaded from files listed
on the command line. You can write these template files without any boilerplate
code for including the configuration files or including your project's
`vendor/autoload.php` Composer script.

## Namespace

All classes are defined under the namespace `Jitsu\App`.

## Usage

For the following examples, let's assume we have the following configuration
file:

**config.php**

```php
$config->scheme = 'http';
$config->host = 'www.example.com';
$config->path = '/api/';
$config->document_root = '/var/www/example';
```

Or, equivalently,

```php
$config->base_url = 'http://www.example.com/api/';
$config->document_root = '/var/www/example';
```

This file is just a normal PHP script which sets properties on a pre-defined
`$config` variable.

Now, let's write an `.htaccess` file which will direct all incoming requests to
the file `index.php`, for those using Apache.

**.htaccess**

```htaccess
RewriteEngine On
RewriteRule ^ index.php [L]
```

Pretty simple. We could improve on this in a couple of ways, by telling Apache
to go ahead and treat CSS, JavaScript, etc. as static assets, and also by
preventing Apache from listing directory contents. Let's do that now.

```htaccess
Options -Indexes
IndexIgnore *
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f [OR]
RewriteCond %{REQUEST_FILENAME} !^/var/www/example/(.*\.(?:css|js)|favicon\.ico|robots\.txt|assets/.*)$
RewriteRule ^ index.php [L]
```

This directs requests to `index.php`, _except_ when Apache has matched the URL
to a file on the filesystem, _and_ that file ends in `.css` or `.js`, is named
`favicon.ico` or `robots.txt`, or is under the `assets/` directory.

Wouldn't it be nice if `/var/www/example` weren't hard-coded? We could write
our `.htaccess` as a template to do just that:

**htaccess.php**

```htaccess
Options -Indexes
IndexIgnore *
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f [OR]
RewriteCond %{REQUEST_FILENAME} !^<?= $config->document_root ?>/(.*\.(?:css|js)|favicon\.ico|robots\.txt|assets/.*)$
RewriteRule ^ index.php [L]
```

In fact, this will allow us to host multiple builds of the site simultaneously
at different document roots.

We could check this PHP file into version control and generate the real
`.htaccess` file with the command

```sh
./vendor/bin/jitsu-config-template -c config.php htaccess.php > .htaccess
```

Now, let's move on to defining the router. We define an application class which
inherits from `Jitsu\App\Application`. Then, we override the `initialize`
method to add routes and handlers.

**MyApplication.php**

```php
<?php
class MyApplication extends \Jitsu\App\Application {
  public function initialize() {
    $this->get('', function() {
      include __DIR__ . '/index.html.php';
    });
    $this->get('hello', function($data) {
      $data->response->setContentType('text/plain');
      echo "Hello World\n";
    });
    $this->get('users/:id', function($data) {
      list($user_id) = $data->parameters;
      // Just pretend this is defined somewhere
      $user = getUserFromDatabase($user_id);
      if($user) {
        include __DIR__ . '/show_user.html.php';
      } else {
        $data->response->setStatusCode(404);
	include __DIR__ . '/404.html.php';
      }
    });
    $this->notFound(function($data) {
      $data->response->setStatusCode(404);
      $uri = $data->request->uri();
      include __DIR__ . '/404.html.php';
    });
    $this->error(function($data) {
      $data->response->setStatusCode(500);
      $exception = $data->exception;
      include __DIR__ . '/500.html.php';
    });
  }
}
```

The routes defined with `get` respond to `GET` requests at their respective
URLs. The `notFound` handler is triggered as a fallback when the request fails
to match any of the routes above. Finally, the `error` handler is entered into
a separate callback queue which is executed whenever a handler throws an
exception.

Note that URLs are specified as Rails-style patterns, allowing certain path
segments to be captured. The supported pattern syntax is as follows:

* `:name` is a variable which captures everything except for a `/` and
  associates the captured text with a parameter called `name`. URL-encoded
  slashes can still get through.<sup>1</sup> Example: `users/:id` matches
  `users/42` and assigns `id` = `42`, but it does not match `users/a/b/c`.
* `*name` is a glob which captures everything, including slashes, and
  associates the captured text with a parameter called `name`. Example:
  `assets/*path` matches `assets/path/to/img.jpg` and assigns `path` =
  `path/to/img.jpg`.
* A pair of `()` encloses an optional section. Example: `users/(index.html)`
  matches `users/` and `users/index.html`.

Patterns are tested in order, so they should be listed in decreasing order of
specificity.

```php
$this->get('users/me', 'showCurrentUser');
$this->get('users/:id', 'showUser');
$this->get('*path', 'pageNotFound');
```

All parameters are collected in `$data->parameters`, an ordered array whose
keys are the parameter names. All values are automatically URL-decoded.

To invoke the router, instantiate your application and pass it a request,
response, and configuration object.

**index.php**

```php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/MyApplication.php';
(new MyApplication())->respond(
  new \Jitsu\Http\CurrentRequest(),
  new \Jitsu\Http\CurrentResponse(),
  new \Jitsu\App\SiteConfig(__DIR__ . '/config.php'));
```

If you are not stubbing out the HTTP request and response objects, you can use
the following shorthand:

```php
MyApplication::main(new \Jitsu\App\SiteConfig(__DIR__ . '/config.php'));
```

<sup>1</sup> The router itself allows forward slashes to be encoded in path
components, but some server configurations may disallow this. For example,
Apache might be configured, by default, to re-encode encoded forward slashes
in incoming URLs, or to refuse such requests with 404 Not Found. This is a
security measure to prevent those with ill intent from gaining access to paths
on the filesystem through unsanitized inputs. You can allow encoded slashes by
adding the following directive to the virtual host in your Apache configuration
file:

```apache
AllowEncodedSlashes NoDecode
```

## API

