<?php
namespace Boxalino\DataIntegration\Service\Util;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Util\Document\StringLocalized;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Trait for storing common logic for properties access
 * Requires ShopwareLocalizedTrait
 *
 * @package Boxalino\DataIntegration\Service\Util
 */
trait ShopwarePropertyTrait
{
    use ShopwareLocalizedTrait;
    //use IntegrationDocHandlerTrait;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $propertyId;

    /**
     * @param string $id
     * @return $this
     */
    public function setPropertyId(string $id)
    {
        $this->propertyId = $id;
    }

    /**
     * Get existing facets names&codes
     *
     * @return false|mixed
     */
    public function getPropertyNames() : array
    {
        $fields = ["LOWER(HEX(property_group.id)) AS " . $this->getDiIdField(), "IF(pgtl.name IS NULL, pgt.name, pgtl.name) AS name"];
        $query = $this->connection->createQueryBuilder()
            ->select($fields)
            ->from("property_group")
            ->leftJoin("property_group", "property_group_translation", "pgt","property_group.id = pgt.property_group_id")
            ->leftJoin("property_group", "property_group_translation", "pgtl",
                "property_group.id = pgtl.property_group_id AND pgtl.language_id=:languageId")
            ->groupBy("property_group.id")
            ->setParameter("languageId", Uuid::fromHexToBytes($this->getConfiguration()->getDefaultLanguageId()), ParameterType::STRING);

        return $query->execute()->fetchAll(FetchMode::ASSOCIATIVE);
    }

    /**
     * Accessing property options name translation (name)
     * If there is no translation available, the default one is used
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     * @throws \Exception
     */
    public function getLocalizedFieldsQuery() : QueryBuilder
    {
        return $this->localizedStringBuilder->getLocalizedFields('property_group_option_translation',
            'property_group_option_id', 'property_group_option_id','property_group_option_id',
            'name', ['property_group_option_translation.property_group_option_id'],
            $this->getConfiguration()->getLanguagesMap(), $this->getConfiguration()->getDefaultLanguageId()
        );
    }


}
