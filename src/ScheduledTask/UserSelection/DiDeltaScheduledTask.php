<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\ScheduledTask\UserSelection;

use Boxalino\DataIntegrationDoc\Framework\Integrate\Mode\Configuration\DeltaTrait;
use Boxalino\DataIntegrationDoc\Framework\Integrate\Type\UserSelectionTrait;
use Boxalino\DataIntegrationDoc\Framework\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\UserSelectionDeltaIntegrationHandlerInterface;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\ScheduledTask\DiGenericAbstractScheduledTask;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * Class DiDeltaScheduledTask
 *
 * Use to trigger the data integration processes for doc_user_selection (wishlist)
 * ex: php bin/console boxalino:di:delta:user_selection [account]
 *
 * @package Boxalino\DataIntegration\ScheduledTask\UserSelection
 */
abstract class DiDeltaScheduledTask extends DiGenericAbstractScheduledTask
{
    use DeltaTrait;
    use UserSelectionTrait;

    /**
     * @var UserSelectionDeltaIntegrationHandlerInterface
     */
    protected $integrationHandler;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        EntityRepositoryInterface $scheduledTaskRepository,
        UserSelectionDeltaIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager, $scheduledTaskRepository);
    }


}
