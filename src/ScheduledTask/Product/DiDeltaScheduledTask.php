<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\ScheduledTask\Product;

use Boxalino\DataIntegrationDoc\Framework\Integrate\Mode\Configuration\DeltaTrait;
use Boxalino\DataIntegrationDoc\Framework\Integrate\Type\ProductTrait;
use Boxalino\DataIntegrationDoc\Framework\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\ProductDeltaIntegrationHandlerInterface;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\ScheduledTask\DiGenericAbstractScheduledTask;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * Class DeltaDataIntegration
 *
 * @package Boxalino\DataIntegration\Service
 */
abstract class DiDeltaScheduledTask extends DiGenericAbstractScheduledTask
{
    use DeltaTrait;
    use ProductTrait;

    /**
     * @var ProductDeltaIntegrationHandlerInterface
     */
    protected $integrationHandler;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        EntityRepositoryInterface $scheduledTaskRepository,
        ProductDeltaIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager, $scheduledTaskRepository);
    }


}
