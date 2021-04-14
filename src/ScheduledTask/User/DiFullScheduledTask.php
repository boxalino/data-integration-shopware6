<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\ScheduledTask\User;

use Boxalino\DataIntegration\Integrate\Mode\FullTrait;
use Boxalino\DataIntegration\Integrate\Type\UserTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\UserIntegrationHandlerInterface;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\ScheduledTask\DiGenericAbstractScheduledTask;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * Class FullDataIntegration
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:full:user [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
abstract class DiFullScheduledTask extends DiGenericAbstractScheduledTask
{
    use FullTrait;
    use UserTrait;

    /**
     * @var UserIntegrationHandlerInterface
     */
    protected $integrationHandler;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        EntityRepositoryInterface $scheduledTaskRepository,
        UserIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager, $scheduledTaskRepository);
    }


}
