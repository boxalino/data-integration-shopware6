<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Language;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\Language;
use Boxalino\DataIntegrationDoc\Service\Generator\DocGeneratorInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocLanguagesHandlerInterface;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocLanguages;

/**
 * Class DocHandler
 * Generates the content for the doc_languages document
 * https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252280975/doc+languages
 *
 * @package Boxalino\DataIntegration\Service\Document\Language
 */
class DocHandler extends DocLanguages
    implements DocLanguagesHandlerInterface, IntegrationDocHandlerInterface
{

    use IntegrationDocHandlerTrait;

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
        foreach($this->getConfiguration()->getLanguagesCountryCodeMap() as $language=>$countryCode)
        {
            /** @var Language | DocHandlerInterface $doc */
            $doc = $this->getDocPropertySchema(DocLanguagesHandlerInterface::DOC_TYPE);
            $doc->setLanguage($language)->setCountryCode($countryCode)->setCreationTm(date("Y-m-d H:i:s"));

            $this->addDocLine($doc);
        }

        return $this;
    }


}
