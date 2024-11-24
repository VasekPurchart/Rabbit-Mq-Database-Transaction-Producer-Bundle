<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection;

use PHPUnit\Framework\Assert;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection\RabbitMqDatabaseTransactionProducerExtension;

class ConnectionCompilerPassTest extends \Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase
{

	protected function registerCompilerPass(ContainerBuilder $container): void
	{
		$container->addCompilerPass(new ConnectionCompilerPass());
	}

	public function testSetLoggerForConnection(): void
	{
		$connectionDefinition = new Definition(Connection::class);
		$this->setDefinition(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION,
			$connectionDefinition
		);
		$this->setParameter(
			'rabbit_mq_database_transaction_producer_bundle.custom_connection_class',
			false
		);

		$this->compile();

		$this->assertContainerBuilderHasService(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION,
			Connection::class
		);
		$setLoggerCall = $this->container->findDefinition(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION
		)->getMethodCalls()[0];
		Assert::assertSame('setLogger', $setLoggerCall[0]);
		Assert::assertInstanceOf(Reference::class, $setLoggerCall[1][0]);
		Assert::assertSame(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_LOGGER,
			$setLoggerCall[1][0]->__toString()
		);
	}

	public function testDoNothingWhenCustomConnectionClassIsSpecified(): void
	{
		$connectionDefinition = new Definition(Connection::class);
		$this->setDefinition(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION,
			$connectionDefinition
		);
		$this->setParameter(
			'rabbit_mq_database_transaction_producer_bundle.custom_connection_class',
			true
		);

		$this->compile();

		$this->assertContainerBuilderHasService(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION,
			Connection::class
		);
		$methodCalls = $this->container->findDefinition(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION
		)->getMethodCalls();
		foreach ($methodCalls as $methodCall) {
			Assert::assertNotSame('setLogger', $methodCall[0]);
		}
	}

}
