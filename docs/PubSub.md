Pub/Sub
=======

Overview
--------

Pub/Sub mechanism implements the message passing between applications using the ORM leveraging the native implementations of Pub/Sub within the database driver in use.

An example use case for Pub/Sub would be sending messages to background tasks that run on a separate process apart from the application itself, processing the workload without holding the application process.

### Publisher

Publisher is the process that sends messages to the subscriber, subscribed on a channel.

### Subscriber

Subscriber interrogate a channel defined and listen for incoming messages. Usually subscriber performs an action upon successful receiving of a message.

An example use case of a subscriber would be a long running daemon process performing tasks for the application layer (Publisher).

Creating a PubSub Manager
=========================

PubSub manager expects a database driver configuration to work. Manager is the entry point to using all functions supported in the Pub/Sub implementation within the ORM.

You can initialise an instance of the PubSubManager by using the following example.

``` sourceCode
$driver = new RedisDriver(['host' => '127.0.0.1', 'port' => 6379, 'database' => 0]);
$pubsub_manager = new PubSubManager($driver);
```

Examples
--------

Following examples use a simple string as the message, in practice it is possible to use JSON encoded strings or serialised data structures to be passed via the PubSub mechanism (if the underlying driver permits).

### Subscriber example

``` sourceCode
$channel_name = 'bg-task-1';

// Send the message to the subscriber
$pubsub_manager->addListener($channel_name, function(PubSubEvent $e) {
    list($cmd, $msg) = explode(' ', $e->getMessage());

    switch ($cmd) {
        case 'SEND-WELCOME-EMAIL':
            $email_address = $msg;

            // code to send the email to $email_address
        default:
            // ...
    }
});
```

### Publisher example

``` sourceCode
$channel_name = 'bg-task-1';
$message      = 'SEND-WELCOME-EMAIL user@acme.org';

// Send the message to the subscriber
$pubsub_manager->publish($channel_name, $message);
```