<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\Connection;

class RabbitMqDatabaseTransactionProducerExtensionTest extends \PHPUnit\Framework\TestCase
{

	public function testDependsOnDoctrineBundle()
	{
		$containerBuilder = new ContainerBuilder();
		$extension = new RabbitMqDatabaseTransactionProducerExtension();
		$this->expectException(
			\VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection\DoctrineExtensionNotFoundException::class
		);
		$extension->prepend($containerBuilder);
	}

	public function testRegisterCustomConnectionClass()
	{
		$doctrineExtension = new DoctrineExtension();
		$extension = new RabbitMqDatabaseTransactionProducerExtension();

		$containerBuilder = new ContainerBuilder();
		$containerBuilder->registerExtension($doctrineExtension);
		$containerBuilder->registerExtension($extension);

		$extension->prepend($containerBuilder);

		$doctrineConfig = $containerBuilder->getExtensionConfig($doctrineExtension->getAlias());
		if (!isset($doctrineConfig[0]) || !isset($doctrineConfig[0]['dbal']) || !isset($doctrineConfig[0]['dbal']['wrapper_class'])) {
			$this->fail();
		}

		$this->assertSame(Connection::class, $containerBuilder->getExtensionConfig($doctrineExtension->getAlias())[0]['dbal']['wrapper_class']);
	}

}
