<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegrationDoc\Doc\Schema\Stock as StockSchema;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
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
class Stock extends ModeIntegrator
{

    use DeltaInstantAddTrait;

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
    public function _getQuery(?string $propertyName = null): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(["LOWER(HEX(id)) AS {$this->getDiIdField()}", "available_stock AS value", "NULL as availability"])
            ->from("product")
            ->andWhere('version_id = :live')
            ->andWhere("JSON_SEARCH(category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            ->orderBy("product.product_number", "DESC")
            ->addOrderBy("product.created_at", "DESC")
            ->setParameter("channelRootCategoryId", $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setFirstResult($this->getFirstResultByBatch())
            ->setMaxResults($this->getSystemConfiguration()->getBatchSize());

        return $query;
    }


}
