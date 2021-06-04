<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegration\Service\Util\ShopwarePropertyTrait;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\Schema\Localized;
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
 * Exports the translation of the attributes
 *
 * @package Boxalino\DataIntegration\Service\Document\Attribute\Value
 */
class Property extends IntegrationSchemaPropertyHandler
{

    use ShopwarePropertyTrait;

    /**
     * @var null | \ArrayObject
     */
    protected $translations = null;

    /**
     * @param Connection $connection
     * @param StringLocalized $localizedStringBuilder
     * @param LoggerInterface $boxalinoLogger
     */
    public function __construct(
        Connection $connection,
        StringLocalized $localizedStringBuilder,
        LoggerInterface $boxalinoLogger
    ){
        $this->logger = $boxalinoLogger;
        $this->localizedStringBuilder = $localizedStringBuilder;
        $this->context = Context::createDefaultContext();
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
        foreach($this->getPropertyNames() as $property)
        {
            $content[$property['name']][DocSchemaInterface::FIELD_NAME] = $property['name'];
            $content[$property['name']][DocSchemaInterface::FIELD_INTERNAL_ID] = $property[$this->getDiIdField()];
            $content[$property['name']][DocSchemaInterface::FIELD_LOCALIZED] = true;
            $content[$property['name']][DocSchemaInterface::FIELD_MULTI_VALUE] = true;
            $content[$property['name']][DocSchemaInterface::FIELD_FILTER_BY] = $property['filterable']==="1";
            $content[$property['name']][DocSchemaInterface::FIELD_LABEL] = $this->getTranslationById($property[$this->getDiIdField()]);
        }

        return $content;
    }

    /**
     * Get the property group translation
     */
    public function getQuery(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getFields("property_group.id"))
            ->from("property_group")
            ->leftJoin('property_group', '( ' . $this->getLocalizedFieldsQuery()->__toString() . ') ',
                $this->prefix, "$this->prefix.property_group_id = property_group.id")
            ->andWhere($this->getLanguageHeaderConditional())
            ->addGroupBy('property_group.id');

        return $query;
    }

    /**
     * @return QueryBuilder
     */
    public function getLocalizedFieldsQuery() : QueryBuilder
    {
        return $this->localizedStringBuilder->getLocalizedFields('property_group_translation',
            'property_group_id', 'property_group_id','property_group_id',
            'name', ['property_group_translation.property_group_id'],
            $this->getSystemConfiguration()->getLanguagesMap(), $this->getSystemConfiguration()->getDefaultLanguageId()
        );
    }

    /**
     * @param string $id
     */
    protected function getTranslationById(string $id) : array
    {
        if(is_null($this->translations))
        {
            $this->translations = new \ArrayObject();
            foreach($this->getData() as $row)
            {
                $this->translations->offsetSet($row[$this->getDiIdField()], $row);
            }
        }

        $schema = [];

        if($this->translations->offsetExists($id))
        {
            $content = $this->translations->offsetGet($id);
            foreach($this->getSystemConfiguration()->getLanguages() as $language)
            {
                $localized = new Localized();
                $localized->setValue($content[$language])->setLanguage($language);
                $schema[] = $localized;
            }
        }

        return $schema;
    }


}
