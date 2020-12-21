<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document\Attribute\Values;

use Boxalino\DataIntegration\Service\InstantUpdate\Document\DocPropertiesHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\AttributeValue;
use Boxalino\DataIntegrationDoc\Service\Integration\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\DocAttributeValuesHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\DocAttributeValues;
use Boxalino\DataIntegration\Service\InstantUpdate\Document\DocHandlerTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\DocProduct\AttributeHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class DocHandler
 * Generator for the doc_attribute_values document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252313624/doc+attribute+values
 *
 * @package Boxalino\DataIntegration\Service\InstantUpdate\Document\Attribute\Values
 */
class DocHandler extends DocAttributeValues
    implements DocAttributeValuesHandlerInterface, DocPropertiesHandlerInterface
{

    use DocHandlerTrait;

    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * @return string
     */
    public function getDoc(): string
    {
        $this->createDocLines();
        return parent::getDoc();
    }

    /**
     * @return $this
     */
    protected function createDocLines() : self
    {
        $this->addPropertiesOnHandlers();
        $data = [];
        try {
            foreach($this->attributeHandlerList as $handler)
            {
                if($handler instanceof AttributeHandlerInterface)
                {
                    /** @var Array: [property-name => [$schema, $schema], property-name => [], [..]] $data */
                    $data = $handler->getValues();
                    foreach($data as $propertyName => $content)
                    {
                        foreach($content as $schema)
                        {
                            /** @var AttributeValue | DocHandlerInterface $doc */
                            $doc = $this->getDocPropertySchema(DocAttributeValuesHandlerInterface::DOC_TYPE, $schema);
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
