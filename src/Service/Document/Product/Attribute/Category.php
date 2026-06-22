<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Boxalino\DataIntegrationDoc\Doc\Schema\Category as CategorySchema;

/**
 * Class Category
 * Category is the only hierarchical property in Shopware6
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Category extends ModeIntegrator
{

    use DeltaInstantTrait;

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $languages = $this->getSystemConfiguration()->getLanguages();
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

            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_CATEGORIES][] = $schema->toArray();
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
    public function _getQuery(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->_getQueryFields())
            ->from("product_category")
            ->leftJoin("product_category", "( " . $this->_getProductQuery()->__toString() . " )", 'product',
                'product_category.product_id = product.id AND product_category.product_version_id = product.version_id')
            ->andWhere('product_category.category_version_id = :categoryLiveVersion')
            ->andWhere('product_category.product_version_id = :productLiveVersion')
            ->andWhere("product.id IS NOT NULL")
            ->addGroupBy('product_category.product_id')
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('productLiveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('categoryLiveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING);

        return $query;
    }

    /**
     * @return string[]
     */
    protected function _getQueryFields() : array
    {
        return [
            "LOWER(HEX(product_id)) AS {$this->getDiIdField()}",
            "GROUP_CONCAT(LOWER(HEX(category_id)) SEPARATOR ',') AS " . DocSchemaInterface::FIELD_INTERNAL_ID
        ];
    }


}
