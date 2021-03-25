<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Category as CategorySchema;

/**
 * Class Category
 * Category is the only hierarchical property in Shopware6
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Category extends IntegrationSchemaPropertyHandler
{

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $languages = $this->getConfiguration()->getLanguages();
        foreach($this->getData() as $item)
        {
            if(!isset($content[$item[$this->getDiIdField()]]))
            {
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_CATEGORIES] = [];
            }

            if(is_null($item[DocSchemaInterface::FIELD_INTERNAL_ID]))
            {
                continue;
            }

            /** @var CategorySchema $schema */
            $schema =  $this->getCategoryAttributeSchema(
                explode(",", $item[DocSchemaInterface::FIELD_INTERNAL_ID]),
                $languages
            );

            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_CATEGORIES][] = $schema;
        }

        return $content;
    }

    /**
     * Get leaf category IDs
     * There is no difference between languages for each product
     *
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function getQuery(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select([
                "LOWER(HEX(product_id)) AS {$this->getDiIdField()}",
                "GROUP_CONCAT(LOWER(HEX(category_id)) SEPARATOR ',') AS " . DocSchemaInterface::FIELD_INTERNAL_ID
            ]
        )->from("product_category")
            ->andWhere('product_category.category_version_id = :categoryLiveVersion')
            ->andWhere('product_category.product_version_id = :productLiveVersion')
            #->andWhere('product_category.product_id IN (:ids)')
            ->addGroupBy('product_id')
            #->setParameter('ids', Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY)
            ->setParameter('productLiveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('categoryLiveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

}
