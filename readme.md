RabbitMQ Database Transaction Producer Bundle
=============================================

**Publish messages to RabbitMQ producer when database transaction was committed**

> **Note:** This bundle expects you are using [Doctrine DBAL & ORM Bundle](https://github.com/doctrine/DoctrineBundle) and [RabbitMqBundle](https://github.com/php-amqplib/RabbitMqBundle/)

### The problem

[Transactions](https://en.wikipedia.org/wiki/Database_transaction) in databases ensure that in a series of operations either all of them are "completed" or none of them is. This is very important for most applications, because otherwise their state becomes broken. That is why most database systems provide transactions (at least at some level). New problem arises, when you are using multiple systems, because there is usually no way to ensure transactional behavior for operations spanning all of them.

This bundle provides solution to mitigate the most common situations which originate from this problem when using RabbitMQ with an SQL database (through Doctrine). Both SQL databases and RabbitMQ have their own transactions, but there is no way to extend the transactions between the systems, which can lead to many erroneous situations, typically:

1) You publish an ID to the RabbitMQ queue, which should be processed asynchronously, but it was never committed to the database.
2) You publish an ID to the RabbitMQ queue, which should be processed asynchronously, but it was *not yet* committed to the database.
3) Everything is committed to the database, but the accompanying message was never sent to the queue.

This is even more common if you are using nested transactions, because then it is especially difficult to tell just by looking at "local" code, when the transaction will actually be committed.

That is precisely the case when using Doctrine ORM, because even when you call `flush`, you cannot be sure, that there is no open transaction wrapping this call.

### What this bundle does

This bundle does not claim to "solve" the problem, because it's almost impossible, but it tries to mitigate most of the practical situations caused by the problem. When publishing messages to RabbitMQ, this bundle will check, if there is an open transaction (including nested) on the database connection and if not, it will send the message right away. But when it detects that there is an open transaction, then it will store the message and it will be sent only after and if all the transactions on the connection were committed.

When writing your code like in the example below, all the situations mentioned in the last section should not make problems:

```php
<?php

use Doctrine\ORM\EntityManager;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface as Producer;

class ImportFacade
{

	/** @var \OldSound\RabbitMqBundle\RabbitMq\ProducerInterface */
	private $importProcessItemRabbitMqProducer;

	/** @var \Doctrine\ORM\EntityManager */
	private $entityManager;

	public function __construct(
		Producer $importProcessItemRabbitMqProducer,
		EntityManager $entityManager
	)
	{
		$this->importProcessItemRabbitMqProducer = $importProcessItemRabbitMqProducer;
		$this->entityManager = $entityManager;
	}

	public function import()
	{
		// $items = ...

		$this->entityManager->transactional(function () use ($items) {
			$this->entityManager->flush();

			foreach ($items as $item) {
				$this->importProcessItemRabbitMqProducer->publish($item->getId());
			}
		});
	}

}
```

The example represents an import which is split into items, which can then be processed asynchronously one by one. All persistence related operations are wrapped in a transaction using `EntityManager::transactional()`. First, the `EntityManager` is flushed, which means, that if any errors should arise when storing the data in the database, an exception will be thrown and the messages will never get published to RabbitMQ. If the data flushed by Doctrine was ok, then the messages will be published either immediately or after all nested transactions are committed.

This ensures that the data, on which the RabbitMQ messages are based will always be in the database before the messages are published, therefore solving the first two situations from the previous section.

The third situation - that the data is saved to the database, but the RabbitMQ message is never published - can unfortunately still occur - this will happen if there is a problem in the application after the commit and between the messages are published. But since the publishing logic is very simple and there is no business logic involved, this should almost never happen, the most common case is probably that the RabbitMQ instance is not reachable.

Configuration
-------------

Configuration structure with listed default values:

```yaml
# config/packages/rabbit_mq_database_transaction_producer.yaml
rabbit_mq_database_transaction_producer:
    # Whether custom connection class for DBAL is used in the project, see below for details.
    # When this is false, custom connection class from this bundle is used.
    custom_connection_class: false
```

### Custom connection class

Doctrine DBAL does not provide any way to add features to `Doctrine\DBAL\Connection` using composition, so that added functionality can be combined from multiple sources. The only way to extend the functionality is trough extending the original class and configuring Doctrine to use that class instead using the [`dbal.wrapper_class`](https://symfony.com/doc/current/reference/configuration/doctrine.html) configuration option.

If you are already using custom connection implementation, you have to make sure it implements  `VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\AfterCommitCallbacksConnection` in order to be compatible with this bundle, namely implement `addAfterCommitCallback` method and make sure the callbacks are triggered after the transaction is committed.

If you are not using any custom implementation, this bundle will provide implementation, which adds the callbacks behavior and on top of that provide logging for exceptions originating from called callbacks.

### Services overloading

You can also override services used internally, for example if you use a non standard logger, you can provide custom instance with an [alias](http://symfony.com/doc/current/components/dependency_injection/advanced.html#aliasing):

```yaml
services:
    my_logger:
        class: Monolog\Logger
        arguments:
            - 'my_channel'

    rabbit_mq_database_transaction_producer_bundle.logger: @my_logger
```

Installation
------------

Install package [`vasek-purchart/rabbit-mq-database-transaction-producer-bundle`](https://packagist.org/packages/vasek-purchart/rabbit-mq-database-transaction-producer-bundle) with [Composer](https://getcomposer.org/):

```bash
composer require vasek-purchart/rabbit-mq-database-transaction-producer-bundle
```

Register the bundle in your application:
```php
// config/bundles.php
return [
	// ...
	VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\RabbitMqDatabaseTransactionProducerBundle::class => ['all' => true],
];
```
