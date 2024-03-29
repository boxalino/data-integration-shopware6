<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product;

use Boxalino\DataIntegrationDoc\Generator\Product\Group;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocProduct;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocProductHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocDeltaIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocDeltaIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationTrait;

/**
 * Class DocHandler
 *
 * 1. Handles the attributes definitions (what property under what schema is exported) : addSchemaDefinition
 * 2. Declares attribute handlers (what property and how is exported)
 * 3. Declares the export logic (product_line / product_groups / skus level elements)
 * 4. Creates the doc_product https://boxalino.atlassian.net/wiki/spaces/BPKB/pages/252149870/doc+product
 *
 * Aspects to consider:
 * 1. every sales channel has 1 Boxalino index
 * 2. every sales channel has 1 root navigation category ID
 *  - based on the navigation category ID it can be identified to which channel/data index the product ID belongs to
 * 3. all Boxalino accounts are updated with the products from the channel linked to the account
 *
 * @package Boxalino\DataIntegration\Service\Document\Product
 */
class DocHandler extends DocProduct
    implements DocProductHandlerInterface, DocDeltaIntegrationInterface, DocInstantIntegrationInterface, IntegrationDocHandlerInterface
{

    use IntegrationDocHandlerTrait;
    use DocDeltaIntegrationTrait;
    use DocInstantIntegrationTrait;

    /**
     * The products are exported at the level of the product_groups
     * Children (skus) are being generated if:
     * 1. there are variants
     * 2. there are no variants
     *
     * For instant update use - the schema will be reduced stringly to the properties that require
     * to be updated instantly
     */
    protected function createDocLines() : self
    {
        $this->addSystemConfigurationOnHandlers();
        $this->generateDocData();

        $productGroups = $this->getDocProductGroups();

        $this->logTime("start" . __FUNCTION__);

        foreach($productGroups as $productGroup)
        {
            $document = $this->getDocSchemaGenerator();

            $productLine = $this->getSchemaGeneratorByType(DocProductHandlerInterface::DOC_PRODUCT_LEVEL_LINE);
            $productLine->addProductGroup($productGroup);

            $document->setProductLine($productLine)->setCreationTm(date("Y-m-d H:i:s"));
            $this->addDocLine($document);
        }

        $this->logTime("end" . __FUNCTION__);
        $this->logMessage(__FUNCTION__, "end" . __FUNCTION__, "start" . __FUNCTION__);

        return $this;
    }

    /**
     * @return array
     */
    protected function getDocProductGroups() : array
    {
        $productGroups = [];
        $productSkus = [];

        $this->logTime("start" . __FUNCTION__);
        foreach($this->getDocData() as $id => $content)
        {
            try{
                if(!isset($content[DocSchemaInterface::DI_DOC_TYPE_FIELD]))
                {
                    if($this->getSystemConfiguration()->isTest())
                    {
                        $this->getLogger()->warning("Boxalino DI: incomplete content for $id: "
                            . json_encode($content) . ". This error usually means the property handlers are missconfigured."
                        );
                    }

                    continue;
                }

                $schema = $this->getSchemaGeneratorByType($content[DocSchemaInterface::DI_DOC_TYPE_FIELD], $content);
                $parentId = $content[DocSchemaInterface::DI_PARENT_ID_FIELD];
                if(is_null($parentId))
                {
                    $sku = $this->docTypePropDiffDuplicate(
                        DocProductHandlerInterface::DOC_PRODUCT_LEVEL_GROUP,
                        DocProductHandlerInterface::DOC_PRODUCT_LEVEL_SKU,
                        $content
                    );

                    $schema->addSkus([$sku]);
                    $productGroups[$id] = $schema;
                    continue;
                }

                if(isset($productGroups[$parentId]))
                {
                    $productGroups[$parentId] = $productGroups[$parentId]->addSkus([$schema]);
                    continue;
                }

                $productSkus[$parentId][] = $schema;

            } catch (\Throwable $exception)
            {
                $this->logger->info($exception->getMessage());
            }
        }

        foreach($productSkus as $parentId => $skus)
        {
            /** @var Group $schema by default - on product update event - the main variant is also exported*/
            $schema = $this->getSchemaGeneratorByType(
                DocProductHandlerInterface::DOC_PRODUCT_LEVEL_GROUP,
                [DocSchemaInterface::FIELD_INTERNAL_ID => $parentId]
            );
            if(isset($productGroups[$parentId]))
            {
                $schema = $productGroups[$parentId];
            }
            $productGroups[$parentId] = $schema->addSkus($skus);
        }

        $this->logTime("end" . __FUNCTION__);
        $this->logMessage(__FUNCTION__, "end" . __FUNCTION__, "start" . __FUNCTION__);

        return $productGroups;
    }


}
