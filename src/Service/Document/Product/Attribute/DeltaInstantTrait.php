<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Strategy for the delta-instant integration modes
 *
 * @package Boxalino\DataIntegration
 */
trait DeltaInstantTrait
{

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQueryDelta(?string $propertyName = null) : QueryBuilder
    {
        $dateCriteria = $this->getSyncCheck() ?? date("Y-m-d H:i", strtotime("-60 min"));
        return $this->_getQuery($propertyName)->setParameter('lastSync', $dateCriteria);
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQueryInstant(?string $propertyName = null) : QueryBuilder
    {
        return $this->_getQuery($propertyName)->setParameter("ids", Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY);
    }

}
