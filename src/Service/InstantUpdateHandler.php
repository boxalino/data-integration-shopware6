<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service;

use Boxalino\DataIntegration\Service\Util\Configuration;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailDocLoadException;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailSyncException;
use Boxalino\DataIntegrationDoc\Service\GcpClientInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;

/**
 * InstantUpdate Boxalino handler
 *
 * It is used to update elements (status, properties, etc)
 * It does not remove existing items
 * It does not add new items
 *
 * @package Boxalini\Exporter\Service\InstantUpdate
 */
class InstantUpdateHandler implements InstantUpdateHandlerInterface
{

    /**
     * @var GcpClientInterface
     */
    protected $client;

    /**
     * @var IntegrationHandlerInterface
     */
    protected $integrationHandler;

    /**
     * @var Configuration 
     */
    protected $configurationManager;

    public function __construct(
        Configuration $configurationManager,
        GcpClientInterface $client,
        IntegrationHandlerInterface $integrationHandler
    ){
        $this->configurationManager = $configurationManager;
        $this->client = $client;
        $this->integrationHandler = $integrationHandler;
    }

    /**
     * @param array $ids
     */
    public function handle(array $ids): void
    {
        /** @var ConfigurationDataObject $configuration */
        foreach($this->configurationManager->getInstantUpdateConfigurations() as $configuration)
        {
            if($configuration->getAllowInstantUpdateRequests())
            {
                try {
                    $configuration->setData("type", GcpClientInterface::GCP_TYPE_PRODUCT);
                    $this->integrationHandler->setIds($ids)->setConfiguration($configuration);
                    $documents = $this->integrationHandler->getDocs();                    
                    $this->client->send($configuration, $documents, GcpClientInterface::GCP_MODE_INSTANT_UPDATE);
                } catch (FailDocLoadException $exception)
                {
                    //maybe a fallback to save the content of the documents and try again later or have the integration team review
                    $this->client->logOrThrowException($exception);
                } catch (FailSyncException $exception)
                {
                    //save that the product id was not synced (relevant for full error data sync alerts)
                    $this->client->logOrThrowException($exception);
                } catch (\Throwable $exception)
                {
                    $this->client->logOrThrowException($exception);
                }
            }
        }
    }

}
