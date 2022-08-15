<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\User;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Doc\User;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocUserHandlerInterface;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocUser;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocDeltaIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocDeltaIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationTrait;

/**
 * Class DocHandler
 * Generates the content for the doc_user document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252182638/doc_user
 *
 * @package Boxalino\DataIntegration\Service\Document\Order
 */
class DocHandler extends DocUser
    implements DocUserHandlerInterface, IntegrationDocHandlerInterface, DocDeltaIntegrationInterface, DocInstantIntegrationInterface
{

    use IntegrationDocHandlerTrait;
    use DocDeltaIntegrationTrait;
    use DocInstantIntegrationTrait;

    /**
     * @return \Boxalino\DataIntegration\Service\Document\Order\DocHandler
     */
    protected function createDocLines() : self
    {
        try {
            $this->addSystemConfigurationOnHandlers();
            $this->generateDocData();

            foreach($this->getDocData() as $id=>$content)
            {
                /** @var User | DocHandlerInterface $doc */
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
