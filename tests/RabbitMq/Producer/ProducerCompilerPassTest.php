<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\RabbitMq\Producer;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PHPUnit\Framework\Assert;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection\RabbitMqDatabaseTransactionProducerExtension;

class ProducerCompilerPassTest extends \PHPUnit\Framework\TestCase
{

	public function testWrapProducersWithDatabaseTransactionProducer(): void
	{
		$container = self::createContainer();
		$container->addCompilerPass(new ProducerCompilerPass());

		$producerDefinition = new Definition(Producer::class);
		$producerDefinition->addTag(ProducerCompilerPass::RABBIT_MQ_EXTENSION_PRODUCER_TAG);

		$serviceId = 'old_sound_rabbit_mq.availability_generate_premise_producer';
		$container->setDefinition($serviceId, $producerDefinition);

		$container->compile();

		$originalServiceId = 'old_sound_rabbit_mq.availability_generate_premise_producer.original';
		self::assertContainerHasService($container, $originalServiceId);
		self::assertContainerServiceIsOfType($container, $originalServiceId, Producer::class);

		self::assertContainerServiceHasTagWithAttributes(
			$container,
			$originalServiceId,
			ProducerCompilerPass::RABBIT_MQ_EXTENSION_PRODUCER_TAG
		);

		self::assertContainerHasService($container, $serviceId);
		self::assertContainerServiceIsOfType($container, $serviceId, DatabaseTransactionProducer::class);

		self::assertContainerServiceHasArgument($container, $serviceId, 0);
		$originalProducerArgument = $container->findDefinition(
			$serviceId
		)->getArgument(0);
		Assert::assertInstanceOf(Reference::class, $originalProducerArgument);
		Assert::assertSame(
			$originalServiceId,
			$originalProducerArgument->__toString()
		);

		self::assertContainerServiceHasArgument($container, $serviceId, 1);
		$connectionArgument = $container->findDefinition(
			$serviceId
		)->getArgument(1);
		Assert::assertInstanceOf(Reference::class, $connectionArgument);
		Assert::assertSame(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION,
			$connectionArgument->__toString()
		);
	}

	private static function createContainer(): ContainerBuilder
	{
		$container = new ContainerBuilder(new ParameterBag([]));
		$container->getCompilerPassConfig()->setOptimizationPasses([]);
		$container->getCompilerPassConfig()->setRemovingPasses([]);
		$container->getCompilerPassConfig()->setAfterRemovingPasses([]);

		return $container;
	}

	private static function assertContainerHasService(ContainerBuilder $container, string $serviceId): void
	{
		Assert::assertTrue(
			$container->has($serviceId),
			sprintf('Container is missing required service `%s`.', $serviceId)
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
			$container->getParameterBag()->resolveValue($serviceDefinition->getClass())
		);
	}

	private static function assertContainerServiceHasArgument(
		ContainerBuilder $container,
		string $serviceId,
		int $argumentIndex
	): void
	{
		$serviceDefinition = $container->findDefinition($serviceId);

		try {
			$serviceDefinition->getArgument($argumentIndex);
		} catch (\Symfony\Component\DependencyInjection\Exception\OutOfBoundsException $exception) {
			Assert::fail(sprintf('Service `%s` does not have any argument `%d`.', $serviceId, $argumentIndex));
		}
	}

	private static function assertContainerServiceHasTagWithAttributes(
		ContainerBuilder $container,
		string $serviceId,
		string $tagName
	): void
	{
		$tagExists = false;
		$serviceDefinition = $container->findDefinition($serviceId);

		foreach ($serviceDefinition->getTags() as $name => $tagsAttributes) {
			if ($name !== $tagName) {
				continue;
			}

			$tagExists = true;
		}

		if (!$tagExists) {
			Assert::fail(sprintf('Service `%s` does not have any tag `%s`.', $serviceId, $tagName));
		}
	}

}
