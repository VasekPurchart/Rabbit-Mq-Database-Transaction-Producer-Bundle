<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\RabbitMq\Producer;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection\RabbitMqDatabaseTransactionProducerExtension;

class ProducerCompilerPassTest extends \Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase
{

	protected function registerCompilerPass(ContainerBuilder $container)
	{
		$container->addCompilerPass(new ProducerCompilerPass());
	}

	public function testWrapProducersWithDatabaseTransactionProducer()
	{
		$producerDefinition = new Definition(Producer::class);
		$producerDefinition->addTag(ProducerCompilerPass::RABBIT_MQ_EXTENSION_PRODUCER_TAG);
		$this->setDefinition('old_sound_rabbit_mq.availability_generate_premise_producer', $producerDefinition);

		$this->compile();

		$this->assertContainerBuilderHasService(
			'old_sound_rabbit_mq.availability_generate_premise_producer.original',
			Producer::class
		);
		$this->assertContainerBuilderHasServiceDefinitionWithTag(
			'old_sound_rabbit_mq.availability_generate_premise_producer.original',
			ProducerCompilerPass::RABBIT_MQ_EXTENSION_PRODUCER_TAG
		);

		$this->assertContainerBuilderHasService(
			'old_sound_rabbit_mq.availability_generate_premise_producer',
			DatabaseTransactionProducer::class
		);

		$this->assertContainerBuilderHasServiceDefinitionWithArgument(
			'old_sound_rabbit_mq.availability_generate_premise_producer',
			0
		);
		$originalProducerArgument = $this->container->findDefinition(
			'old_sound_rabbit_mq.availability_generate_premise_producer'
		)->getArgument(0);
		$this->assertInstanceOf(Reference::class, $originalProducerArgument);
		$this->assertSame(
			'old_sound_rabbit_mq.availability_generate_premise_producer.original',
			$originalProducerArgument->__toString()
		);

		$this->assertContainerBuilderHasServiceDefinitionWithArgument(
			'old_sound_rabbit_mq.availability_generate_premise_producer',
			1
		);
		$connectionArgument = $this->container->findDefinition(
			'old_sound_rabbit_mq.availability_generate_premise_producer'
		)->getArgument(1);
		$this->assertInstanceOf(Reference::class, $connectionArgument);
		$this->assertSame(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_SERVICE_DATABASE_CONNECTION,
			$connectionArgument->__toString()
		);
	}

}
