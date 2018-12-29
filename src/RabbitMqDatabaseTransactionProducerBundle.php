<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\ConnectionCompilerPass;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\RabbitMq\Producer\ProducerCompilerPass;

class RabbitMqDatabaseTransactionProducerBundle extends \Symfony\Component\HttpKernel\Bundle\Bundle
{

	/**
	 * @codeCoverageIgnore does not define any logic
	 *
	 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
	 */
	public function build(ContainerBuilder $container): void
	{
		parent::build($container);

		$container->addCompilerPass(new ConnectionCompilerPass());

		// must run before \OldSound\RabbitMqBundle\DependencyInjection\Compiler\RegisterPartsPass
		$container->addCompilerPass(new ProducerCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
	}

}
