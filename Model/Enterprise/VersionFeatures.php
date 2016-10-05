<?php
namespace FireGento\FastSimpleImport\Model\Enterprise;
/**
 * Provides a wrapper for the Enterprise Edition only features
 * Class VersionFeatures
 * @package FireGento\FastSimpleImport\Model\Enterprise
 */
class VersionFeatures
{

    /**
     * @var VersionManager
     */
    protected $versionManager = null;

    /**
     * @var ReadEntityVersion
     */
    protected $entityVersion = null;

    public function __construct(
        EnterpriseFactory $factory,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    )
    {
        switch ($productMetadata->getEdition()) {
            case 'Community':
                break;
            case 'Enterprise':
                $this->versionManager = $factory->create('Magento\Staging\Model\VersionManager');
                $this->entityVersion = $factory->create('Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion');
                break;

        }

    }

    public function setCurrentVersionId($versionID)
    {
        if ($this->versionManager) {
            $this->versionManager->setCurrentVersionId($versionID);
        }
    }

    public function getPreviousVersionId()
    {
        if ($this->entityVersion) {
            return $this->entityVersion->getPreviousVersionId(
                CategoryInterface::class,
                1
            );
        }


    }

    public function getNextVersionId()
    {
        if ($this->entityVersion) {
            return $this->entityVersion->getNextVersionId(CategoryInterface::class, 1);
        }

    }
}