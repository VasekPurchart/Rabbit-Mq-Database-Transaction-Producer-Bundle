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
		$container->setParameter('kernel.debug', true);
		$extension = new RabbitMqDatabaseTransactionProducerExtension();
		$this->expectException(
			\VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection\DoctrineExtensionNotFoundException::class
		);
		$extension->prepend($container);
	}

	public function testRegisterCustomConnectionClass(): void
	{
		$container = self::createContainer();
		$container->setParameter('kernel.debug', true);
		$container->registerExtension(new DoctrineExtension());
		$container->registerExtension(new RabbitMqDatabaseTransactionProducerExtension());

		self::loadRegisteredExtensionsUsingConfigurationByAlias($container);

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
		$container->setParameter('kernel.debug', true);
		$container->registerExtension(new DoctrineExtension());
		$container->registerExtension(new RabbitMqDatabaseTransactionProducerExtension());

		self::loadRegisteredExtensionsUsingConfigurationByAlias($container, $configuration);

		self::assertContainerHasParameter($container, $parameterName);
		Assert::assertSame($expectedParameterValue, $container->getParameter($parameterName));
	}

	private static function createContainer(): ContainerBuilder
	{
		$container = new ContainerBuilder(new ParameterBag([]));
		$container->getCompilerPassConfig()->setOptimizationPasses([]);
		$container->getCompilerPassConfig()->setRemovingPasses([]);
		$container->getCompilerPassConfig()->setAfterRemovingPasses([]);

		return $container;
	}

	/**
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 * @param mixed[][]|array $configurationsByAlias format: extensionAlias(string) => configuration(mixed[])
	 */
	private static function loadRegisteredExtensionsUsingConfigurationByAlias(
		ContainerBuilder $container,
		array $configurationsByAlias = []
	): void
	{
		foreach ($container->getExtensions() as $extension) {
			if ($extension instanceof PrependExtensionInterface) {
				$extension->prepend($container);
			}
		}

		foreach ($container->getExtensions() as $extensionAlias => $extension) {
			if (array_key_exists($extensionAlias, $configurationsByAlias)) {
				$extension->load([$configurationsByAlias[$extensionAlias]], $container);
			} else {
				$extension->load([], $container);
			}
		}
	}

	private static function assertContainerHasParameter(
		ContainerBuilder $container,
		string $parameterName
	): void
	{
		Assert::assertTrue(
			$container->hasParameter($parameterName),
			sprintf('Expecting the container to have parameter `%s`.', $parameterName)
		);
	}

}
