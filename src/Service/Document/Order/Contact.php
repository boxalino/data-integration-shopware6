<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Order;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Order\Contact as OrderContactSchema;

/**
 * Class Contact
 * Export order contact information following the documented schema
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/254050518/Referenced+Schema+Types#CONTACT
 *
 * @package Boxalino\DataIntegration\Service\Document\Order
 */
abstract class Contact extends IntegrationSchemaPropertyHandler
{

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        foreach($this->getData() as $item)
        {
            if(!isset($content[$item[$this->getDiIdField()]]))
            {
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_CONTACTS] = [];
            }

            $schema = new OrderContactSchema($item);
            if(isset($item["updated_at"]))
            {
                $schema->addDatetimeAttributes($this->getDatetimeAttributeSchema([$item["updated_at"]], "updated_at"));
            }

            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_CONTACTS][] = $schema;
        }

        return $content;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQuery(?string $propertyName = null) : QueryBuilder
    {
        $stateMachineId = $this->getStateId();
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getFields())
            ->from("`order`", "o")
            ->leftJoin(
                "o", $this->getSourceTable(), 'src', "src.order_id = o.id AND src.order_version_id = o.version_id AND src.version_id=:live"
            )
            ->leftJoin(
                "src", 'state_machine_state', 'sms', "sms.id=src.state_id AND sms.state_machine_id = :stateMachineId"
            )
            ->leftJoin(
                "o", 'order_customer', 'oc', "oc.order_id = o.id AND oc.order_version_id = o.version_id AND oc.version_id=:live"
            )
            ->leftJoin(
                "oc", 'customer', 'c', "oc.customer_id = c.id AND oc.customer_number=c.customer_number"
            )
            ->leftJoin(
                "c", 'customer_group_translation', 'cgt', "c.customer_group_id = cgt.customer_group_id AND cgt.language_id=:defaultLanguageId"
            )
            ->leftJoin(
                "c", 'salutation_translation', 'st', "c.salutation_id = st.salutation_id AND st.language_id=:defaultLanguageId"
            )
            ->leftJoin(
                $this->getAddressJoinSrc(), 'order_address', 'oa', $this->getAddressCondition()
            )
            ->leftJoin(
                'oa', 'country', 'cb', 'oa.country_id = cb.id'
            )
            ->leftJoin(
                'oa', 'country_state_translation', 'cstb', 'oa.country_state_id = cstb.country_state_id AND cstb.language_id=:defaultLanguageId'
            )
            //->andWhere("o.sales_channel_id=:channelId")
            ->andWhere("o.version_id = :live")
            ->andWhere("src.version_id = :live")
            ->groupBy("o.id")
            //->setParameter('channelId', Uuid::fromHexToBytes($this->getConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('stateMachineId', $stateMachineId, ParameterType::BINARY)
            ->setParameter('defaultLanguageId', Uuid::fromHexToBytes($this->getConfiguration()->getDefaultLanguageId()), ParameterType::BINARY)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);
//                ->setFirstResult(($page - 1) * OrderComponentInterface::EXPORTER_STEP)
//                ->setMaxResults(OrderComponentInterface::EXPORTER_STEP);

        return $query;
    }

    /**
     * @return array
     */
    protected function getFields() : array
    {
        $type = $this->getContactType();
        $field = $this->getTypeStatusSchemaField();
        return [
            "LOWER(HEX(o.id)) AS ". $this->getDiIdField(),
            "'$type' AS type",
            "LOWER(HEX(oc.customer_id)) AS persona_id",
            "LOWER(HEX(oc.customer_id)) AS internal_id",
            "oc.customer_number AS external_id",
            "oa.title AS title",
            "st.display_name AS salutation_id",
            "oa.first_name AS firstname",
            "oa.last_name AS lastname",
            "c.birthday AS date_of_birth",
            "c.created_at AS account_creation",
            "cgt.name AS customer_groups",
            "sms.technical_name AS $field",
            "oa.company AS company",
            "oa.vat_id AS vat",
            "oa.street AS street",
            "CONCAT(oa.additional_address_line1, ' ', oa.additional_address_line2) AS additional_address_line",
            "oa.city AS city",
            "oa.zipcode AS zipcode",
            "oa.department AS department",
            "cstb.name AS statename",
            "cb.iso AS countryiso",
            "oa.phone_number AS phone",
            "oc.email AS email",
            "src.updated_at AS updated_at"
        ];
    }

    /**
     * @return false|mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    abstract public function getStateId() : string;

    /**
     * @return string
     */
    abstract public function getSourceTable() : string;

    /**
     * @return string
     */
    abstract public function getAddressCondition() : string;

    /**
     * @return string
     */
    abstract public function getAddressJoinSrc() : string;

    /**
     * @return string
     */
    abstract public function getContactType() : string;

    /**
     * @return string
     */
    abstract public function getTypeStatusSchemaField() : string;


}
