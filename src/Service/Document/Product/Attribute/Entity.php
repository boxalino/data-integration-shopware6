<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegrationDoc\Doc\Schema\Localized;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\NoRecordsFoundException;
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
class Entity extends ModeIntegrator
{

    use EntityFullTrait;
    use EntityInstantTrait;
    use DeltaInstantAddTrait;

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $iterator = $this->getQueryIterator($this->getStatementQuery());

        foreach ($iterator->getIterator() as $item)
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
                    if(in_array($docAttributeName, $this->getProductBooleanSchemaTypes()))
                    {
                        $content[$item[$this->getDiIdField()]][$docAttributeName] = (bool)$value;
                        continue;
                    }

                    if(in_array($docAttributeName, $this->getProductSingleValueSchemaTypes()))
                    {
                        $content[$item[$this->getDiIdField()]][$docAttributeName] = $value;
                        continue;
                    }

                    if(in_array($docAttributeName, $this->getProductMultivalueSchemaTypes()))
                    {
                        if(!isset($content[$item[$this->getDiIdField()]][$docAttributeName]))
                        {
                            $content[$item[$this->getDiIdField()]][$docAttributeName] = [];
                        }

                        if(in_array($docAttributeName, $this->getProductLocalizedSchemaProperties()))
                        {
                            foreach($this->getSystemConfiguration()->getLanguages() as $language)
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
                            $typedProperty = $this->getAttributeSchema($docAttributeName);
                            if($typedProperty)
                            {
                                $typedProperty->setName($propertyName);
                                foreach($this->getSystemConfiguration()->getLanguages() as $language)
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

        if(empty($content))
        {
            throw new NoRecordsFoundException("No records available. This is a logical exception in order to exit the handler loop.");
        }

        return $content;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function _getQuery(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->_getQueryFields())
            ->from('product')
            ->leftJoin("product", 'product', 'parent',
                'product.parent_id = parent.id AND product.parent_version_id = parent.version_id')
            ->leftJoin('product', 'tax', 'tax', 'tax.id = product.tax_id')
            ->leftJoin('product', 'product_visibility', 'pv', 'product.id = pv.product_id AND pv.sales_channel_id = :channelId')
            ->andWhere('product.version_id = :live')
            # connect to the product IDs that belong to the channel linked to the Boxalino account/data index
            ->andWhere("JSON_SEARCH(product.category_tree, 'one', :channelRootCategoryId) IS NOT NULL OR pv.product_id IS NOT NULL")
            ->orderBy("product.product_number", "DESC")
            ->addOrderBy("product.created_at", "DESC")
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setFirstResult($this->getFirstResultByBatch())
            ->setMaxResults($this->getSystemConfiguration()->getBatchSize());

        return $query;
    }

    /**
     * Generic properties to be updated on the product entity
     *
     * @return string[]
     */
    public function _getQueryFields() : array
    {
        if($this->filterByIds())
        {
            return $this->getInstantFields();
        }

        return $this->getFullFields();
    }


}
