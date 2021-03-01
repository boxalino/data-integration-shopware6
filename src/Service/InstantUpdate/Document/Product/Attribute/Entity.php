<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Component\ProductComponentInterface;
use Boxalino\DataIntegration\Service\InstantUpdate\Document\DocHandlerTrait;
use Boxalino\DataIntegration\Service\InstantUpdate\Document\DocPropertiesHandlerInterface;
use Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\AttributeHandler;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Localized;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\TypedLocalized;
use Boxalino\DataIntegrationDoc\Service\DocPropertiesTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\DocProduct\Attribute;
use Boxalino\DataIntegrationDoc\Service\Integration\DocProduct\AttributeHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\DocProductHandlerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class Entity
 * Access the product_groups & sku informatio from the product table
 *
 * @package Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\Attribute
 */
class Entity extends AttributeHandler
{

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        foreach ($this->getData() as $item)
        {
            $content[$item[$this->getInstantUpdateIdField()]] = [];
            foreach($item as $propertyName => $value)
            {
                if($propertyName == $this->getInstantUpdateIdField())
                {
                    continue;
                }

                if($this->handlerHasProperty($propertyName))
                {
                    $docAttributeName = $this->properties[$propertyName];
                    if(in_array($docAttributeName, $this->getBooleanSchemaTypes()))
                    {
                        $content[$item[$this->getInstantUpdateIdField()]][$docAttributeName] = (bool)$value;
                        continue;
                    }

                    if(in_array($docAttributeName, $this->getSingleValueSchemaTypes()))
                    {
                        $content[$item[$this->getInstantUpdateIdField()]][$docAttributeName] = $value;
                        continue;
                    }

                    if(in_array($docAttributeName, $this->getMultivalueSchemaTypes()))
                    {
                        if(!isset($content[$item[$this->getInstantUpdateIdField()]][$docAttributeName]))
                        {
                            $content[$item[$this->getInstantUpdateIdField()]][$docAttributeName] = [];
                        }

                        if(in_array($docAttributeName, $this->getLocalizedSchemaProperties()))
                        {
                            foreach($this->getConfiguration()->getLanguages() as $language)
                            {
                                $localized = new Localized();
                                $localized->setLanguage($language)->setValue($value);
                                $content[$item[$this->getInstantUpdateIdField()]][$docAttributeName][] = $localized;
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

                                $content[$item[$this->getInstantUpdateIdField()]][$docAttributeName][] = $typedProperty;
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

                                $content[$item[$this->getInstantUpdateIdField()]][$docAttributeName][] = $typedProperty;
                            }

                            continue;
                        }

                        #default
                        $propertyType = $this->getAttributeSchema($docAttributeName);
                        if(method_exists($propertyType, "setValue"))
                        {
                            $propertyType->setValue($value);
                            $content[$item[$this->getInstantUpdateIdField()]][$docAttributeName][] = $propertyType;

                            continue;
                        }
                    }
                }

                $content[$item[$this->getInstantUpdateIdField()]][$propertyName] = $value;
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
        $query->select($this->getProperties())
            ->from('product')
            ->leftJoin("product", 'product', 'parent',
                'product.parent_id = parent.id AND product.parent_version_id = parent.version_id')
            ->andWhere('product.version_id = :live')
            ->andWhere("product.id IN (:ids)")
            # connect to the product IDs that belong to the channel linked to the Boxalino account/data index
            ->andWhere("JSON_SEARCH(product.category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            ->orderBy("product.created_at", "DESC")
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('ids', Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY);

        return $query;
    }

    /**
     * Generic properties to be updated on the product entity
     *
     * @return string[]
     */
    public function getProperties() : array
    {
        return [
            /** process-required properties (for mapping) */
            "IF(product.parent_id IS NULL, '" . DocProductHandlerInterface::DOC_PRODUCT_LEVEL_GROUP . "', '" .  DocProductHandlerInterface::DOC_PRODUCT_LEVEL_SKU . "') AS " . Attribute::DI_DOC_TYPE_FIELD,
            "LOWER(HEX(product.parent_id)) AS " . Attribute::DI_PARENT_ID_FIELD,
            "LOWER(HEX(product.id)) AS " . Attribute::DI_ID_FIELD,
            /** entity-specific properties */
            "LOWER(HEX(product.id)) AS " . AttributeHandlerInterface::ATTRIBUTE_TYPE_INTERNAL_ID,
            "product.product_number AS " . AttributeHandlerInterface::ATTRIBUTE_TYPE_SKU,
            "product.created_at AS " . AttributeHandlerInterface::ATTRIBUTE_TYPE_CREATION,
            "product.updated_at AS " . AttributeHandlerInterface::ATTRIBUTE_TYPE_UPDATE,
            "IF(product.active IS NULL, parent.active, product.active) AS " . AttributeHandlerInterface::ATTRIBUTE_TYPE_STATUS,
            "IF(product.ean IS NULL, parent.ean, product.ean) AS " . AttributeHandlerInterface::ATTRIBUTE_TYPE_EAN,
            "IF(product.is_closeout IS NULL, IF(parent.is_closeout = '1', 0, 1), IF(product.is_closeout = '1', 0, 1)) AS " . AttributeHandlerInterface::ATTRIBUTE_TYPE_SHOW_OUT_OF_STOCK, #bool
            'IF(product.parent_id IS NULL, product.rating_average, parent.rating_average) AS rating_average',                   #numeric
            'IF(product.mark_as_topseller IS NULL, parent.mark_as_topseller, product.mark_as_topseller) AS mark_as_topseller'  #string
        ];
    }


}
