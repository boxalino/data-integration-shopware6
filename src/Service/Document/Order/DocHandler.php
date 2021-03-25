<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Order;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\Order;
use Boxalino\DataIntegrationDoc\Service\Generator\DocGeneratorInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocOrderHandlerInterface;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocOrder;

/**
 * Class DocHandler
 * Generates the content for the doc_order document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252313666/doc+order
 *
 * For a full order history export - the
 *
 * @package Boxalino\DataIntegration\Service\Document\Order
 */
class DocHandler extends DocOrder
    implements DocOrderHandlerInterface, IntegrationDocHandlerInterface
{

    use IntegrationDocHandlerTrait;

    /**
     * @return string
     */
    public function getDoc(): string
    {
        /** @var Order | DocHandlerInterface $doc */
        $doc = $this->getDocPropertySchema(DocOrderHandlerInterface::DOC_TYPE);
        $this->addDocLine($doc);
        return parent::getDoc();
    }


}
