<?php
namespace Boxalino\DataIntegration\Service\Util;

/**
 * Interface DiConfigurationInterface
 *
 * @package Boxalino\DataIntegration\Service
 */
interface DiConfigurationInterface
{

    /** @var string key for the configuration access, Shopware6 way */
    public CONST BOXALINO_CONFIG_KEY = "BoxalinoDataIntegration";

    /**
     * @return array
     * @throws \Psr\Cache\CacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getInstantUpdateConfigurations() : array;

    /**
     * @return array
     * @throws \Psr\Cache\CacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getFullConfigurations() : array;

    /**
     * @return array
     * @throws \Psr\Cache\CacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDeltaConfigurations() : array;

}
