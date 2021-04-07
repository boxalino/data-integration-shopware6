<?php
namespace Boxalino\DataIntegration\Service\Util;

use Boxalino\DataIntegration\Service\Util\Document\StringLocalized;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Localized;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Repeated;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\RepeatedLocalized;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Typed\StringAttribute;

/**
 * Trait for storing common logic for localized content access
 * Dependent on the IntegrationDocHandlerTrait
 *
 * @package Boxalino\DataIntegration\Service\Util
 */
trait ShopwareLocalizedTrait
{
    /**
     * @var StringLocalized
     */
    protected $localizedStringBuilder;

    /**
     * @var string
     */
    protected $prefix = "translation";

    /**
     * @return array
     * @throws \Exception
     */
    public function getFields(string $diIdFieldMap = "product.id") : array
    {
        return array_merge($this->getLanguageHeaderColumns(),["LOWER(HEX($diIdFieldMap)) AS {$this->getDiIdField()}"]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getLanguageHeaderConditional() : string
    {
        $conditional = [];
        foreach ($this->getLanguageHeaderColumns() as $column)
        {
            $conditional[]= "$column IS NOT NULL ";
        }

        return implode(" OR " , $conditional);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getLanguageHeaderColumns() : array
    {
        return preg_filter('/^/', $this->getPrefix() .'.', $this->getSystemConfiguration()->getLanguages());
    }

    /**
     * @return string
     */
    public function getPrefix() : string
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }


}
