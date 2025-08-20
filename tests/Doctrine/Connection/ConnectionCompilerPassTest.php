<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection;

use PHPUnit\Framework\Assert;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection\RabbitMqDatabaseTransactionProducerExtension;

class ConnectionCompilerPassTest extends \PHPUnit\Framework\TestCase
{

	public function testSetLoggerForConnection(): void
	{
		$container = self::createContainer();
		$container->addCompilerPass(new ConnectionCompilerPass());

		$connectionDefinition = new Definition(Connection::class);
		$container->setDefinition(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION,
			$connectionDefinition
		);
		$container->setParameter(
			'rabbit_mq_database_transaction_producer_bundle.custom_connection_class',
			false
		);

		$container->compile();

		self::assertContainerHasService(
			$container,
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION
		);
		$setLoggerCall = $container->findDefinition(
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
		$container = self::createContainer();
		$container->addCompilerPass(new ConnectionCompilerPass());

		$connectionDefinition = new Definition(Connection::class);
		$container->setDefinition(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION,
			$connectionDefinition
		);
		$container->setParameter(
			'rabbit_mq_database_transaction_producer_bundle.custom_connection_class',
			true
		);

		$container->compile();

		self::assertContainerHasService(
			$container,
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION
		);

		self::assertContainerServiceIsOfType(
			$container,
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION,
			Connection::class
		);

		$methodCalls = $container->findDefinition(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION
		)->getMethodCalls();
		foreach ($methodCalls as $methodCall) {
			Assert::assertNotSame('setLogger', $methodCall[0]);
		}
	}

	private static function createContainer(): ContainerBuilder
	{
		$container = new ContainerBuilder(new ParameterBag([]));
		$container->getCompilerPassConfig()->setOptimizationPasses([]);
		$container->getCompilerPassConfig()->setRemovingPasses([]);
		$container->getCompilerPassConfig()->setAfterRemovingPasses([]);

		return $container;
	}

	private static function assertContainerHasService(
		ContainerBuilder $container,
		string $serviceId
	): void
	{
		Assert::assertTrue(
			$container->has($serviceId),
			sprintf('Expecting the container to have service `%s`.', $serviceId)
		);
	}

	private static function assertContainerServiceIsOfType(
		ContainerBuilder $container,
		string $serviceId,
		string $expectedClassString
	): void
	{
		$serviceDefinition = $container->findDefinition($serviceId);

		Assert::assertSame(
			$expectedClassString,
			$container->getParameterBag()->resolveValue($serviceDefinition->getClass()),
			sprintf('Expecting the service `%s` to be of type `%s`.', $serviceId, $expectedClassString)
		);
	}

}
