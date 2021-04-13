<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Order;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Doc\Order;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailSyncException;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocOrderHandlerInterface;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocOrder;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocDeltaIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocDeltaIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Mode\InstantIntegrationInterface;

/**
 * Class DocHandler
 * Generates the content for the doc_order document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252313666/doc+order
 *
 * @package Boxalino\DataIntegration\Service\Document\Order
 */
class DocHandler extends DocOrder
    implements DocOrderHandlerInterface, IntegrationDocHandlerInterface, DocDeltaIntegrationInterface, DocInstantIntegrationInterface
{

    use IntegrationDocHandlerTrait;
    use DocDeltaIntegrationTrait;
    use DocInstantIntegrationTrait;

    /**
     * @return $this
     */
    protected function createDocLines() : self
    {
        try {
            $this->addSystemConfigurationOnHandlers();
            $this->generateDocData();

            foreach($this->getDocData() as $id=>$content)
            {
                /** @var Order | DocHandlerInterface $doc */
                $doc = $this->getDocSchemaGenerator($content);
                $doc->setCreationTm(date("Y-m-d H:i:s"));

                $this->addDocLine($doc);
            }
        } catch (\Throwable $exception)
        {
            $this->logger->info($exception->getMessage());
        }

        return $this;
    }


}
