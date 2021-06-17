<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\User;

use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\User;
use Boxalino\DataIntegrationDoc\Service\GcpRequestInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;
use Boxalino\DataIntegrationDoc\Doc\Schema\User\Contact as UserContactSchema;

/**
 * Class Contact
 * Export order contact information following the documented schema
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/254050518/Referenced+Schema+Types#CONTACT
 *
 * @package Boxalino\DataIntegration\Service\Document\Order
 */
abstract class Contact extends ModeIntegrator
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
            if(!isset($content[$item[$this->getDiIdField()]]))
            {
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_CONTACTS] = [];
            }

            try{
                $schema = new UserContactSchema($item);
                if(isset($item["updated_at"]))
                {
                    $schema->addDatetimeAttributes($this->getDatetimeAttributeSchema([$item["updated_at"]], "updated_at"));
                }
                if(isset($item["created_at"]))
                {
                    $schema->addDatetimeAttributes($this->getDatetimeAttributeSchema([$item["created_at"]], "created_at"));
                }

                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_CONTACTS][] = $schema;
            } catch (\Throwable $exception)
            {
                //missing information
            }
        }

        return $content;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function _getQuery() : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getFields())
            ->from("customer")
            ->leftJoin(
                "customer", "customer_address", 'customer_address', $this->getAddressJoinCondition()
            )
            ->leftJoin(
                "customer_address", 'salutation_translation', 'st', "customer_address.salutation_id = st.salutation_id AND st.language_id=:defaultLanguageId"
            )
            ->leftJoin(
                'customer_address', 'country', 'cb', 'customer_address.country_id = cb.id'
            )
            ->leftJoin(
                'customer_address', 'country_state_translation', 'cstb', 'customer_address.country_state_id = cstb.country_state_id AND cstb.language_id=:defaultLanguageId'
            )
            ->andWhere("customer.sales_channel_id=:channelId")
            ->addOrderBy("customer.created_at", 'DESC')
            ->groupBy("customer.id")
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('defaultLanguageId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getDefaultLanguageId()), ParameterType::BINARY)
            ->setFirstResult($this->getFirstResultByBatch())
            ->setMaxResults($this->getSystemConfiguration()->getBatchSize());

        return $query;
    }

    /**
     * @return array
     */
    protected function getFields() : array
    {
        $type = $this->getContactType();
        return [
            "LOWER(HEX(customer.id)) AS ". $this->getDiIdField(),
            "'$type' AS type",
            "customer_address.title AS title",
            "st.display_name AS salutation_id",
            "customer_address.first_name AS firstname",
            "customer_address.last_name AS lastname",
            "customer_address.company AS company",
            "customer_address.vat_id AS vat",
            "customer_address.street AS street",
            "CONCAT(customer_address.additional_address_line1, ' ', customer_address.additional_address_line2) AS additional_address_line",
            "customer_address.city AS city",
            "customer_address.zipcode AS zipcode",
            "customer_address.department AS department",
            "cstb.name AS statename",
            "cb.iso AS countryiso",
            "customer_address.phone_number AS phone",
            "customer_address.updated_at AS updated_at"
        ];
    }

    /**
     * @return string
     */
    abstract public function getAddressJoinCondition() : string;

    /**
     * @return string
     */
    abstract public function getContactType() : string;



}
