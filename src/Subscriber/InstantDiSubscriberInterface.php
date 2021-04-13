<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Interface InstantDiSubscriberInterface
 * Instant Update Boxalino SOLR handler interface
 *
 * @package Boxalini\DataIntegration\Subscriber
 */
interface InstantDiSubscriberInterface extends EventSubscriberInterface
{

    /**
     * @param array $ids
     */
    public function handle(array $ids) : void;

}
