<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\RabbitMq\Producer;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection\RabbitMqDatabaseTransactionProducerExtension;

class ProducerCompilerPass implements \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface
{

	public const RABBIT_MQ_EXTENSION_PRODUCER_TAG = 'old_sound_rabbit_mq.producer';

	public function process(ContainerBuilder $container): void
	{
		foreach ($container->findTaggedServiceIds(self::RABBIT_MQ_EXTENSION_PRODUCER_TAG) as $id => $attributes) {
			$originalDefinition = $container->getDefinition($id);
			$originalId = sprintf('%s.original', $id);
			$container->setDefinition($originalId, $originalDefinition);

			$definition = new Definition(DatabaseTransactionProducer::class, [
				new Reference($originalId),
				new Reference(RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION),
			]);
			$container->setDefinition($id, $definition);
		}
	}

}
