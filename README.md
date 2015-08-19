Jitsu Application Routing and Configuration
-------------------------------------------

This package supplies a basic architecture for web applications. It defines
extensible request routing and application configuration mechanisms.

Define an application class which inherits from `Jitsu\App\Application`. Then,
override the `initialize` method to define request handlers and routes for your
application.

A quick example:

```php
<?php
class MyApplication extends \Jitsu\App\Application {
  public function initialize() {
    $this->get('', function() {
      include __DIR__ . '/index.html.php';
    });
    $this->notFound(function($data) {
      $uri = $data->request->uri();
      include __DIR__ . '/404.html.php';
    });
    $this->error(function($data) {
      $exception = $data->exception;
      include __DIR__ . '/500.html.php';
    });
  }
}
```
