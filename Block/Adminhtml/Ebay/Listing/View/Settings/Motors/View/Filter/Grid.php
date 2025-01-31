<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Filter;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private $listingProductId;
    private $listingProduct;
    private $motorsType;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors */
    private $componentEbayMotors;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Motors $componentEbayMotors,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->componentEbayMotors = $componentEbayMotors;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayMotorViewFilterGrid');

        // Set default values
        //------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
        $this->setUseAjax(true);
        $this->setPagerVisibility(false);
        $this->setDefaultLimit(false);
        //------------------------------
    }

    //------------------------------

    protected function _prepareCollection()
    {
        $motorsHelper = $this->componentEbayMotors;

        $attributeValue = $this->getListingProduct()->getMagentoProduct()->getAttributeValue(
            $motorsHelper->getAttribute($this->getMotorsType())
        );

        $motorsData = $motorsHelper->parseAttributeValue($attributeValue);

        $collection = $this->activeRecordFactory->getObject('Ebay_Motor_Filter')->getCollection();
        $collection->getSelect()->where('id IN (?)', $motorsData['filters']);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('title', [
            'header'       => $this->__('Title'),
            'align'        => 'left',
            'type'         => 'text',
            'index'        => 'title',
            'filter_index' => 'title',
            'escape'       => true,
            'frame_callback' => [$this, 'callbackColumnTitle']
        ]);

        $this->addColumn('note', [
            'header'       => $this->__('Note'),
            'align'        => 'left',
            'type'         => 'text',
            'index'        => 'note',
            'filter_index' => 'note'
        ]);
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setUseSelectAll(false);
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // Set mass-action
        //--------------------------------
        $this->getMassactionBlock()->addItem('removeFilter', [
            'label'   => $this->__('Remove'),
            'url'     => ''
        ]);
        //--------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        return $value;
    }

    //########################################

    protected function _toHtml()
    {
        if (!$this->canDisplayContainer()) {
            $this->js->add(<<<JS
    EbayListingViewSettingsMotorsViewFilterGridObj.afterInitPage();
JS
            );

            return parent::_toHtml();
        }

        $this->js->add(<<<JS
    require([
        'M2ePro/Ebay/Listing/View/Settings/Motors/View/Filter/Grid'
    ], function() {
        EbayListingViewSettingsMotorsViewFilterGridObj = new EbayListingViewSettingsMotorsViewFilterGrid(
            '{$this->getId()}',
            '{$this->getListingProductId()}'
        );
        EbayListingViewSettingsMotorsViewFilterGridObj.afterInitPage();
    });
JS
        );

        return parent::_toHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_settings_motors/viewFilterGrid', [
            '_current' => true
        ]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    public function setMotorsType($motorsType)
    {
        $this->motorsType = $motorsType;
    }

    public function getMotorsType()
    {
        if ($this->motorsType === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Motors type not set.');
        }

        return $this->motorsType;
    }

    //########################################

    public function getItemsColumnTitle()
    {
        if ($this->componentEbayMotors->isTypeBasedOnEpids($this->getMotorsType())) {
            return $this->__('ePID(s)');
        }

        return $this->__('kType(s)');
    }

    //########################################

    /**
     * @return null
     */
    public function getListingProductId()
    {
        return $this->listingProductId;
    }

    /**
     * @param null $listingProductId
     */
    public function setListingProductId($listingProductId)
    {
        $this->listingProductId = $listingProductId;
    }

    //########################################

    public function getListingProduct()
    {
        if ($this->listingProduct === null) {
            $this->listingProduct = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'Listing\Product',
                $this->getListingProductId()
            );
        }

        return $this->listingProduct;
    }

    //########################################
}
