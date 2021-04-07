<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\InstantUpdate\Document\Attribute\Values\Category as DocAttributeValues;
use Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\AttributeHandler;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Localized;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaPropertyHandlerInterface;
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
 * @package Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\Attribute
 */
class Category extends AttributeHandler
{

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $propertyName = "category_id";
        foreach($this->getData($propertyName) as $item)
        {
            if(!isset($content[$item[$this->getDiIdField()]]))
            {
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_CATEGORIES] = [];
            }

            $categoryzation = new CategorySchema();
            foreach($this->getSystemConfiguration()->getLanguages() as $language)
            {
                foreach(explode(",", $item[$propertyName]) as $categoryId)
                {
                    $localizedCategory = new Localized();
                    $localizedCategory->setValue($categoryId)->setLanguage($language);
                    $categoryzation->addCategoryId($localizedCategory);
                }
            }

            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_CATEGORIES][] = $categoryzation;
        }

        return $content;
    }

    /**
     * Get leaf category IDs
     * There is no difference between languages for each product
     *
     * @param string | null $propertyName
     * @return QueryBuilder
     */
    public function getQuery(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select([
                "LOWER(HEX(product_id)) AS {$this->getDiIdField()}",
                "GROUP_CONCAT(LOWER(HEX(category_id)) SEPARATOR ',') AS {$propertyName}"
                ]
            )->from("product_category")
            ->andWhere('product_category.category_version_id = :categoryLiveVersion')
            ->andWhere('product_category.product_version_id = :productLiveVersion')
            ->andWhere('product_category.product_id IN (:ids)')
            ->addGroupBy('product_id')
            ->setParameter('ids', Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY)
            ->setParameter('productLiveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('categoryLiveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

}
