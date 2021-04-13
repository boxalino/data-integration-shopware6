<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute\Value;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Util\ShopwareLocalizedTrait;
use Boxalino\DataIntegrationDoc\Doc\Schema\Localized;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\Schema\RepeatedGenericLocalized;
use Boxalino\DataIntegrationDoc\Doc\Schema\RepeatedLocalized;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Trait DocAttributeValueTrait
 * requires ShopwareLocalizedTrait and IntegrationDocHandlerTrait
 *
 * @package Boxalino\DataIntegration\Service\Document
 */
trait DocAttributeValueTrait
{

    /**
     * @var array | null
     */
    protected $localizedPropertyValues = null;

    /**
     * @param string $row
     * @return array
     * @throws \Exception
     */
    public function initializeSchemaForRow(array $row) : array
    {
        $schema = [];
        $schema[DocSchemaInterface::FIELD_NUMERICAL] = false;
        $schema[DocSchemaInterface::FIELD_VALUE_ID] = $row[$this->getDiIdField()];

        $schema = $this->addingPropertyToSchema(DocSchemaInterface::FIELD_VALUE_LABEL, $schema, $row);

        if(isset($row[DocSchemaInterface::FIELD_IMAGES]))
        {
            // adding media
            $schema[DocSchemaInterface::FIELD_IMAGES][] = $this->getImage($row);
        }

        if(isset($row[DocSchemaInterface::FIELD_STATUS]))
        {
            // adding status
            $schema = $this->addingPropertyToSchema(DocSchemaInterface::FIELD_STATUS, $schema, $row[DocSchemaInterface::FIELD_STATUS]);
        }

        if(isset($row[DocSchemaInterface::FIELD_LINK]))
        {
            // adding link
            $schema = $this->addingPropertyToSchema(DocSchemaInterface::FIELD_LINK, $schema, $row[DocSchemaInterface::FIELD_LINK]);
        }

        return $schema;
    }

    /**
     * @param string $property
     * @param array $schema
     * @param array | string | null $source
     * @return array
     */
    public function addingPropertyToSchema(string $property, array $schema, $source = null) : array
    {
        foreach($this->getSystemConfiguration()->getLanguages() as $language)
        {
            $content = null;
            if(is_array($source) && isset($source[$language])){ $content = $source[$language]; }
            if(isset($schema[$language]) && !isset($source[$language])){ $content = $schema[$language]; }
            if(is_null($content)){  continue; }

            $localized = new Localized();
            $localized->setValue($content)->setLanguage($language);
            $schema[$property][] = $localized;
        }

        return $schema;
    }

    /**
     * @param array $item
     * @return RepeatedGenericLocalized
     */
    public function getImage(array $item) : RepeatedGenericLocalized
    {
        $images = new RepeatedGenericLocalized();
        $schema = new RepeatedLocalized();
        if($item[DocSchemaInterface::FIELD_IMAGES])
        {
            $value = $this->getImageByMediaId($item[DocSchemaInterface::FIELD_IMAGES]);
            foreach($this->getSystemConfiguration()->getLanguages() as $language)
            {
                $localized = new Localized();
                $localized->setLanguage($language)->setValue($value);
                $schema->addValue($localized);
            }
            $schema->setValueId($item[DocSchemaInterface::FIELD_IMAGES]);
        }
        $images->addValue($schema);

        return $images;
    }

    /**
     * Identify localized content by the brand id
     *
     * @param string $id
     * @param string $propertyName
     * @return array
     * @throws \Exception
     */
    public function getLocalizedPropertyById(string $id, string $propertyName) : ?array
    {
        $localizedValues = $this->getLocalizedPropertyValues($propertyName);
        foreach($localizedValues as $row)
        {
            if($row[$this->getDiIdField()] === $id)
            {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getLocalizedPropertyValues(string $propertyName) : array
    {
        if(is_null($this->localizedPropertyValues))
        {
            $this->localizedPropertyValues = new \ArrayObject();
        }

        if($this->localizedPropertyValues->offsetExists($propertyName))
        {
            return $this->localizedPropertyValues->offsetGet($propertyName);
        }

        $this->localizedPropertyValues->offsetSet($propertyName, $this->getLocalizedQueryResults($propertyName));
        return $this->localizedPropertyValues->offsetGet($propertyName);
    }

}
