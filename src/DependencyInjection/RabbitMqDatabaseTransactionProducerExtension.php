<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\Connection;

class RabbitMqDatabaseTransactionProducerExtension
	extends \Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension
	implements \Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface
{

	public const ALIAS = 'rabbit_mq_database_transaction_producer';

	public const CONTAINER_SERVICE_DATABASE_CONNECTION = 'rabbit_mq_database_transaction_producer_bundle.database_connection';
	public const CONTAINER_SERVICE_LOGGER = 'rabbit_mq_database_transaction_producer_bundle.logger';

	public const DOCTRINE_EXTENSION_ALIAS = 'doctrine';

	public function prepend(ContainerBuilder $container): void
	{
		$config = $this->getMergedConfig($container);
		if ($config[Configuration::PARAMETER_CUSTOM_CONNECTION_CLASS]) {
			return;
		}

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
	 * @param mixed[] $mergedConfig
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 */
	protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
	{
		$container->setParameter(
			'rabbit_mq_database_transaction_producer_bundle.custom_connection_class',
			$mergedConfig[Configuration::PARAMETER_CUSTOM_CONNECTION_CLASS]
		);

		$loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
		$loader->load('services.yaml');
	}

	/**
	 * @param mixed[] $config
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 * @return \VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection\Configuration
	 */
	public function getConfiguration(array $config, ContainerBuilder $container): Configuration
	{
		return new Configuration(
			$this->getAlias()
		);
	}

	public function getAlias(): string
	{
		return self::ALIAS;
	}

	/**
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 * @return mixed[]
	 */
	private function getMergedConfig(ContainerBuilder $container): array
	{
		$configs = $container->getExtensionConfig($this->getAlias());
		return $this->processConfiguration($this->getConfiguration([], $container), $configs);
	}

}
