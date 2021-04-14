<?php
namespace Boxalino\DataIntegration\ScheduledTask;

use Boxalino\DataIntegration\Integrate\DiAbstractTrait;
use Boxalino\DataIntegration\Integrate\DiIntegrateTrait;
use Boxalino\DataIntegration\Integrate\DiLoggerTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailDocLoadException;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailSyncException;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Boxalino\Exporter\Service\ExporterFullInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;

/**
 * Class DiGenericAbstractScheduledTask
 * Can be used for Delta and Full
 * @package Boxalino\DataIntegration\ScheduledTask
 */
abstract class DiGenericAbstractScheduledTask extends ScheduledTaskHandler
{
    use DiLoggerTrait;
    use DiAbstractTrait;
    use DiIntegrateTrait;

    /**
     * @var DiConfigurationInterface
     */
    protected $configurationManager;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        EntityRepositoryInterface $scheduledTaskRepository
    ){
        parent::__construct($scheduledTaskRepository);

        $this->configurationManager = $configurationManager;
        $this->logger = $logger;
        $this->environment = $environment;
    }

    /**
     * Set the class with the scheduled task configuration
     *
     * @return iterable
     */
    abstract static function getHandledMessages(): iterable;

    /**
     * Triggers the full data exporter for a specific account if so it is set
     *
     * @throws \Exception
     */
    public function run(): void
    {
        try{
            /** @var ConfigurationDataObject $configuration */
            foreach($this->getConfigurations() as $configuration)
            {
                if($this->canRun($configuration))
                {
                    $this->integrate($configuration);
                    continue;
                }
            }
        } catch (\Exception $exc)
        {
            $this->getLogger()->error($exc->getMessage());
        }
    }

    /**
     * @return DiConfigurationInterface
     */
    public function getConfigurationManager() : DiConfigurationInterface
    {
        return $this->configurationManager;
    }

}
