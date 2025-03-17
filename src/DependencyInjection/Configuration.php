<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements \Symfony\Component\Config\Definition\ConfigurationInterface
{

	public const PARAMETER_CUSTOM_CONNECTION_CLASS = 'custom_connection_class';

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
		$treeBuilder = new TreeBuilder($this->rootNode);
		if (method_exists($treeBuilder, 'getRootNode')) {
			$rootNode = $treeBuilder->getRootNode();
		} else {
			// BC layer for symfony/config 4.1 and older
			$rootNode = $treeBuilder->root($this->rootNode);
		}

		$rootNode->children()->append($this->createCustomConnectionClassNode(self::PARAMETER_CUSTOM_CONNECTION_CLASS));

		return $treeBuilder;
	}

	private function createCustomConnectionClassNode(string $nodeName): BooleanNodeDefinition
	{
		$node = new BooleanNodeDefinition($nodeName);
		$node->info('Whether custom connection class for DBAL is used in the project');
		$node->defaultValue(false);

		return $node;
	}

}
