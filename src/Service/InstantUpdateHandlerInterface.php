<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service;

/**
 * Interface InstantUpdateHandlerInterface
 * InstantUpdate Boxalino SOLR handler interface
 *
 * @package Boxalini\DataIntegration\Service
 */
interface InstantUpdateHandlerInterface
{

    /**
     * @param array $ids
     */
    public function handle(array $ids) : void;

}
