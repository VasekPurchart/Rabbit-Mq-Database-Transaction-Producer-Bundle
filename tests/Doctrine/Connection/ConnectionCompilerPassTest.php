<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection\RabbitMqDatabaseTransactionProducerExtension;

class ConnectionCompilerPassTest extends \Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase
{

	protected function registerCompilerPass(ContainerBuilder $container)
	{
		$container->addCompilerPass(new ConnectionCompilerPass());
	}

	public function testSetLoggerForConnection()
	{
		$connectionDefinition = new Definition(Connection::class);
		$this->setDefinition(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION,
			$connectionDefinition
		);

		$this->compile();

		$this->assertContainerBuilderHasService(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION,
			Connection::class
		);
		$setLoggerCall = $this->container->findDefinition(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION
		)->getMethodCalls()[0];
		$this->assertSame('setLogger', $setLoggerCall[0]);
		$this->assertInstanceOf(Reference::class, $setLoggerCall[1][0]);
		$this->assertSame(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_LOGGER,
			$setLoggerCall[1][0]->__toString()
		);
	}

}
