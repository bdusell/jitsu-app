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

### class Jitsu\\App\\Application

Extends `Router`.

An extensible top-level HTTP request router.

#### new Application()

Automatically adds a configuration handler.

### class Jitsu\\App\\SubRouter

Extends `Router`.

An extensible sub-router which can be mounted in another router.

#### new SubRouter()

### class Jitsu\\App\\Router

Extends `BasicRouter`.

An extensible HTTP request router.

Like `BasicRouter` but with fancy shortcut methods.

#### abstract protected function initialize()

Override this method to configure routes and handlers on this
router.

#### $router->respond($request, $response, $config)

Dispatch a request to this router.

|   | Type | Description |
|---|------|-------------|
| **`$request`** | `\Jitsu\Http\RequestBase` | The HTTP request object. This will be made available to handlers via `$data->request`. |
| **`$response`** | `\Jitsu\Http\ResponseBase` | The HTTP response object with which the application code will interact. This will be made available to handlers via `$data->response`. |
| **`$config`** | `\Jitsu\App\SiteConfig` | Configuration settings for the router. This will be made available to handlers via `$data->config`. |

#### Router::main($config)

Shorthand for `respond` which invokes the router with the current
HTTP request and response.

|   | Type | Description |
|---|------|-------------|
| **`$config`** | `\Jitsu\App\SiteConfig` | Configuration settings for the router. |

#### $router->callback($callback)

Add a callback to the request handler queue.

The callback will receive a single `stdObject` argument (`$data`)
which has been passed through earlier handlers. The handler may read
properties from this object to access data from earlier handlers and
assign properties to pass data to later handlers. The router
initially adds the properties `request`, `response`, and `config`.

The handler should return `true` if routing should cease with this
handler (indicating a match) or `false` if the router should
continue to attempt to match later routes. A handler can perform
some action or add to `$data` without returning `true`, so that it
merely serves to communicate with later handlers.

Callbacks are executed in the same order they are added.

|   | Type | Description |
|---|------|-------------|
| **`$callback`** | `callable` | A callback which accepts a single `stdObject` argument and returns a `bool` to indicate whether it has handled the request and the router should stop dispatching. |

#### $router->setNamespace($value)

Set the namespace which will be automatically prefixed to the names
of callbacks passed to all handler functions.

Function names are accepted as callbacks in all of the callback
registration methods here. If all of your callbacks are under one
namespace, this can be used to avoid repeating the namespace in all
function names.

|   | Type |
|---|------|
| **`$value`** | `string` |

#### $router->route($route, $callback)

Handle all requests to a certain path.

The path is specified as a pattern which may contain named
parameters. The following syntax is supported:

* `:name`, which captures a portion of a path segment called `name`.
  This will not match slash (`/`) characters.
* `*name`, which captures a portion of text called `name` which can
  span multiple path segments. This will match any character.
* `(optional)`, where the portion enclosed by `()` characters may
  optionally be present. Any pattern syntax may appear inside the
  optional portion.

Any named parameters will automatically be URL-decoded and stored in
`$data->parameters`, an array mapping parameter names to captured
values. The key-value pairs will occur in the same order as the
parameters were specified.

|   | Type | Description |
|---|------|-------------|
| **`$route`** | `string` | A path pattern. |
| **`$callback`** | `callable` |  |

#### $router->endpoint($method, $route, $callback)

Handle all requests to a certain combination of method and path.

|   | Type | Description |
|---|------|-------------|
| **`$method`** | `string` | The method (`GET`, `POST`, etc.). |
| **`$route`** | `string` | A path pattern. |
| **`$callback`** | `callable` |  |

#### $router->get($route, $callback)

Handle a GET request to a certain path.

|   | Type | Description |
|---|------|-------------|
| **`$route`** | `string` | A path pattern. |
| **`$callback`** | `callable` |  |

#### $router->post($route, $callback)

Handle a POST request to a certain path.

|   | Type | Description |
|---|------|-------------|
| **`$route`** | `string` | A path pattern. |
| **`$callback`** | `callable` |  |

#### $router->put($route, $callback)

Handle a PUT request to a certain path.

|   | Type | Description |
|---|------|-------------|
| **`$route`** | `string` | A path pattern. |
| **`$callback`** | `callable` |  |

#### $router->delete($route, $callback)

Handle a DELETE request to a certain path.

|   | Type | Description |
|---|------|-------------|
| **`$route`** | `string` | A path pattern. |
| **`$callback`** | `callable` |  |

#### $router->mount($route, $router)

Mount a sub-router at a certain path.

Stops routing if and only if the sub-router matches. Routing
continues if the sub-router does not match, even if the mount point
matched.

|   | Type | Description |
|---|------|-------------|
| **`$route`** | `string` | A path pattern indicating where the sub-router will be mounted. |
| **`$router`** | `\Jitsu\App\Router` |  |

#### $router->badMethod($callback)

Handles any request whose URL was matched in an earlier handler but
was not handled because the method did not match.

The property `$data->matched_methods` will contain the list of
allowed methods for this URL.

|   | Type |
|---|------|
| **`$callback`** | `callable` |

#### $router->notFound($callback)

Handles any request which was not matched in an earlier handler.

|   | Type |
|---|------|
| **`$callback`** | `callable` |

#### $router->error($callback)

Handles any exceptions thrown by request handlers.

The property `$data->exception` will be set to the exception thrown.

|   | Type | Description |
|---|------|-------------|
| **`$callback`** | `callable` | A callback which accepts a single `stdObject` argument. |

### class Jitsu\\App\\BasicRouter

Basic router class.

The router consists of two queues: the normal request handler queue, and
the error handler queue. The router calls request handlers in the same
order they were registered until one of them returns `true`. If a handler
throws an exception, control passes irreversibly to the error handler queue,
which behaves in the same way but has no rescue strategy for exceptions.

#### new BasicRouter()

#### $basic\_router->handler($handler)

Add a handler to the request handler queue.

|   | Type |
|---|------|
| **`$handler`** | `\Jitsu\App\Handler` |

#### $basic\_router->errorHandler($handler)

Add a handler to the error handler queue.

The `$data` argument to the handler will have the property
`exception` set to the exception that was thrown.

|   | Type |
|---|------|
| **`$handler`** | `\Jitsu\App\Handler` |

#### $basic\_router->run($data)

Invoke the router with some datum to be passed from handler to
handler.

|   | Type | Description |
|---|------|-------------|
| **`$data`** | `object` | Some datum to be passed from handler to handler. |
| returns | `bool` | Whether the invocation was handled, meaning that routing ended when some request handler or error handler returned `true`. |
| throws | `\Exception` | Any exception thrown by a request handler that was not handled in the error handler queue, or any exception thrown from an error handler. |

### interface Jitsu\\App\\Handler

A route handler interface.

#### $handler->handle($data)

React to a request.

|   | Type | Description |
|---|------|-------------|
| **`$data`** | `object` | Some datum which is passed from handler to handler. |
| returns | `bool` | Whether this handler has matched the route and the router should stop routing. |

### class Jitsu\\App\\SiteConfig

Extends `Config`.

A subclass of `Config` specialized for websites.

Adds the following properties:

* `base_url`: The external URL at which the router is mounted. Tied to the
  properties `scheme`, `host`, and `path`.
* `scheme`: The scheme or protocol used by the site (`http` or `https`).
* `host`: The host name of the site (such as `example.com`).
* `path`: The path section of the `base_url`.
* `base_path`: Like `path`, but formatted so that it always begins and ends
   with a slash.
* `locale`: The locale of the currently running PHP script. When setting,
  use an array of values to indicate a series of fallbacks.

#### $site\_config->set\_base\_url($url)

Set the base URL of the site.

If the `scheme`, `host`, or `path` can be parsed from the new value,
they are set accordingly.

|   | Type |
|---|------|
| **`$url`** | `string` |

#### $site\_config->get\_base\_url()

Get the base URL.

Consists of `<scheme>://<host>/<path>`.

|   | Type |
|---|------|
| returns | `string` |

#### $site\_config->get\_base\_path()

Get the `path`, formatted so that it always begins and ends with a
slash.

|   | Type |
|---|------|
| returns | `string` |

#### $site\_config->makePath($rel\_path)

Given a relative path, append it to the `path` to form an absolute
path.

This has the exception that if both are the empty string, it returns
the empty string.

|   | Type |
|---|------|
| **`$rel_path`** | `string` |
| returns | `string` |

#### $site\_config->removePath($abs\_path)

Strip off the `path` from an absolute path, or return `null` if the
path does not match.

|   | Type |
|---|------|
| **`$abs_path`** | `string` |
| returns | `string|null` |

#### $site\_config->makeUrl($rel\_path)

Given a relative path, append it to the `base_url` to form an
absolute URL.

A small, special case: if the configured `path` is set to the empty
string, then an empty `$rel_path` will produce a URL consisting of
the domain with _no_ trailing slash (e.g. `http://www.example.com`).
This is unlike setting `path` to `/`, which will _always_ produce a
URL with a trailing slash, even when `$rel_path` is empty (e.g.
`http://www.example.com/`). The former is techinically malformed,
although it may be desired, and browsers will generally still accept
it as a hyperlink.

|   | Type |
|---|------|
| **`$rel_path`** | `string` |
| returns | `string` |

#### $site\_config->set\_locale($value)

Set the locale.

If the value is an array of strings, each string serves as a
fallback for the preceding ones in case they are not available.

|   | Type |
|---|------|
| **`$value`** | `string|string[]` |

#### $site\_config->get\_locale()

Get the locale.

|   | Type |
|---|------|
| returns | `string` |

### class Jitsu\\App\\Config

An object whose properties store configuration settings.

Usage is very simple. It behaves just like an `stdObject`, where properties
can be set and accessed at will.

    $config = new Config(['a' => 1]);
    $config->b = 2;
    echo $config->a, ' ', $config->b, "\n";

Sub-classes may add dynamic getter and setter functions for specific
properties by defining methods prefixed with `get_` and `set_`, followed by
the name of the simulated property.

Configuration settings may be read from any PHP file which assigns properties
to a pre-defined variable called `$config`. For example:

**config.php**

    $config->a = 1;
    $config->b = 2;

To read the file:

    $config = new Config('config.php');

#### new Config($args,...)

Initialize with the name of a PHP file or an `array` of properties.

|   | Type |
|---|------|
| **`$args,...`** | `string|array` |

#### $config->read($filename)

Read settings from a PHP file.

This simply evaluates a PHP file with this object assigned to
`$config`.

|   | Type |
|---|------|
| **`$filename`** | `string` |
| returns | `$this` |

#### $config->set($name, $value = null)

Set/add a property.

|   | Type | Description |
|---|------|-------------|
| **`$name`** | `string|array` | The name of the property to set. Alternatively, pass a single `array` to set multiple properties. |
| **`$value`** | `mixed` |  |
| returns | `$this` |  |

#### $config->merge($arg)

Set/add multiple properties.

|   | Type | Description |
|---|------|-------------|
| **`$arg`** | `string|array` | The name of a file to read or an array of properties to set. |

#### $config->\_\_set($name, $value)

#### $config->get($name, $default = null)

Get a property.

|   | Type | Description |
|---|------|-------------|
| **`$name`** | `string` | The name of the property. |
| **`$default`** | `mixed` | Default value to get if the property does not exist. |

#### $config->\_\_get($name)

#### $config->has($name)

Tell whether a certain property exists.

|   | Type |
|---|------|
| **`$name`** | `string` |
| returns | `bool` |

#### $config->\_\_isset($name)

