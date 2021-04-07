<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document\Attribute\Values;

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
 * Generator for the doc_attribute_values document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252313624/doc+attribute+values
 *
 * @package Boxalino\DataIntegration\Service\InstantUpdate\Document\Attribute\Values
 */
class DocHandler extends DocAttributeValues
    implements DocAttributeValuesHandlerInterface, IntegrationDocHandlerInterface
{

    use IntegrationDocHandlerTrait;

    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * @return string
     */
    public function getDocContent(): string
    {
        $this->createDocLines();
        return parent::getDocContent();
    }

    /**
     * @return $this
     */
    protected function createDocLines() : self
    {
        $this->addSystemConfigurationOnHandlers();
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
                            /** @var AttributeValue | DocHandlerInterface $doc */
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
