<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Util;

use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailDocLoadException;
use Boxalino\DataIntegrationDoc\Service\GcpClient;
use Boxalino\DataIntegrationDoc\Service\GcpClientInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;

/**
 * Class Connector
 *
 * @package Boxalino\DataIntegration\Service\Util
 */
class Connector extends GcpClient
    implements GcpClientInterface
{

    /**
     * @var string
     */
    protected $environment;

    /**
     * Connector constructor.
     * @param LoggerInterface $logger
     * @param string $environment
     * @param int $timeout
     */
    public function __construct(LoggerInterface $logger, string $environment, int $timeout = 3)
    {
        $this->environment = $environment;
        $this->timeout = $timeout;
        parent::__construct($logger);
    }

    /**
     * Do not throw exception, the product update must not be blocked if the SOLR SYNC update does not work
     *
     * @param \Throwable $exception
     * @return bool
     * @throws \Throwable
     */
    public function logOrThrowException(\Throwable $exception)
    {
        if ($this->environment === 'prod') {
            $this->logger->warning("Boxalino API Data Integration error: " . $exception->getMessage());
            throw $exception;
        }

        $this->logger->info("Boxalino API Data Integration error: " . $exception->getMessage());
    }

}
