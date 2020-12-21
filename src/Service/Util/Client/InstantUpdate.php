<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Util\Client;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\Service\Util\Configuration;

class InstantUpdate
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Configuration
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
     * Instant Update client constructor.
     *
     * @param Configuration $configurator
     */
    public function __construct(Configuration $configurator, LoggerInterface $logger, string $environment)
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
     * @return Configuration
     */
    public function getConfigurator() : Configuration
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
