<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\AttributeHandler;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaPropertyHandlerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Visibility as VisibilitySchema;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Localized;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;

/**
 * Class Visibility
 *
 * @package Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\Attribute
 */
class Visibility extends AttributeHandler
{

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        foreach ($this->getData(DocSchemaInterface::FIELD_VISIBILITY) as $item)
        {
            $schema = new VisibilitySchema();
            foreach($this->getSystemConfiguration()->getLanguages() as $language)
            {
                $localized = new Localized();
                $localized->setLanguage($language)->setValue($item[DocSchemaInterface::FIELD_VISIBILITY]);
                $schema->addValue($localized);
            }
            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_VISIBILITY] = [$schema];
        }

        return $content;
    }

    /**
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function getQuery(string $propertyName): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(["LOWER(HEX(product.id)) AS {$this->getDiIdField()}", "product_visibility.visibility AS {$propertyName}"])
            ->from("product")
            ->leftJoin("product", 'product_visibility', 'product_visibility',
                'product.id = product_visibility.product_id AND product.version_id = product_visibility.product_version_id')
            ->andWhere('product_visibility.product_version_id = :live')
            ->andWhere('product_visibility.sales_channel_id = :channel')
            ->andWhere('product.id IN (:ids)')
            ->addGroupBy('product.id')
            ->setParameter('ids', Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY)
            ->setParameter("channel", Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

}
