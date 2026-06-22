<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\ScheduledTask\UserSelection;

use Boxalino\DataIntegrationDoc\Framework\Integrate\Mode\Configuration\FullTrait;
use Boxalino\DataIntegrationDoc\Framework\Integrate\Type\UserSelectionTrait;
use Boxalino\DataIntegrationDoc\Framework\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\UserSelectionIntegrationHandlerInterface;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\ScheduledTask\DiGenericAbstractScheduledTask;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

/**
 * Class DiFullScheduledTask
 *
 * Use to trigger the data integration processes for doc_user_selection (wishlist)
 * ex: php bin/console boxalino:di:full:user_selection [account]
 *
 * @package Boxalino\DataIntegration\ScheduledTask\UserSelection
 */
abstract class DiFullScheduledTask extends DiGenericAbstractScheduledTask
{
    use FullTrait;
    use UserSelectionTrait;

    /**
     * @var UserSelectionIntegrationHandlerInterface
     */
    protected $integrationHandler;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        EntityRepository $scheduledTaskRepository,
        UserSelectionIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager, $scheduledTaskRepository);
    }


}
