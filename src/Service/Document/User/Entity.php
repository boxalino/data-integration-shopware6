<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\User;

use Boxalino\DataIntegrationDoc\Doc\Schema\Localized;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class Entity
 * Access the user main information from the customer table
 * The fields exported are added as properties in the order.xml service definition
 *
 * @package Boxalino\DataIntegration\Service\Document\Order
 */
class Entity extends ModeIntegrator
{
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
                    if(in_array($docAttributeName, $this->getUserBooleanSchemaTypes()))
                    {
                        $content[$item[$this->getDiIdField()]][$docAttributeName] = (bool)$value;
                        continue;
                    }

                    if(in_array($docAttributeName, $this->getUserNumericSchemaTypes()))
                    {
                        $content[$item[$this->getDiIdField()]][$docAttributeName] = (float)$value;
                        continue;
                    }

                    if(in_array($docAttributeName, $this->getUserMultivalueSchemaTypes()))
                    {
                        if(!isset($content[$item[$this->getDiIdField()]][$docAttributeName]))
                        {
                            $content[$item[$this->getDiIdField()]][$docAttributeName] = [];
                        }

                        if(in_array($docAttributeName, $this->getTypedSchemaProperties()))
                        {
                            $typedProperty = $this->getAttributeSchema($docAttributeName);
                            if($typedProperty)
                            {
                                $typedProperty->setName($propertyName)
                                    ->addValue($value);

                                $content[$item[$this->getDiIdField()]][$docAttributeName][] = $typedProperty->toArray();
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

                                $content[$item[$this->getDiIdField()]][$docAttributeName][] = $typedProperty->toArray();
                            }

                            continue;
                        }

                        #default
                        $propertyType = $this->getAttributeSchema($docAttributeName);
                        if(method_exists($propertyType, "setValue"))
                        {
                            $propertyType->setValue($value);
                            $content[$item[$this->getDiIdField()]][$docAttributeName][] = $propertyType->toArray();

                            continue;
                        }
                    }

                    /** most of user information is single-value */
                    $content[$item[$this->getDiIdField()]][$docAttributeName] = $value;
                    continue;
                }

                $content[$item[$this->getDiIdField()]][$propertyName] = $value;
            }
        }

        return $content;
    }

    /**
     * @return QueryBuilder
     * @throws \Doctrine\DBAL\DBALException
     */
    public function _getQuery() : QueryBuilder
    {
        $properties = $this->getFields();
        $defaultLanguageId = $this->getSystemConfiguration()->getDefaultLanguageId();

        $query = $this->connection->createQueryBuilder();
        $query->select($properties)
            ->from("customer")
            ->leftJoin(
                "customer", 'sales_channel_translation', 'sct', 'sct.sales_channel_id=customer.sales_channel_id AND sct.language_id=:defaultLanguageId'
            )
            ->leftJoin(
                "customer", 'language', 'language', 'language.id = customer.language_id'
            )
            ->leftJoin(
                'language', 'locale', 'locale', 'locale.id = language.locale_id'
            )
            ->leftJoin(
                "customer", 'customer_group_translation', 'cgt', "customer.customer_group_id = cgt.customer_group_id AND cgt.language_id=:defaultLanguageId"
            )
            ->leftJoin(
                "customer", 'salutation_translation', 'st', "customer.salutation_id = st.salutation_id AND st.language_id=:defaultLanguageId"
            )
            ->andWhere("customer.sales_channel_id=:channelId")
            ->addOrderBy("customer.created_at", 'DESC')
            ->groupBy("customer.id")
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('defaultLanguageId', Uuid::fromHexToBytes($defaultLanguageId), ParameterType::BINARY)
            ->setFirstResult($this->getFirstResultByBatch())
            ->setMaxResults($this->getSystemConfiguration()->getBatchSize());

        return $query;
    }

    /**
     * Fields are mapped to the properties of the doc order in order build the content dynamically
     *
     * @return string[]
     */
    public function getFields() : array
    {
        return [
            'LOWER(HEX(customer.id)) AS ' . $this->getDiIdField(),
            'LOWER(HEX(customer.id)) AS id', 'LOWER(HEX(customer.id)) AS persona_id', "customer.customer_number",
            'sct.name AS store',  "customer.title", "st.display_name AS prefix", "customer.first_name", "customer.last_name",
            'customer.birthday', "customer.created_at", "cgt.name AS auto_group", "customer.active",
            "customer.company", "customer.email", "customer.auto_increment", 'customer.campaign_code',
            'locale.code as language', "customer.newsletter", "customer.guest",
            'IF(customer.updated_at IS NULL, customer.created_at, customer.updated_at) AS updated_at',
            "customer.first_login", "customer.last_login"
        ];
    }


}
