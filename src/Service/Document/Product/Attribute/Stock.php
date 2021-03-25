<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Stock as StockSchema;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Stock
 * Logic for accessing the stock of the SKU (product)
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Stock extends IntegrationSchemaPropertyHandler
{

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        foreach ($this->getData() as $item)
        {
            $stockValue = $item['value'] ?? false;
            if($stockValue)
            {
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_STOCK][] = $this->getStockSchema($item['value'], $item['availability']);
            }
        }

        return $content;
    }

    /**
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function getQuery(?string $propertyName = null): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(["LOWER(HEX(id)) AS {$this->getDiIdField()}", "available_stock AS value", "NULL as availability"])
            ->from("product")
            ->andWhere('version_id = :live')
            ->andWhere("JSON_SEARCH(category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            #->andWhere('id IN (:ids)')
            #->setParameter('ids', Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY)
            ->setParameter("channelRootCategoryId", $this->getConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }


}
