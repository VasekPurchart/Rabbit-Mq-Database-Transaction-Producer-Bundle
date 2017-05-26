<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\Connection;

class RabbitMqDatabaseTransactionProducerExtension
	extends \Symfony\Component\HttpKernel\DependencyInjection\Extension
	implements \Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface
{

	const ALIAS = 'rabbit_mq_database_transaction_producer';

	const CONTAINER_SERVICE_DATABASE_CONNECTION = 'rabbit_mq_database_transaction_producer_bundle.database_connection';
	const CONTAINER_SERVICE_LOGGER = 'rabbit_mq_database_transaction_producer_bundle.logger';

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
		$loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
		$loader->load('services.yml');
	}

	public function getAlias(): string
	{
		return self::ALIAS;
	}

}
