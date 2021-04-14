<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Integrate;

use Psr\Log\LoggerInterface;

/**
 * Trait DiLoggerTrait
 * @package Boxalino\DataIntegration\Console
 */
trait DiLoggerTrait
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $environment;

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
            $this->getLogger()->warning("Boxalino DI error: " . $exception->getMessage());
            throw $exception;
        }

        $this->getLogger()->info("Boxalino DI error: " . $exception->getMessage());
        throw $exception;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger() : LoggerInterface
    {
        return $this->logger;
    }


}
