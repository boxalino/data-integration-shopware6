<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\Attribute;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocAttribute;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocAttributeHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaPropertyHandlerInterface;

/**
 * Class DocHandler
 * Generator for the doc_attribute document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252280945/doc+attribute
 *
 * @package Boxalino\DataIntegration\Service\Document\Attribute
 */
class DocHandler extends DocAttribute
    implements DocAttributeHandlerInterface, IntegrationDocHandlerInterface
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
            $this->setLanguages($this->getSystemConfiguration()->getLanguages());
            $this->createDocLines();
        }
        return parent::getDocContent();
    }

    /**
     * @return $this
     */
    protected function createDocLines() : self
    {
        try {
            $data = [];
            foreach($this->getHandlers() as $handler)
            {
                if($handler instanceof DocSchemaPropertyHandlerInterface)
                {
                    /** @var Array: [property-name => [$schema, $schema], property-name => [], [..]] $data */
                    foreach($handler->getValues() as $propertyName => $content)
                    {
                        /** @var Attribute | DocHandlerInterface $doc */
                        $doc = $this->getDocSchemaGenerator($content);
                        $doc->setName($propertyName)->setCreationTm(date("Y-m-d H:i:s"));
                        $this->applyPropertyConfigurations($doc);

                        $this->addDocLine($doc);
                    }
                }
            }

            /**
             * other properties outside of product table & properties
             */
            $this->addConfiguredProperties();

        } catch (\Throwable $exception)
        {
            $this->getLogger()->info($exception->getMessage());
        }

        return $this;
    }

}
