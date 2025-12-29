<?php
namespace Boxalino\DataIntegration\ScheduledTask;

use Boxalino\DataIntegrationDoc\Framework\Integrate\DiAbstractTrait;
use Boxalino\DataIntegrationDoc\Framework\Integrate\DiIntegrateTrait;
use Boxalino\DataIntegrationDoc\Framework\Integrate\DiLoggerTrait;
use Boxalino\DataIntegrationDoc\Framework\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
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
        EntityRepository $scheduledTaskRepository
    ){
        parent::__construct($scheduledTaskRepository);

        $this->configurationManager = $configurationManager;
        $this->logger = $logger;
        $this->environment = $environment;
    }

    /**
     * Triggers the data exporter for a specific account if so it is set
     *
     * @throws \Exception
     */
    public function run(): void
    {
        try{
            $this->getLogger()->info("Boxalino DI: START OF SCHEDULED TASK");
            /** @var ConfigurationDataObject $configuration */
            foreach($this->getConfigurations() as $configuration)
            {
                if($this->canRun($configuration))
                {
                    $this->integrate($configuration);
                    continue;
                }
            }
            $this->getLogger()->info("Boxalino DI: END OF SCHEDULED TASK");
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
