<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use Generator;
use PHPUnit\Framework\Assert;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\Connection;

class RabbitMqDatabaseTransactionProducerExtensionTest extends \PHPUnit\Framework\TestCase
{

	public function testDependsOnDoctrineBundle(): void
	{
		$container = self::createContainer();
		$extension = new RabbitMqDatabaseTransactionProducerExtension();
		$this->expectException(
			\VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection\DoctrineExtensionNotFoundException::class
		);
		$extension->prepend($container);
	}

	public function testRegisterCustomConnectionClass(): void
	{
		$container = self::createContainer();
		$container->registerExtension(new DoctrineExtension());
		$container->registerExtension(new RabbitMqDatabaseTransactionProducerExtension());

		$this->loadExtensions($container, []);

		$doctrineConfig = $container->getExtensionConfig('doctrine');
		if (!isset($doctrineConfig[0]) || !isset($doctrineConfig[0]['dbal'])
			|| !isset($doctrineConfig[0]['dbal']['wrapper_class'])) {
			Assert::fail();
		}

		Assert::assertSame(Connection::class, $doctrineConfig[0]['dbal']['wrapper_class']);
	}

	/**
	 * @return mixed[][]|\Generator
	 */
	public function configureContainerParameterDataProvider(): Generator
	{
		yield 'default connection integration' => [
			'configuration' => [],
			'parameterName' => 'rabbit_mq_database_transaction_producer_bundle.custom_connection_class',
			'expectedParameterValue' => false,
		];

		yield 'turn off default connection integration' => [
			'configuration' => [
				'rabbit_mq_database_transaction_producer' => [
					'custom_connection_class' => true,
				],
			],
			'parameterName' => 'rabbit_mq_database_transaction_producer_bundle.custom_connection_class',
			'expectedParameterValue' => true,
		];
	}

	/**
	 * @dataProvider configureContainerParameterDataProvider
	 *
	 * @param mixed[][]|array $configuration
	 * @param string $parameterName
	 * @param bool $expectedParameterValue
	 */
	public function testConfigureContainerParameter(
		array $configuration,
		string $parameterName,
		bool $expectedParameterValue
	): void
	{
		$container = self::createContainer();
		$container->registerExtension(new DoctrineExtension());
		$container->registerExtension(new RabbitMqDatabaseTransactionProducerExtension());

		$this->loadExtensions($container, $configuration);

		self::assertContainerHasParameter($container, $parameterName);
		Assert::assertSame($expectedParameterValue, $container->getParameter($parameterName));
	}

	/**
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 * @param mixed[]|array $configuration format: extensionAlias(string) => configuration(mixed[])
	 */
	private function loadExtensions(ContainerBuilder $container, array $configuration = []): void
	{
		$configurations = [];
		foreach ($container->getExtensions() as $extensionAlias => $extension) {
			$configurations[$extensionAlias] = [];

			if (array_key_exists($extensionAlias, $configuration)) {
				$container->loadFromExtension($extensionAlias, $configuration[$extensionAlias]);
				$configurations[$extensionAlias][] = $configuration[$extensionAlias];
			}
		}
		foreach ($container->getExtensions() as $extensionAlias => $extension) {
			if ($extension instanceof PrependExtensionInterface) {
				$extension->prepend($container);
			}
		}
		foreach ($container->getExtensions() as $extensionAlias => $extension) {
			$extension->load($configurations[$extensionAlias], $container);
		}
	}

	private static function createContainer(): ContainerBuilder
	{
		$container = new ContainerBuilder(new ParameterBag([]));
		$container->getCompilerPassConfig()->setOptimizationPasses([]);
		$container->getCompilerPassConfig()->setRemovingPasses([]);
		$container->getCompilerPassConfig()->setAfterRemovingPasses([]);

		$container->setParameter('kernel.debug', true);

		return $container;
	}

	private static function assertContainerHasParameter(ContainerBuilder $container, string $parameterName): void
	{
		Assert::assertTrue(
			$container->hasParameter($parameterName),
			sprintf('Container is missing required parameter `%s`.', $parameterName)
		);
	}

}
