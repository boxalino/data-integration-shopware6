<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute\Value;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Doc\AttributeValue;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocAttributeValuesHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocAttributeValues;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaPropertyHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationTrait;

/**
 * Class DocHandler
 * Generator for the doc_attribute_value document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252313624/doc+attribute+values
 *
 * The doc_attribute_value is exported fully for FULL and DELTA data integrations
 * The doc_attribute_value is exported partially for INSTANT data integrations
 *
 * @package Boxalino\DataIntegration\Service\Document\Attribute\Value
 */
class DocHandler extends DocAttributeValues
    implements DocAttributeValuesHandlerInterface, IntegrationDocHandlerInterface, DocInstantIntegrationInterface
{

    use IntegrationDocHandlerTrait;
    use DocInstantIntegrationTrait;

    /**
     * Integrate document
     */
    public function integrate() : void
    {
        if($this->getSystemConfiguration()->isTest())
        {
            $this->getLogger()->info("Boxalino DI: load for {$this->getDocType()}");
        }

        $this->createDocLines();
        parent::integrate();
    }

    /**
     * @return $this
     */
    protected function createDocLines() : self
    {
        $data = [];
        $this->addSystemConfigurationOnHandlers();
        try {
            foreach($this->getHandlers() as $handler)
            {
                if($handler instanceof DocSchemaPropertyHandlerInterface)
                {
                    $this->_createDocLinesByHandler($handler);
                }
            }
        } catch (\Throwable $exception)
        {
            $this->logger->info($exception->getMessage());
        }

        return $this;
    }

    /**
     * @param DocSchemaPropertyHandlerInterface $handler
     */
    protected function _createDocLinesByHandler(DocSchemaPropertyHandlerInterface $handler) : void
    {
        /** @var Array: [property-name => [$schema, $schema], property-name => [], [..]] $data */
        $data = $handler->getValues();
        foreach($data as $propertyName => $content)
        {
            foreach($content as $schema)
            {
                /** @var AttributeValue | DocSchemaPropertyHandlerInterface $doc */
                $doc = $this->getDocSchemaGenerator($schema);
                $doc->setAttributeName($propertyName)->setCreationTm(date("Y-m-d H:i:s"));

                $this->addDocLine($doc);
            }
        }
    }

}
