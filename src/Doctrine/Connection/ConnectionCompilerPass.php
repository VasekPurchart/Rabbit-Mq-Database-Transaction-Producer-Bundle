<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection\RabbitMqDatabaseTransactionProducerExtension;

class ConnectionCompilerPass implements \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface
{

	public function process(ContainerBuilder $container): void
	{
		if (
			!$container->hasParameter('rabbit_mq_database_transaction_producer_bundle.custom_connection_class')
			|| $container->getParameter('rabbit_mq_database_transaction_producer_bundle.custom_connection_class')
			|| !$container->has(RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION)
		) {
			return;
		}

		// expects that the connection wrapper class has setLogger method
		// such as VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\Connection
		$connection = $container->findDefinition(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION
		);
		$connection->addMethodCall('setLogger', [
			new Reference(RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_LOGGER),
		]);
	}

}
