<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Util;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;

/**
 * @package Boxalino\DataIntegration\Service\Util
 */
class DiFlaggedIdHandler implements DiFlaggedIdHandlerInterface
{

    use DiFlaggedIdsTrait;

    /**
     * @var string
     */
    protected $handlerIntegrateTime;

    public function __construct(
        Connection $connection
    ){
        $this->connection = $connection;
    }

    /**
     * @return string
     */
    public function getHandlerIntegrateTime(): string
    {
        if(!$this->handlerIntegrateTime)
        {
            $this->setHandlerIntegrateTime((new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT));
        }

        return $this->handlerIntegrateTime;
    }

    /**
     * @param string $handlerIntegrateTime
     */
    public function setHandlerIntegrateTime(string $handlerIntegrateTime) : void
    {
        $this->handlerIntegrateTime = $handlerIntegrateTime;
    }


}
