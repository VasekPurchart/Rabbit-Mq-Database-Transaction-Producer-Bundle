<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection\RabbitMqDatabaseTransactionProducerExtension;

class ConnectionCompilerPass implements \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface
{

	public function process(ContainerBuilder $container)
	{
		if (
			!$container->hasParameter(RabbitMqDatabaseTransactionProducerExtension::CONTAINER_PARAMETER_CUSTOM_CONNECTION_CLASS)
			|| $container->getParameter(RabbitMqDatabaseTransactionProducerExtension::CONTAINER_PARAMETER_CUSTOM_CONNECTION_CLASS)
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
