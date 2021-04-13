<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Console\Mode;

/**
 * Class InstantTrait
 *
 * @package Boxalino\DataIntegration\Service
 */
trait InstantTrait
{

    /**
     * Access configurations for the instant update process
     *
     * @return array
     * @throws \Psr\Cache\CacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getConfigurations(): array
    {
        return $this->getConfigurationManager()->getInstantUpdateConfigurations();
    }


}
