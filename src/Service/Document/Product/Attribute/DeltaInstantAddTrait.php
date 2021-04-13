<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Strategy for the delta-instant integration modes
 *
 * @package Boxalino\DataIntegration
 */
trait DeltaInstantAddTrait
{

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQueryInstant(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->_getQuery($propertyName);
        return $this->addInstantCondition($query);
    }

    /**
     * If the logic for delta needs to be updated - rewrite this function
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQueryDelta(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->_getQuery($propertyName);
        return $this->addDeltaCondition($query);
    }

}
