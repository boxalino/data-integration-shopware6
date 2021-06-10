<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Mode;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegration\Service\Util\DiFlaggedIdsTrait;
use Doctrine\DBAL\Connection;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandler;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;

/**
 * @package Boxalino\DataIntegration\Service\Integration\Mode
 */
abstract class AbstractIntegrationHandler extends IntegrationHandler
{

    use DiFlaggedIdsTrait;
    use IntegrationDocHandlerTrait;

    public function __construct(
        Connection $connection
    ){
        $this->connection = $connection;
        parent::__construct();
    }

    /**
     * @return string
     */
    abstract public function getEntityName() : string;

    /**
     * Clear the ids that have been synced
     */
    public function clearDiFlaggedIds(): void
    {
        try{
            $this->deleteFlaggedIdsByEntityName($this->getEntityName());
        } catch(\Throwable $exception)
        {
            throw $exception;
        }
    }


}
