<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute\Value;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Content\Media\DataAbstractionLayer\MediaRepositoryDecorator;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Property
 * Exports the translation of the properties available in the eshop
 *
 * @package Boxalino\DataIntegration\Service\Document\Attribute\Value
 */
class Tag extends ModeIntegrator
{

    use DocAttributeValueTrait;

    /**
     * Structure: [property-name => [$schema, $schema], property-name => [], [..]]
     *
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        foreach($this->getData() as $item)
        {
            $content["tag"][] = $this->initializeSchemaForRow($item);
        }

        return $content;
    }

    /**
     * Get the options translation per property group
     */
    public function _getQuery(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->_getQueryFields())
            ->from("tag");

        return $query;
    }

    public function getQueryInstant(?string $propertyName = null): QueryBuilder
    {
        $mainQuery = $this->_getQuery($propertyName);
        $mainQuery->leftJoin("tag", "product_tag", "product_tag",
            "product_tag.tag_id=tag.id")
            ->leftJoin("product_tag", "product", "product",
                "product_tag.product_id=product.id AND product_tag.product_version_id=product.version_id")
            ->andWhere('product.version_id = :live')
            ->andWhere("JSON_SEARCH(product.category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            ->andWhere("product_tag.product_id IN (:ids)")
            ->addGroupBy("tag.id")
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION))
            ->setParameter("ids", Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY);

        return $mainQuery;
    }

    /**
     * @return array
     */
    protected function _getQueryFields() : array
    {
        return array_merge(
            ["LOWER(HEX(tag.id)) AS {$this->getDiIdField()}"],
            preg_filter('/^/', 'name AS ', $this->getSystemConfiguration()->getLanguages())
        );
    }



}
