<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\ScheduledTask\Product;

use Boxalino\DataIntegration\Integrate\Mode\Configuration\InstantTrait;
use Boxalino\DataIntegration\Integrate\Type\ProductTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\ProductInstantIntegrationHandlerInterface;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\ScheduledTask\DiGenericAbstractScheduledTask;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * Class DiInstantScheduledTask
 *
 * @package Boxalino\DataIntegration\Service
 */
abstract class DiInstantScheduledTask extends DiGenericAbstractScheduledTask
{
    use InstantTrait;
    use ProductTrait;

    /**
     * @var ProductInstantIntegrationHandlerInterface
     */
    protected $integrationHandler;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        EntityRepositoryInterface $scheduledTaskRepository,
        ProductInstantIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager, $scheduledTaskRepository);
    }


}
