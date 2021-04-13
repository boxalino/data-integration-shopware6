<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Subscriber;

use Boxalino\DataIntegration\Console\DiLoggerTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailDocLoadException;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailSyncException;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Mode\InstantIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\OrderIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Psr\Log\LoggerInterface;

/**
 * Class GenericDiSubscriber
 *
 * @package Boxalino\DataIntegration\Service
 */
abstract class InstantDiSubscriber implements InstantDiSubscriberInterface
{

    use DiLoggerTrait;

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
     * Logic for accessing the configurations
     * @return array
     */
    abstract function getConfigurations() : array;

    /**
     * @param ConfigurationDataObject $configurationDataObject
     * @return bool
     */
    abstract function canRun(ConfigurationDataObject $configurationDataObject) : bool;

    /**
     * @return IntegrationHandlerInterface
     */
    abstract function getIntegrationHandler() : IntegrationHandlerInterface;

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
                    $this->integrate($configuration, $ids);
                    continue;
                }
            }
        } catch (\Exception $exc)
        {
            $this->getLogger()->error($exc->getMessage());
        }
    }

    /**
     * @throws \Throwable
     */
    protected function integrate(ConfigurationDataObject $configuration, array $ids) : void
    {
        try {
            if($this->getIntegrationHandler() instanceof IntegrationDocHandlerInterface)
            {
                $this->getIntegrationHandler()->setSystemConfiguration($configuration);
            }

            if($this->getIntegrationHandler() instanceof InstantIntegrationInterface)
            {
                $this->getLogger()->info(
                    "Boxalino DI: Start {$this->getIntegrationHandler()->getIntegrationType()} {$this->getIntegrationHandler()->getIntegrationMode()} sync for {$configuration->getAccount()}"
                );

                $this->getIntegrationHandler()->setIds($ids);
                $this->getIntegrationHandler()
                    ->manageConfiguration($configuration)
                    ->integrate();

                $this->getLogger()->info(
                    "Boxalino DI: End of {$this->getIntegrationHandler()->getIntegrationType()} {$this->getIntegrationHandler()->getIntegrationMode()} sync for {$configuration->getAccount()}"
                );
            }
        } catch (FailDocLoadException $exception)
        {
            //maybe a fallback to save the content of the documents and try again later or have the integration team review
            $this->logOrThrowException($exception);
        } catch (FailSyncException $exception)
        {
            //save that the product id was not synced (relevant for full error data sync alerts)
            $this->logOrThrowException($exception);
        } catch (\Throwable $exception)
        {
            $this->logOrThrowException($exception);
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
