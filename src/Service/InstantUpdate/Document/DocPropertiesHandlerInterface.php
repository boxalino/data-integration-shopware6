<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document;

use Boxalino\DataIntegration\Service\InstantUpdate\Util\InstantUpdateConfigurationElement;

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
     * @return InstantUpdateConfigurationElement
     */
    public function getConfiguration() : InstantUpdateConfigurationElement;

    /**
     * @param InstantUpdateConfigurationElement $configuration
     * @return DocPropertiesHandlerInterface
     */
    public function setConfiguration(InstantUpdateConfigurationElement $configuration) : DocPropertiesHandlerInterface;

}
