<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Util;

use Boxalino\DataIntegration\Core\DataIntegration\DiFlaggedIdDefinition;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @package Boxalino\DataIntegration\Service\Util
 */
trait DiFlaggedIdsTrait
{

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param string $entityName
     * @return array
     */
    public function getFlaggedIdsByEntityName(string $entityName) : array
    {
        return $this->getFlaggedIdsByEntityNameAndDate($entityName, $this->getHandlerIntegrateTime());
    }

    /**
     * @param string $entityName
     * @param string $date
     * @return array
     */
    public function getFlaggedIdsByEntityNameAndDate(string $entityName, string $date) : array
    {
        $query = $this->getFlaggedIdsByEntityNameAndDateSql($entityName, $date);
        $content = $query->execute()->fetchAll(FetchMode::COLUMN);

        return array_unique($content);
    }

    /**
     * @param string $entityName
     * @param string $date
     * @return QueryBuilder
     */
    protected function getFlaggedIdsByEntityNameAndDateSql(string $entityName, string $date) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(['entity_id'])
            ->from($this->getDiFlaggedIdTableName())
            ->where("entity_name = '$entityName'")
            ->andWhere("STR_TO_DATE(created_at,  '%Y-%m-%d %H:%i:%s.%f') < '$date'")
            ->orderBy("STR_TO_DATE(created_at, '%Y-%m-%d %H:%i:%s.%f')", "DESC");

        return $query;
    }

    /**
     * @param string $entityName
     * @return void
     */
    public function deleteFlaggedIdsByEntityName(string $entityName) : void
    {
        $this->deleteFlaggedIdsByEntityNameAndDate($entityName, $this->getHandlerIntegrateTime());
    }

    /**
     * @param string $entityName
     * @param string $date
     * @return void
     */
    public function deleteFlaggedIdsByEntityNameAndDate(string $entityName, string $date) : void
    {
        $tableName = $this->getDiFlaggedIdTableName();
        $query = <<<SQL
DELETE FROM {$tableName} WHERE entity_name="$entityName" AND STR_TO_DATE(created_at,  "%Y-%m-%d %H:%i:%s.%f") < "{$date}";
SQL;

        $this->connection->executeUpdate($query);
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    public function addOrDeltaConditionByEntityNameDate(QueryBuilder $query, string $entityName, string $alias, string $otherConditional, ?string $mapField = "id") : QueryBuilder
    {
        $handlerTime = $this->getHandlerIntegrateTime();
        $query->leftJoin($alias, $this->getDiFlaggedIdTableName(), "bxfid",
            "LOWER(HEX($alias.$mapField)) = bxfid.entity_id AND bxfid.entity_name='$entityName'"
        )
            ->andWhere("(STR_TO_DATE(bxfid.created_at,  '%Y-%m-%d %H:%i:%s.%f') < '$handlerTime' AND bxfid.entity_id IS NOT NULL) OR ($otherConditional)");

        return $query;
    }

    /**
     * An explanatory sample, no real use in current implementation
     *
     * @param string $entityName
     * @param string $date
     * @param int $limit
     * @return Criteria
     */
    public function getCriteriaByEntityNameDateAndLimit(string $entityName, string $date, int $limit = 200) : Criteria
    {
        $criteria = new Criteria();
        $criteria->addFilter(new AndFilter(
            [
                new EqualsFilter('entityName', $entityName),
                new RangeFilter("createdAt", [RangeFilter::LTE => $date])
            ]
        ));

        $criteria->addSorting(new FieldSorting("createdAt", FieldSorting::DESCENDING));
        $criteria->setLimit($limit);

        return $criteria;
    }

    /**
     * @return string
     */
    public function getDiFlaggedIdTableName() : string
    {
        return DiFlaggedIdDefinition::ENTITY_NAME;
    }

    /**
     * @return string
     */
    abstract public function getHandlerIntegrateTime() : string;


}
