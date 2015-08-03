## Illuminate Mail Outside Laravel

### Installation

`composer require illuminate/config`

`composer require illuminate/mail`

`composer require hazzard/mail`

`composer require guzzlehttp/guzzle` _(required for Mailgun and Mandrill)_

### Usage 

````
use Hazzard\Mail\Mail;
use Illuminate\Container\Container;
use Illuminate\Config\Repository as Config;

// Create a container and config repository.
$app = new Container;
$app['config'] = new Config;

// Set the mail & services configuration.
$app['config']['mail'] = require 'config/mail.php';
$app['config']['services'] = require 'config/services.php';

$mail = new Mail($container);

// Set the storage path used by the views.
$mail->setViewStoragePath(_DIR__.'/path/to/views');

// Make the instance available globally via static methods (optional).
$mail->setAsGlobal();

// Create a class alias (optional). 
$mail->classAlias();
````

##### Using The Mailer

````
Mail::send('emails.welcome', ['key' => 'value'], function ($message) {
    $message->to('foo@example.com', 'John Smith')->subject('Welcome!');
});
````

The rest is the same as [Laravel](http://laravel.com/docs/5.0/mail#basic-usage).

##### Providing A Custom View Factory

To provide a custom view factory register a `view` binding to the container.

The view factory must implement `Illuminate\Contracts\View\Factory` and the view `Illuminate\Contracts\View\View` (or at least to have a `render` method).

````
$container['view'] = new CustomViewFactory();
````
