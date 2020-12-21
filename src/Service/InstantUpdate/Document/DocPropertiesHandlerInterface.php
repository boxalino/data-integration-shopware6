<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document;

use Boxalino\DataIntegration\Service\Util\ConfigurationDataObject;

/**
 * Interface DocPropertiesHandlerInterface
 * @package Boxalino\DataIntegration\Service\InstantUpdate\Document
 */
interface DocPropertiesHandlerInterface
{

    /**
     * @return arrau
     */
    public function getIds() : array;

    /**
     * @param array $ids
     * @return DocPropertiesHandlerInterface
     */
    public function setIds(array $ids) : DocPropertiesHandlerInterface;

    /**
     * @return ConfigurationDataObject
     */
    public function getConfiguration() : ConfigurationDataObject;

    /**
     * @param ConfigurationDataObject $configuration
     * @return DocPropertiesHandlerInterface
     */
    public function setConfiguration(ConfigurationDataObject $configuration) : DocPropertiesHandlerInterface;

}
