<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Util;

use Boxalino\DataIntegrationDoc\Service\GcpClient;
use Boxalino\DataIntegrationDoc\Service\GcpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Class InstantUpdate
 * 
 * @package Boxalino\DataIntegration\Service\Util\Client
 */
class Connector extends GcpClient
    implements GcpClientInterface
{

    /**
     * @param \Throwable $exception
     * @return bool
     * @throws \Throwable
     */
    public function logOrThrowException(\Throwable $exception): bool
    {
        if ($this->environment === 'dev') {
            $this->logger->info("Boxalino API InstantUpdate error: " . $exception->getMessage());
            throw $exception;
        }
        if ($this->environment === 'prod') {
            $this->logger->warning("Boxalino API InstantUpdate error: " . $exception->getMessage());
        }

        return false;
    }

}
