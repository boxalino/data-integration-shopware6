<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Language;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Doc\Language;
use Boxalino\DataIntegrationDoc\Generator\DocGeneratorInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocLanguagesHandlerInterface;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocLanguages;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationTrait;

/**
 * Class DocHandler
 * Generates the content for the doc_languages document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252280975/doc+languages
 *
 * @package Boxalino\DataIntegration\Service\Document\Language
 */
class DocHandler extends DocLanguages
    implements DocLanguagesHandlerInterface, IntegrationDocHandlerInterface, DocInstantIntegrationInterface
{

    use IntegrationDocHandlerTrait;
    use DocInstantIntegrationTrait;

    public function integrate(): void
    {
        if($this->getSystemConfiguration()->isTest())
        {
            $this->getLogger()->info("Boxalino DI: sync for {$this->getDocType()}");
        }

        $this->createDocLines();
        parent::integrate();
    }

    /**
     * @return $this
     */
    protected function createDocLines() : self
    {
        foreach($this->getSystemConfiguration()->getLanguagesCountryCodeMap() as $language=>$countryCode)
        {
            /** @var Language | DocHandlerInterface $doc */
            $doc = $this->getDocSchemaGenerator();
            $doc->setLanguage($language)->setCountryCode($countryCode)->setCreationTm(date("Y-m-d H:i:s"));

            $this->addDocLine($doc);
        }

        return $this;
    }


}
