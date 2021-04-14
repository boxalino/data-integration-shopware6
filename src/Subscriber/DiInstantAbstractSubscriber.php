<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Subscriber;

use Boxalino\DataIntegration\Integrate\DiAbstractTrait;
use Boxalino\DataIntegration\Integrate\DiIntegrateTrait;
use Boxalino\DataIntegration\Integrate\DiLoggerTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Psr\Log\LoggerInterface;

/**
 * Class DiInstantAbstractSubscriber
 *
 * @package Boxalino\DataIntegration\Service
 */
abstract class DiInstantAbstractSubscriber implements DiInstantSubscriberInterface
{

    use DiLoggerTrait;
    use DiAbstractTrait;
    use DiIntegrateTrait;

    /**
     * @var Configuration
     */
    protected $configurationManager;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager
    ){
        $this->configurationManager = $configurationManager;
        $this->logger = $logger;
        $this->environment = $environment;
    }

    /**
     * @param array $ids
     * @throws \Throwable
     */
    public function handle(array $ids) : void
    {
        try{
            /** @var ConfigurationDataObject $configuration */
            foreach($this->getConfigurations() as $configuration)
            {
                if($this->canRun($configuration))
                {
                    $this->integrateByIds($configuration, $ids);
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
