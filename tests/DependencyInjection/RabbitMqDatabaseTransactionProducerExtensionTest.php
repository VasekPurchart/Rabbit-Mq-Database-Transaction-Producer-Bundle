<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use PHPUnit\Framework\Assert;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\Connection;

class RabbitMqDatabaseTransactionProducerExtensionTest extends \Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase
{

	public function setUp(): void
	{
		parent::setUp();
		$this->setParameter('kernel.debug', true);
	}

	/**
	 * @return \Symfony\Component\DependencyInjection\Extension\ExtensionInterface[]
	 */
	protected function getContainerExtensions(): array
	{
		return [
			new DoctrineExtension(),
			new RabbitMqDatabaseTransactionProducerExtension(),
		];
	}

	public function testDependsOnDoctrineBundle(): void
	{
		$containerBuilder = new ContainerBuilder();
		$extension = new RabbitMqDatabaseTransactionProducerExtension();
		$this->expectException(
			\VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection\DoctrineExtensionNotFoundException::class
		);
		$extension->prepend($containerBuilder);
	}

	public function testRegisterCustomConnectionClass(): void
	{
		$this->loadExtensions();

		$doctrineConfig = $this->container->getExtensionConfig('doctrine');
		if (!isset($doctrineConfig[0]) || !isset($doctrineConfig[0]['dbal']) || !isset($doctrineConfig[0]['dbal']['wrapper_class'])) {
			Assert::fail();
		}

		Assert::assertSame(Connection::class, $doctrineConfig[0]['dbal']['wrapper_class']);
	}

	public function testLoadExtension(): void
	{
		$this->loadExtensions();

		$this->assertContainerBuilderHasParameter(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_PARAMETER_CUSTOM_CONNECTION_CLASS,
			false
		);
	}

	public function testTurnOffDefaultConnectionIntegration(): void
	{
		$this->loadExtensions([
			'rabbit_mq_database_transaction_producer' => [
				'custom_connection_class' => true,
			],
		]);

		$this->assertContainerBuilderHasParameter(
			RabbitMqDatabaseTransactionProducerExtension::CONTAINER_PARAMETER_CUSTOM_CONNECTION_CLASS,
			true
		);
	}

	/**
	 * @param mixed[] $configuration format: extensionAlias(string) => configuration(mixed[])
	 */
	private function loadExtensions(array $configuration = []): void
	{
		$configurations = [];
		foreach ($this->container->getExtensions() as $extensionAlias => $extension) {
			$configurations[$extensionAlias] = [];
			if (array_key_exists($extensionAlias, $this->getMinimalConfiguration())) {
				$this->container->loadFromExtension($extensionAlias, $this->getMinimalConfiguration()[$extensionAlias]);
				$configurations[$extensionAlias][] = $this->getMinimalConfiguration()[$extensionAlias];
			}
			if (array_key_exists($extensionAlias, $configuration)) {
				$this->container->loadFromExtension($extensionAlias, $configuration[$extensionAlias]);
				$configurations[$extensionAlias][] = $configuration[$extensionAlias];
			}
		}
		foreach ($this->container->getExtensions() as $extensionAlias => $extension) {
			if ($extension instanceof PrependExtensionInterface) {
				$extension->prepend($this->container);
			}
		}
		foreach ($this->container->getExtensions() as $extensionAlias => $extension) {
			$extension->load($configurations[$extensionAlias], $this->container);
		}
	}

}
