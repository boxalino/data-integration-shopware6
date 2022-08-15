<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\ScheduledTask\Order;

use Boxalino\DataIntegrationDoc\Framework\Integrate\Mode\Configuration\FullTrait;
use Boxalino\DataIntegrationDoc\Framework\Integrate\Type\OrderTrait;
use Boxalino\DataIntegrationDoc\Framework\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\OrderIntegrationHandlerInterface;
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
