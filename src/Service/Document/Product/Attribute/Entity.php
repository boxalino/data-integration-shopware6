<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Localized;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\TypedLocalized;
use Boxalino\DataIntegrationDoc\Service\Doc\DocPropertiesTrait;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocProductHandlerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class Entity
 * Access the product_groups & sku informatio from the product table
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Entity extends IntegrationSchemaPropertyHandler
{

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        foreach ($this->getData() as $item)
        {
            $content[$item[$this->getDiIdField()]] = [];
            foreach($item as $propertyName => $value)
            {
                if($propertyName == $this->getDiIdField())
                {
                    continue;
                }

                if($this->handlerHasProperty($propertyName))
                {
                    $docAttributeName = $this->properties[$propertyName];
                    if(in_array($docAttributeName, $this->getBooleanSchemaTypes()))
                    {
                        $content[$item[$this->getDiIdField()]][$docAttributeName] = (bool)$value;
                        continue;
                    }

                    if(in_array($docAttributeName, $this->getSingleValueSchemaTypes()))
                    {
                        $content[$item[$this->getDiIdField()]][$docAttributeName] = $value;
                        continue;
                    }

                    if(in_array($docAttributeName, $this->getMultivalueSchemaTypes()))
                    {
                        if(!isset($content[$item[$this->getDiIdField()]][$docAttributeName]))
                        {
                            $content[$item[$this->getDiIdField()]][$docAttributeName] = [];
                        }

                        if(in_array($docAttributeName, $this->getLocalizedSchemaProperties()))
                        {
                            foreach($this->getConfiguration()->getLanguages() as $language)
                            {
                                $localized = new Localized();
                                $localized->setLanguage($language)->setValue($value);
                                $content[$item[$this->getDiIdField()]][$docAttributeName][] = $localized;
                            }

                            continue;
                        }

                        if(in_array($docAttributeName, $this->getTypedSchemaProperties()))
                        {
                            $typedProperty = $this->getAttributeSchema($docAttributeName);
                            if($typedProperty)
                            {
                                $typedProperty->setName($propertyName)
                                    ->addValue($value);

                                $content[$item[$this->getDiIdField()]][$docAttributeName][] = $typedProperty;
                            }

                            continue;
                        }

                        if(in_array($docAttributeName, $this->getTypedLocalizedSchemaProperties()))
                        {
                            /** @var TypedLocalized $typedProperty */
                            $typedProperty = $this->getAttributeSchema($docAttributeName);
                            if($typedProperty)
                            {
                                $typedProperty->setName($propertyName);
                                foreach($this->getConfiguration()->getLanguages() as $language)
                                {
                                    $localized = new Localized();
                                    $localized->setLanguage($language)->setValue($value);
                                    $typedProperty->addValue($localized);
                                }

                                $content[$item[$this->getDiIdField()]][$docAttributeName][] = $typedProperty;
                            }

                            continue;
                        }

                        #default
                        $propertyType = $this->getAttributeSchema($docAttributeName);
                        if(method_exists($propertyType, "setValue"))
                        {
                            $propertyType->setValue($value);
                            $content[$item[$this->getDiIdField()]][$docAttributeName][] = $propertyType;

                            continue;
                        }
                    }
                }

                $content[$item[$this->getDiIdField()]][$propertyName] = $value;
            }
        }

        return $content;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQuery(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getFields())
            ->from('product')
            ->leftJoin("product", 'product', 'parent',
                'product.parent_id = parent.id AND product.parent_version_id = parent.version_id')
            ->leftJoin('product', 'tax', 'tax', 'tax.id = product.tax_id')
            ->andWhere('product.version_id = :live')
            #->andWhere("product.id IN (:ids)")
            # connect to the product IDs that belong to the channel linked to the Boxalino account/data index
            ->andWhere("JSON_SEARCH(product.category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            ->orderBy("product.created_at", "DESC")
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getConfiguration()->getNavigationCategoryId(), ParameterType::STRING);
            #->setParameter('ids', Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY);

        return $query;
    }

    /**
     * Generic properties to be updated on the product entity
     *
     * @return string[]
     */
    public function getFields() : array
    {
        return [
            /** process-required properties (for mapping) */
            "IF(product.parent_id IS NULL, '" . DocProductHandlerInterface::DOC_PRODUCT_LEVEL_GROUP . "', '" .  DocProductHandlerInterface::DOC_PRODUCT_LEVEL_SKU . "') AS " . DocSchemaInterface::DI_DOC_TYPE_FIELD,
            "LOWER(HEX(product.parent_id)) AS " . DocSchemaInterface::DI_PARENT_ID_FIELD,
            "LOWER(HEX(product.id)) AS " . DocSchemaInterface::DI_ID_FIELD,

            /** entity-specific properties */
            "LOWER(HEX(product.id)) AS id", // . DocSchemaInterface::FIELD_INTERNAL_ID,
            "product.product_number AS product_number", // . DocSchemaInterface::FIELD_SKU,
            "product.created_at AS created_at",// . DocSchemaInterface::FIELD_CREATION,
            "product.updated_at AS updated_at",// . DocSchemaInterface::FIELD_UPDATE,
            "IF(product.active IS NULL, parent.active, product.active) AS active",// . DocSchemaInterface::FIELD_STATUS,
            "IF(product.ean IS NULL, parent.ean, product.ean) AS ean", // . DocSchemaInterface::FIELD_EAN,
            "IF(product.is_closeout IS NULL, IF(parent.is_closeout = '1', 0, 1), IF(product.is_closeout = '1', 0, 1)) AS is_closeout", // . DocSchemaInterface::FIELD_SHOW_OUT_OF_STOCK, #bool

            /** numeric properties */
            'IF(product.parent_id IS NULL, product.rating_average, parent.rating_average) AS rating_average',
            'product.restock_time AS restock_time',
            'product.sales AS sales',
            'product.child_count AS child_count',
            'IF(product.purchase_price IS NULL, parent.purchase_price, product.purchase_price) AS purchase_price',
            'IF(product.min_purchase IS NULL, parent.min_purchase, product.min_purchase) AS min_purchase',
            'tax.tax_rate AS tax_rate',

            /** string properties */
            'product.auto_increment AS auto_increment',
            'IF(product.shipping_free IS NULL, parent.shipping_free, product.shipping_free) AS shipping_free',
            'IF(product.is_closeout IS NULL, parent.is_closeout, product.is_closeout) AS is_closeout',
            'IF(product.available IS NULL, parent.available, product.available) AS available',
            'IF(product.mark_as_topseller IS NULL, parent.mark_as_topseller, product.mark_as_topseller) AS mark_as_topseller',
            'IF(product.main_variant_id IS NULL, LOWER(HEX(parent.main_variant_id)), LOWER(HEX(product.main_variant_id))) AS main_variant_id',
            'IF(product.manufacturer_number IS NULL, LOWER(HEX(parent.manufacturer_number)), LOWER(HEX(product.manufacturer_number))) AS manufacturer_number',
            'IF(product.delivery_time_id IS NULL, LOWER(HEX(parent.delivery_time_id)), LOWER(HEX(product.delivery_time_id))) AS delivery_time_id',
            'LOWER(HEX(product.parent_id)) AS parent_id',
            'product.category_tree AS category_tree',
            'product.option_ids AS option_ids',
            'product.property_ids AS property_ids',
            'product.display_group AS display_group',

            /** datetime attributes */
            'IF(product.release_date IS NULL, parent.release_date, product.release_date) AS release_date',
        ];
    }


}
