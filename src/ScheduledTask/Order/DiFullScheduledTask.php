<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\ScheduledTask\Order;

use Boxalino\DataIntegration\Integrate\Mode\FullTrait;
use Boxalino\DataIntegration\Integrate\Type\OrderTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\OrderIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\ScheduledTask\DiGenericAbstractScheduledTask;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * Class FullDataIntegration
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:full:order [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
abstract class DiFullScheduledTask extends DiGenericAbstractScheduledTask
{
    use FullTrait;
    use OrderTrait;

    /**
     * @var OrderIntegrationHandlerInterface
     */
    protected $integrationHandler;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        EntityRepositoryInterface $scheduledTaskRepository,
        OrderIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager, $scheduledTaskRepository);
    }

}
