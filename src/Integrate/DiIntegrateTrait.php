<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Integrate;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailDocLoadException;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailSyncException;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Mode\InstantIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\OrderIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;

/**
 * Trait DiIntegrateTrait
 * Provides strategies for triggering the integration handlers integrate logic
 * Must be used with other traits
 *
 * @package Boxalino\DataIntegration\Console
 */
trait DiIntegrateTrait
{

    /**
     * @var IntegrationHandlerInterface
     */
    protected $integrationHandler;

    /**
     * @throws \Throwable
     */
    public function integrate(ConfigurationDataObject $configuration) : void
    {
        try {
            if($this->getIntegrationHandler() instanceof IntegrationDocHandlerInterface)
            {
                $this->getIntegrationHandler()->setSystemConfiguration($configuration);
            }

            $this->getIntegrationHandler()->manageConfiguration($configuration);
            $this->getLogger()->info(
                "Boxalino DI: Start {$this->getIntegrationHandler()->getIntegrationType()} {$this->getIntegrationHandler()->getIntegrationMode()} {$this->getIntegrationHandler()->getDiConfiguration()->getTm()} sync for {$configuration->getAccount()}"
            );

            $this->getIntegrationHandler()->integrate();

            $this->getLogger()->info(
                "Boxalino DI: End of {$this->getIntegrationHandler()->getIntegrationType()} {$this->getIntegrationHandler()->getIntegrationMode()} {$this->getIntegrationHandler()->getDiConfiguration()->getTm()} sync for {$configuration->getAccount()}"
            );
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

    public function getIntegrationHandler(): IntegrationHandlerInterface
    {
        return $this->integrationHandler;
    }


}
