Simple PHP Messaging Service
============================

[![Build Status](https://travis-ci.org/ArthurHoaro/simple-php-messaging-service.svg?branch=master)](https://travis-ci.org/ArthurHoaro/simple-php-messaging-service)
[![Coverage Status](https://coveralls.io/repos/github/ArthurHoaro/simple-php-messaging-service/badge.svg?branch=master)](https://coveralls.io/github/ArthurHoaro/simple-php-messaging-service?branch=master)

SimplePMS is a very simple messaging service component with a database backend.

It allows applications to communicate without a heavy traditional client-server architecture.
It is a way to manage asynchronous, potentially long-running PHP tasks such as
API requests, database export/import operations, email sending, payment notification handlers, feed generation etc.

This project is based on [php-queue-manager](https://github.com/fordnox/php-queue-manager), except I wanted my messages
to be only messages. Therefore, messages are not tasks in SimplePMS and are not expected to carry business code.

SimplePMS can be integrated to any PHP based application with minimum effort, because it does not depend on any framework.
PDO extension is the only requirement.

If you need a more feature-full messaging service - e.g. multiple workers consuming the same queue - consider 
using a robust messaging service/queue manager such as JMS, ActiveMQ, RabbitMQ, IronMQ, etc. ; even though it requires a more complex setup.

Requirements
============

* PHP >5.5
* PDO
* Database backend (sqlite, PostgreSQL, MySQL)

Features
========

  * Asynchronous messaging service through database.
  * Send any type of data.
  * Multiple queues.
  * A timeout system to handle stuck messages.
  * All actions are log in the DB.

Install with Composer
=====================

* Install dependency

```json
{
    "require": {
        "arthurhoaro/simple-php-messaging-service": "dev-master"
    }
}
```

* Create your database structure using one of the schema available in `extra/`.

Usage example
=============

```php
// Your software send a message to a worker
$message = new Anything('param');
$pms = new SimplePMS();
$pms->setPdo($pdoInstance);
$pms->send($message, 'my_queue');

// The worker reads the queue
$message = $pms->reveive('my_queue');
try {
    $message->doAnything();
    $pms->deleteMessage($message);
} catch (\Exception $e) {
    $pms->log('Can not do anything. '. $e->getMessage());
}
```
