<?php
namespace FireGento\FastSimpleImport\Model\Enterprise;
/**
 * Factory which creates Classes from Enterprise
 * Class ClassFactory
 * @package FireGento\FastSimpleImport\Model\Enterprise
 */
class VersionFeaturesFactory
{

    const EDITION_ENTERPRISE = 'Enterprise';
    const EDITION_COMMUNITY = 'Community';
    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager
     */
    protected $_objectManager;
    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $productMetadata;

    public function __construct(
        \Magento\Framework\ObjectManager\ObjectManager $objectManager,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    )
    {
        $this->_objectManager = $objectManager;
        $this->productMetadata = $productMetadata;
    }

    public function create($featureName)
    {
        $features = $this->getFeatures();
        $feature = $features[$featureName];

        if ( ! version_compare($this->productMetadata->getVersion(), $feature['minVersion'],'>=')) {
            return null;
        }
        if ($feature['minEdition'] == self::EDITION_ENTERPRISE && $this->productMetadata->getEdition() == self::EDITION_COMMUNITY) {
            return null;
        }
        return $this->_objectManager->create($feature['className'], array());
    }

    /**
     * Gets an Array of Magento Version Specific Features
     * @return array
     */
    public function getFeatures()
    {
        return array(
            "CategoryImportVersion" => array(
                "minVersion" => "2.1.1",
                "minEdition" => self::EDITION_ENTERPRISE,
                "className" => 'FireGento\FastSimpleImport\Model\Enterprise\CategoryImportVersion'
            )
        );
    }
}