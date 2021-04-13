<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegration\Service\Util\ShopwareLocalizedTrait;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\Schema\Typed\StringAttribute;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Tag
 * Exporter for the tags
 * The Shopware6 property is exported as string attribute
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Tag extends ModeIntegrator
{

    use ShopwareLocalizedTrait;
    use DeltaInstantAddTrait;

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        foreach ($this->getData() as $item)
        {
            if(!isset($content[$item[$this->getDiIdField()]]))
            {
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_STRING] = [];
            }

            if(is_null($item[DocSchemaInterface::FIELD_INTERNAL_ID]))
            {
                continue;
            }

            /** @var StringAttribute $schema */
            $schema = $this->getStringAttributeSchema(explode(",", $item[DocSchemaInterface::FIELD_INTERNAL_ID]), "tag");
            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_STRING][] = $schema;
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
        $query->select(["LOWER(HEX(product.id)) AS {$this->getDiIdField()}", "GROUP_CONCAT(tag.name) AS " . DocSchemaInterface::FIELD_INTERNAL_ID])
            ->from('product')
            ->leftJoin('product',"product_tag","product_tag",
                "product_tag.product_id=product.id AND product_tag.product_version_id=product.version_id")
            ->leftJoin("product_tag","tag", "tag", "product_tag.tag_id=tag.id")
            ->andWhere('product.version_id = :live')
            ->andWhere("JSON_SEARCH(product.category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            ->addGroupBy('product.id')
            ->orderBy("product.created_at", "DESC")
            ->addOrderBy("product.auto_increment", "DESC")
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION))
            ->setFirstResult($this->getFirstResultByBatch())
            ->setMaxResults($this->getSystemConfiguration()->getBatchSize());

        return $query;
    }

}
