<?php
namespace FireGento\FastSimpleImport\Model\Enterprise;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * Factory which creates Classes from Enterprise
 * Class ClassFactory
 * @package FireGento\FastSimpleImport\Model\Enterprise
 */
class VersionFeaturesFactory
{

    const EDITION_ENTERPRISE = 'Enterprise';
    const EDITION_COMMUNITY = 'Community';
    const EDITION_B2B = 'B2B';
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * VersionFeaturesFactory constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ProductMetadataInterface $productMetadata
    )
    {
        $this->_objectManager = $objectManager;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @param string $featureName
     * @return mixed|null
     */
    public function create($featureName)
    {
        $features = $this->getFeatures();
        $feature = $features[$featureName];

        if ( ($this->productMetadata->getEdition() != self::EDITION_B2B && ! version_compare($this->productMetadata->getVersion(), $feature['minVersion'],'>=')) ||
            ($this->productMetadata->getEdition() == self::EDITION_B2B && ! version_compare($this->productMetadata->getVersion(), $feature['minB2bVersion'], '>='))) {
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
                "minB2bVersion" => "1.0.0-rc",
                "minEdition" => self::EDITION_ENTERPRISE,
                "className" => 'FireGento\FastSimpleImport\Model\Enterprise\CategoryImportVersion'
            )
        );
    }
}