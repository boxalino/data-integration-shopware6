<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\UserSelection;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocUserSelection;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocUserSelectionHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocDeltaIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocDeltaIntegrationTrait;

/**
 * Class DocHandler
 * Generates the content for the doc_user_selection document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252313673/doc+user+selection
 *
 * @package Boxalino\DataIntegration\Service\Document\UserSelection
 */
class DocHandler extends DocUserSelection
    implements DocUserSelectionHandlerInterface, IntegrationDocHandlerInterface, DocDeltaIntegrationInterface
{

    use IntegrationDocHandlerTrait;
    use DocDeltaIntegrationTrait;

    protected function createDocLines(): self
    {
        try {
            $this->addSystemConfigurationOnHandlers();
            $this->generateDocData();

            foreach ($this->getDocData() as $id => $content) {
                /** @var DocHandlerInterface $doc */
                $doc = $this->getDocSchemaGenerator($content);
                $doc->setCreationTm(date("Y-m-d H:i:s"));

                $this->addDocLine($doc);
            }
        } catch (\Throwable $exception) {
            $this->logger->info($exception->getMessage());
        }

        return $this;
    }

}
