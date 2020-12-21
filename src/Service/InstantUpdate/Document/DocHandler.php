<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document;

use Boxalino\DataIntegrationDoc\Service\Integration\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandler;

/**
 * Class DocHandler
 *
 * @package Boxalino\DataIntegrationDoc\Service
 */
class DocHandler extends IntegrationHandler
    implements DocPropertiesHandlerInterface, IntegrationHandlerInterface
{

    use DocHandlerTrait;

    /**
     * @return \ArrayIterator
     */
    public function getDocs(): \ArrayIterator
    {
        $this->addPropertiesOnHandlers();
        return parent::getDocs();
    }

}
