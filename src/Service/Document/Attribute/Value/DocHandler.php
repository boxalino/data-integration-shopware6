<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute\Value;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\AttributeValue;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocAttributeValuesHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocAttributeValues;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaPropertyHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class DocHandler
 * Generator for the doc_attribute_value document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252313624/doc+attribute+values
 *
 * @package Boxalino\DataIntegration\Service\Document\Attribute\Value
 */
class DocHandler extends DocAttributeValues
    implements DocAttributeValuesHandlerInterface, IntegrationDocHandlerInterface
{

    use IntegrationDocHandlerTrait;

    /**
     * @return string
     */
    public function getDocContent(): string
    {
        if(empty($this->docs))
        {
            $this->addSystemConfigurationOnHandlers();
            $this->createDocLines();

        }
        return parent::getDocContent();
    }

    /**
     * @return $this
     */
    protected function createDocLines() : self
    {
        $data = [];
        try {
            foreach($this->getHandlers() as $handler)
            {
                if($handler instanceof DocSchemaPropertyHandlerInterface)
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
        } catch (\Throwable $exception)
        {
            $this->logger->info($exception->getMessage());
        }

        return $this;
    }


}
