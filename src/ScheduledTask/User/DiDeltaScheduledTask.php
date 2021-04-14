<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\ScheduledTask\User;

use Boxalino\DataIntegration\Integrate\Mode\DeltaTrait;
use Boxalino\DataIntegration\Integrate\Type\UserTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\UserDeltaIntegrationHandlerInterface;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\ScheduledTask\DiGenericAbstractScheduledTask;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * Class DeltaDataIntegration
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:delta:user [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
abstract class DiDeltaScheduledTask extends DiGenericAbstractScheduledTask
{
    use DeltaTrait;
    use UserTrait;

    /**
     * @var UserDeltaIntegrationHandlerInterface
     */
    protected $integrationHandler;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        EntityRepositoryInterface $scheduledTaskRepository,
        OrderDeltaIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager, $scheduledTaskRepository);
    }


}
