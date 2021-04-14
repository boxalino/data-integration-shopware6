<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Order;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\Schema\Localized;
use Boxalino\DataIntegrationDoc\Service\GcpRequestInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class Entity
 * Access the order main information from the order table
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
                    if(in_array($docAttributeName, $this->getOrderBooleanSchemaTypes()))
                    {
                        $content[$item[$this->getDiIdField()]][$docAttributeName] = (bool)$value;
                        continue;
                    }

                    if(in_array($docAttributeName, $this->getOrderSingleValueSchemaTypes()))
                    {
                        $content[$item[$this->getDiIdField()]][$docAttributeName] = $value;
                        continue;
                    }

                    if(in_array($docAttributeName, $this->getOrderNumericSchemaTypes()))
                    {
                        $content[$item[$this->getDiIdField()]][$docAttributeName] = (float)$value;
                        continue;
                    }

                    if(in_array($docAttributeName, $this->getOrderMultivalueSchemaTypes()))
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
     * @return QueryBuilder
     * @throws \Doctrine\DBAL\DBALException
     */
    public function _getQuery() : QueryBuilder
    {
        $properties = $this->getFields();
        $orderStateId = $this->getStateId();
        $defaultLanguageId = $this->getSystemConfiguration()->getDefaultLanguageId();

        $query = $this->connection->createQueryBuilder();
        $query->select($properties)
            ->from("`order`", "o")
            ->leftJoin(
                "o", 'state_machine_state', 'smso', "smso.id = o.state_id AND smso.state_machine_id = :orderStateMachineId"
            )
            ->leftJoin(
                "o", 'sales_channel_translation', 'sct', 'sct.sales_channel_id=o.sales_channel_id AND sct.language_id=:defaultLanguageId'
            )
            ->leftJoin(
                "o", 'currency', 'c', "o.currency_id = c.id"
            )
            ->leftJoin(
                "o", 'language', 'language', 'language.id = o.language_id'
            )
            ->leftJoin(
                'language', 'locale', 'locale', 'locale.id = language.locale_id'
            )
            ->leftJoin(
                "o", 'order_customer', 'oc', 'oc.order_id=o.id AND oc.order_version_id=o.version_id'
            )
            ->leftJoin(
                "o", 'order_delivery', 'od', 'o.id = od.order_id AND o.version_id=od.order_version_id'
            )
            ->leftJoin(
                'od', 'shipping_method_translation', 'smt', 'smt.shipping_method_id=od.shipping_method_id AND smt.language_id = :defaultLanguageId'
            )
            ->leftJoin(
                "o", 'order_transaction', 'ot', 'o.id = ot.order_id AND o.version_id=ot.order_version_id'
            )
            ->leftJoin(
                'ot', 'payment_method_translation', 'pmt', "pmt.payment_method_id=ot.payment_method_id AND pmt.language_id = :defaultLanguageId"
            )
            ->andWhere("o.sales_channel_id=:channelId")
            ->andWhere("o.version_id = :live")
            ->addOrderBy("o.order_date_time", 'DESC')
            ->groupBy("o.id")
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('defaultLanguageId', Uuid::fromHexToBytes($defaultLanguageId), ParameterType::BINARY)
            ->setParameter('orderStateMachineId', $orderStateId, ParameterType::BINARY)
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
            'LOWER(HEX(o.id)) AS ' . $this->getDiIdField(),
            'LOWER(HEX(o.id)) AS id', 'o.order_number', 'sct.name AS store',
            'c.iso_code AS currency_cd', 'o.currency_factor',  'o.amount_total', 'o.amount_net',
            'o.tax_status', 'o.shipping_total', 'IF(o.tax_status="net",1,0) AS tax_free',
            'TRUNCATE(o.amount_total - o.amount_net, 2) AS tax_amnt', 'locale.code as language',
            'GROUP_CONCAT(od.tracking_codes) AS tracking_code', 'pmt.name AS payment_method',
            'smt.name AS shipping_method','smt.description AS shipping_description', 'od.shipping_date_latest AS sent',
            'oc.email', 'LOWER(HEX(oc.customer_id)) AS persona_id', 'o.customer_comment AS customer_comment',
            'IF(o.updated_at IS NULL, o.created_at, o.updated_at) AS updated_at',
            'o.order_date_time', 'IF(smso.technical_name="completed",1, 0) AS status', 'smso.technical_name AS status_code',
            'o.auto_increment', 'o.campaign_code', 'o.affiliate_code', 'o.deep_link_code'
        ];
    }

    /**
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getStateId() : string
    {
        return $this->connection->fetchColumn("SELECT id FROM state_machine WHERE technical_name='order.state'");
    }


}
