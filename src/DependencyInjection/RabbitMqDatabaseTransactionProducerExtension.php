<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class RabbitMqDatabaseTransactionProducerExtension extends \Symfony\Component\HttpKernel\DependencyInjection\Extension
{

	const ALIAS = 'rabbit_mq_database_transaction_producer';

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
