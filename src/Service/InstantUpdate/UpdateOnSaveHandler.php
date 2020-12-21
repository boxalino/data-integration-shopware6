<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate;

use Boxalino\DataIntegration\Service\InstantUpdate\Util\InstantUpdateClient;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
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
class UpdateOnSaveHandler implements UpdateOnSaveHandlerInterface
{

    /**
     * @var InstantUpdateClient
     */
    protected $client;

    /**
     * @var IntegrationHandlerInterface
     */
    protected $integrationHandler;

    public function __construct(
        InstantUpdateClient $client,
        IntegrationHandlerInterface $integrationHandler
    ){
        $this->client = $client;
        $this->integrationHandler = $integrationHandler;
    }

    /**
     * @param array $ids
     */
    public function handle(array $ids): void
    {
        foreach($this->client->getConfigurator()->getConfigurationFromCache() as $configuration)
        {
            if($configuration->getAllowInstantUpdateRequests())
            {
                try {
                    $this->integrationHandler->setIds($ids)->setConfiguration($configuration);
                    $documents = $this->integrationHandler->getDocs();

                    $tm = date("YmDHis");
                    foreach($documents as $type => $document)
                    {
                        $this->client->log($document);
                        
                        $this->client->getClient()->send(
                            new Request(
                                'POST',
                                $configuration->getEndpoint(),
                                [
                                    'Content-Type' => 'application/json'
                                ],
                                $document
                            ),
                            [
                                'auth' => [$configuration->getAccount(), $configuration->getApiKey(), 'basic'],
                                'client' => $configuration->getAccount(),
                                'doc' => $type,
                                'type'=> "D",
                                'dev' => $configuration->getIsDev(),
                                'tm' => $tm
                            ]
                        );
                         
                    }
                } catch (\Throwable $exception)
                {
                    $this->client->logOrThrowException($exception);
                }
            }
        }
    }

}
