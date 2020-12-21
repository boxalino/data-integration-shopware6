<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate;

/**
 * Interface UpdateOnSaveIndexerInterface
 * InstantUpdate Boxalino SOLR handler interface
 *
 * @package Boxalini\Exporter\Service\InstantUpdate
 */
interface UpdateOnSaveHandlerInterface
{

    /**
     * @param array $ids
     */
    public function handle(array $ids) : void;

}
