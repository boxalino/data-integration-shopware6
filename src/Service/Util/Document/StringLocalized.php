<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Util\Document;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class StringLocalized
 * Example of string localized properties: title, description, meta-information, category names, etc
 * @duplicate from generic Translation Item export
 *
 * @package Boxalino\DataIntegration\Service\InstantUpdate\Document\Util
 */
class StringLocalized
{

    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(
        Connection $connection
    ){
        $this->connection = $connection;
    }

    /**
     * @param string $mainTable
     * @param string $mainTableIdField
     * @param string $idField
     * @param string $versionIdField
     * @param string $localizedFieldName
     * @param array $groupByFields
     * @param array $languages
     * @param string $defaultLanguage
     * @param array $whereConditions
     * @return QueryBuilder
     */
    public function getLocalizedFields(string $mainTable, string $mainTableIdField, string $idField,
                                       string $versionIdField, string $localizedFieldName, array $groupByFields,
                                       array $languages, string $defaultLanguage, array $whereConditions = []
    ) : QueryBuilder {
        $alias = []; $innerConditions = []; $leftConditions = []; $selectFields = array_merge($groupByFields, []);
        $inner='inner'; $left='left';
        $default = $mainTable . "_default";
        $defaultConditions = [
            "$mainTable.$mainTableIdField = $default.$idField",
            "$mainTable.$versionIdField = $default.$versionIdField",
            "LOWER(HEX($default.language_id)) = '$defaultLanguage'"
        ];
        foreach($languages as $languageId=>$languageCode)
        {
            $t1 = $mainTable . "_" . $languageCode . "_" . $left;
            $alias[$languageCode] = $t1;
            $selectFields[] = "IF(MIN($t1.$localizedFieldName) IS NULL, MIN($default.$localizedFieldName), MIN($t1.$localizedFieldName)) as $languageCode";
            $leftConditions[$languageCode] = [
                "$mainTable.$mainTableIdField = $t1.$idField",
                "$mainTable.$versionIdField = $t1.$versionIdField",
                "LOWER(HEX($t1.language_id)) = '$languageId'"
            ];
        }

        $query = $this->connection->createQueryBuilder();
        $query->select($selectFields)
            ->from($mainTable)
            ->leftJoin($mainTable, $mainTable, $default, implode(" AND ", $defaultConditions));

        foreach($languages as $languageCode)
        {
            $query->leftJoin($mainTable, $mainTable, $alias[$languageCode], implode(" AND ", $leftConditions[$languageCode]));
        }

        foreach($whereConditions as $condition)
        {
            $query->andWhere($condition);
        }

        $query->groupBy($groupByFields);
        return $query;
    }

}
