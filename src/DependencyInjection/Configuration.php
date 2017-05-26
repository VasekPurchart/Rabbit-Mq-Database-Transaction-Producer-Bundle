<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements \Symfony\Component\Config\Definition\ConfigurationInterface
{

	const PARAMETER_CUSTOM_CONNECTION_CLASS = 'custom_connection_class';

	/** @var string */
	private $rootNode;

	public function __construct(
		string $rootNode
	)
	{
		$this->rootNode = $rootNode;
	}

	public function getConfigTreeBuilder(): TreeBuilder
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root($this->rootNode);

		$rootNode
			->children()
				->booleanNode(self::PARAMETER_CUSTOM_CONNECTION_CLASS)
					->info('Whether custom connection class for DBAL is used in the project')
					->defaultValue(false)
					->end()
			->end();

		return $treeBuilder;
	}

}
