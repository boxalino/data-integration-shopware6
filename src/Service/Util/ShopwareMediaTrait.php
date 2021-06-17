<?php
namespace Boxalino\DataIntegration\Service\Util;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\Exception\EmptyMediaFilenameException;
use Shopware\Core\Content\Media\Exception\EmptyMediaIdException;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Trait for accessing Shopware Media content
 *
 * @package Boxalino\DataIntegration\Service\Util
 */
trait ShopwareMediaTrait
{
    /**
     * @var UrlGeneratorInterface
     */
    protected $mediaUrlGenerator;

    /**
     * @var EntityRepositoryInterface
     */
    protected $mediaRepository;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param string|null $mediaId
     * @return string|null
     */
    public function getImageByMediaId(?string $mediaId) : string
    {
        $image = "";
        try{
            /** @var MediaEntity $media */
            $media = $this->mediaRepository->search(new Criteria([$mediaId]), $this->context)->get($mediaId);
            $image = $this->mediaUrlGenerator->getAbsoluteMediaUrl($media);
        } catch(EmptyMediaFilenameException $exception)
        {
            if($this->getSystemConfiguration()->isTest())
            {
                $this->logger->info("Shopware: Media Path Export not available for media ID $mediaId: " . $exception->getMessage());
            }
        } catch(EmptyMediaIdException $exception)
        {
            if($this->getSystemConfiguration()->isTest())
            {
                $this->logger->info("Shopware: Media Path Export not available for media ID $mediaId: " . $exception->getMessage());
            }
        } catch(\Exception $exception)
        {
            if($this->getSystemConfiguration()->isTest())
            {
                $this->logger->info("Shopware: Media Path Export not available for media ID $mediaId: " . $exception->getMessage());
            }
        }

        return $image;
    }


}
