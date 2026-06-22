<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegration\Service\Util\Document\StringLocalized;
use Boxalino\DataIntegration\Service\Util\ShopwareLocalizedTrait;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\Schema\Repeated;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class DeliveryTime
 * Exporter for the delivery times
 * The Shopware6 property is exported as localized string attribute
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class DeliveryTime extends ModeIntegrator
{

    use ShopwareLocalizedTrait;
    use DeltaInstantAddTrait;

    public function __construct(
        Connection $connection,
        StringLocalized $localizedStringBuilder
    ){
        $this->localizedStringBuilder = $localizedStringBuilder;
        parent::__construct($connection);
    }

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $languages = $this->getSystemConfiguration()->getLanguages();
        $iterator = $this->getQueryIterator($this->getStatementQuery());

        foreach ($iterator->getIterator() as $item)
        {
            if(!isset($content[$item[$this->getDiIdField()]]))
            {
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_STRING_LOCALIZED] = [];
            }

            if(is_null($item[DocSchemaInterface::FIELD_INTERNAL_ID]))
            {
                continue;
            }

            /** @var Repeated $schema */
            $schema = $this->getRepeatedLocalizedSchema($item, $languages,"delivery_time");
            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_STRING_LOCALIZED][] = $schema->toArray();
        }

        return $content;
    }

    /**
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function _getQuery(?string $propertyName = null): QueryBuilder
    {
        $fields = array_merge(
            $this->getFields(),
            ["LOWER(HEX($this->prefix.delivery_time_id)) AS " . DocSchemaInterface::FIELD_INTERNAL_ID]
        );
        $query = $this->connection->createQueryBuilder();
        $query->select($fields)
            ->from('product')
            ->leftJoin('product','( ' . $this->getLocalizedFieldsQuery()->__toString() . ') ',
                $this->prefix, "$this->prefix.delivery_time_id = product.delivery_time_id"
            )
            ->leftJoin('product', 'product_visibility', 'pv', 'product.id = pv.product_id AND pv.sales_channel_id = :channelId')
            ->andWhere('product.version_id = :live')
            ->andWhere("JSON_SEARCH(product.category_tree, 'one', :channelRootCategoryId) IS NOT NULL OR pv.product_id IS NOT NULL")
            ->addGroupBy('product.id')
            ->orderBy("product.product_number", "DESC")
            ->addOrderBy("product.created_at", "DESC")
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION))
            ->setFirstResult($this->getFirstResultByBatch())
            ->setMaxResults($this->getSystemConfiguration()->getBatchSize());

        return $query;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Exception
     */
    public function getLocalizedFieldsQuery() : QueryBuilder
    {
        return $this->localizedStringBuilder->getLocalizedFields('delivery_time_translation',
            'delivery_time_id', 'delivery_time_id','delivery_time_id',
            'name', ['delivery_time_translation.delivery_time_id'],
            $this->getSystemConfiguration()->getLanguagesMap(), $this->getSystemConfiguration()->getDefaultLanguageId()
        );
    }

}
