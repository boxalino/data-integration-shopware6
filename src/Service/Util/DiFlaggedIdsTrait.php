<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Util;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
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
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getFlaggedIdsByEntityNameAndDateFromTo(string $entityName, string $dateFrom, string $dateTo) : array
    {
        $query = $this->getFlaggedIdsByEntityNameAndDateSql($entityName, $dateFrom, $dateTo);
        $content = $query->execute()->fetchAll(FetchMode::COLUMN);

        return array_unique($content);
    }

    /**
     * @param string $entityName
     * @param string $dateTo
     * @return QueryBuilder
     */
    protected function getFlaggedIdsByEntityNameAndDateSql(string $entityName, string $dateFrom, string $dateTo) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(['DISTINCT(entity_id)'])
            ->from($this->getDiFlaggedIdTableNameByType($entityName))
            ->andWhere("STR_TO_DATE(created_at,  '%Y-%m-%d %H:%i:%s.%f') < '$dateTo'")
            ->andWhere("STR_TO_DATE(created_at,  '%Y-%m-%d %H:%i:%s.%f') > '$dateFrom'");

        return $query;
    }

    /**
     * @param string $entityName
     * @param string $date
     * @return void
     */
    public function deleteFlaggedIdsByEntityNameAndDate(string $entityName, string $date) : void
    {
        $tableName = $this->getDiFlaggedIdTableNameByType($entityName);
        $query = <<<SQL
# boxalino::di::$tableName::delete
DELETE FROM {$tableName} WHERE STR_TO_DATE(created_at,  "%Y-%m-%d %H:%i:%s.%f") < "{$date}";
SQL;

        $this->connection->executeUpdate($query);
    }

    /**
     * @param QueryBuilder $query
     * @param string $entityName
     * @param string $alias
     * @param string $otherConditional
     * @param string $lastSyncCheck
     * @param string|null $mapField
     *
     * @return QueryBuilder
     */
    public function addOrDeltaConditionByEntityNameDate(QueryBuilder $query, string $entityName, string $alias, string $otherConditional, string $lastSyncCheck, ?string $mapField = "id") : QueryBuilder
    {
        $handlerTime = $this->getHandlerIntegrateTime();
        $timeConditional = "STR_TO_DATE(bxfid.created_at,  '%Y-%m-%d %H:%i:%s.%f') < '$handlerTime' AND STR_TO_DATE(bxfid.created_at,  '%Y-%m-%d %H:%i') >= '$lastSyncCheck' ";
        $query->leftJoin($alias, $this->getDiFlaggedIdTableNameByType($entityName), "bxfid",
            "$alias.$mapField = bxfid.id"
        )
            ->andWhere("($timeConditional AND bxfid.entity_id IS NOT NULL) OR ($otherConditional)");

        return $query;
    }

    /**
     * @return string
     */
    public function getDiFlaggedIdTableNameByType(string $type) : string
    {
        return DiFlaggedIdHandlerInterface::ENTITY_NAME_PREFIX . strtolower($type);
    }

    /**
     * @return string
     */
    abstract public function getHandlerIntegrateTime() : string;


}
