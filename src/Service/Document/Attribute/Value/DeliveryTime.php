<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute\Value;

use Boxalino\DataIntegration\Service\Document\Attribute\Value\DocAttributeValueTrait;
use Boxalino\DataIntegration\Service\Util\ShopwareLocalizedTrait;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Boxalino\DataIntegration\Service\Util\Document\StringLocalized;

/**
 * Class Property
 * Exports the translation of the properties available in the eshop
 *
 * @package Boxalino\DataIntegration\Service\Document\Attribute\Value
 */
class DeliveryTime extends ModeIntegrator
{

    use DocAttributeValueTrait;
    use ShopwareLocalizedTrait;

    /**
     * @param Connection $connection
     * @param StringLocalized $localizedStringBuilder
     */
    public function __construct(
        Connection $connection,
        StringLocalized $localizedStringBuilder
    ){
        $this->localizedStringBuilder = $localizedStringBuilder;
        parent::__construct($connection);
    }

    /**
     * Structure: [property-name => [$schema, $schema], property-name => [], [..]]
     *
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        foreach($this->getData() as $item)
        {
            $content["delivery_time"][] = $this->initializeSchemaForRow($item);
        }

        return $content;
    }

    /**
     * Get the options translation per property group
     */
    public function _getQuery(?string $propertyName = null) : QueryBuilder
    {
        $groupBy = "$this->prefix.delivery_time_id";
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getFields($groupBy))
            ->from('( ' . $this->getLocalizedFieldsQuery()->__toString() . ')', $this->getPrefix())
            ->addGroupBy($groupBy);

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
