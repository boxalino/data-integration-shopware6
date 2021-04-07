<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Visibility as VisibilitySchema;

/**
 * Class Visibility
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Visibility extends IntegrationSchemaPropertyHandler
{
    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $languages = $this->getSystemConfiguration()->getLanguages();
        foreach ($this->getData() as $item)
        {
            /** @var VisibilitySchema $schema */
            $schema = $this->getVisibilitySchema($languages, $item[DocSchemaInterface::FIELD_INTERNAL_ID]);
            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_VISIBILITY][] = $schema;
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
        $query->select(["LOWER(HEX(product.id)) AS {$this->getDiIdField()}", "product_visibility.visibility AS " . DocSchemaInterface::FIELD_INTERNAL_ID])
            ->from("product")
            ->leftJoin("product", 'product_visibility', 'product_visibility',
                'product.id = product_visibility.product_id AND product.version_id = product_visibility.product_version_id')
            ->andWhere('product_visibility.product_version_id = :live')
            ->andWhere('product_visibility.sales_channel_id = :channel')
            ->andWhere("JSON_SEARCH(product.category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            #->andWhere('product.id IN (:ids)')
            ->addGroupBy('product.id')
            #->setParameter('ids', Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY)
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter("channel", Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

}
