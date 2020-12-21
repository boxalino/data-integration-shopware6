<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Util;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class InstantUpdateClient
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var InstantUpdateConfiguration
     */
    protected $configurator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $environment = "dev";

    /**
     * InstantUpdateClient constructor.
     *
     * @param InstantUpdateConfiguration $configurator
     */
    public function __construct(InstantUpdateConfiguration $configurator, LoggerInterface $logger, string $environment)
    {
        $this->configurator = $configurator;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->client = new Client();
    }

    /**
     * @return Client
     */
    public function getClient() : Client
    {
        return $this->client;
    }

    /**
     * @return InstantUpdateConfiguration
     */
    public function getConfigurator() : InstantUpdateConfiguration
    {
        return $this->configurator;
    }

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

    /**
     * @param string $message
     */
    public function log(string $message) : void
    {
        $this->logger->info($message);
    }

}
