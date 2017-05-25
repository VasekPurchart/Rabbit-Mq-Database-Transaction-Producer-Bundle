<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\Connection;

class RabbitMqDatabaseTransactionProducerExtension
	extends \Symfony\Component\HttpKernel\DependencyInjection\Extension
	implements \Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface
{

	const ALIAS = 'rabbit_mq_database_transaction_producer';

	const CONTAINER_SERVICE_DATABASE_CONNECTION = 'database_connection';
	const CONTAINER_SERVICE_LOGGER = 'logger';

	const DOCTRINE_EXTENSION_ALIAS = 'doctrine';

	public function prepend(ContainerBuilder $container)
	{
		if (!$container->hasExtension(self::DOCTRINE_EXTENSION_ALIAS)) {
			throw new \VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection\DoctrineExtensionNotFoundException();
		}
		$container->loadFromExtension(self::DOCTRINE_EXTENSION_ALIAS, [
			'dbal' => [
				'wrapper_class' => Connection::class,
			],
		]);
	}

	/**
	 * @param mixed[][] $configs
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 */
	public function load(array $configs, ContainerBuilder $container)
	{
		// ...
	}

	public function getAlias(): string
	{
		return self::ALIAS;
	}

}
