<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegrationDoc\Doc\Schema\Localized;
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
            ->andWhere('product.version_id = :live')
            # connect to the product IDs that belong to the channel linked to the Boxalino account/data index
            ->andWhere("JSON_SEARCH(product.category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            ->orderBy("product.created_at", "DESC")
            ->addOrderBy("product.auto_increment", "DESC")
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setFirstResult($this->getFirstResultByBatch())
            ->setMaxResults($this->getSystemConfiguration()->getBatchSize());

        return $query;
    }

    /**
     * If the logic for delta needs to be updated - rewrite this function
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQueryDelta(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->_getQuery($propertyName);

        $dateCriteria = $this->getSyncCheck() ?? date("Y-m-d H:i", strtotime("-60 min"));
        $query->andWhere(
            "STR_TO_DATE(product.updated_at, '%Y-%m-%d %H:%i') > :lastSync OR STR_TO_DATE(parent.updated_at, '%Y-%m-%d %H:%i') > :lastSync " .
            "OR STR_TO_DATE(product.created_at, '%Y-%m-%d %H:%i') > :lastSync OR STR_TO_DATE(parent.created_at,  '%Y-%m-%d %H:%i') > :lastSync"
        )
            ->setParameter('lastSync', $dateCriteria);

        return $query;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQueryInstant(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->_getQuery($propertyName);
        return $this->addInstantCondition($query);
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
