<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage\Tabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\Manage\Tabs\Variations
 */
class Variations extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var  \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingProductVariationManage');
        $this->_controller = 'adminhtml_amazon_listing_product_variation_manage_tabs_variations';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('add');
        // ---------------------------------------
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @return $this
     */
    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;

        return $this;
    }
    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        return $this->listingProduct;
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->getChildBlock('grid')->setListingProduct($this->getListingProduct());

        return parent::_beforeToHtml();
    }

    //########################################

    protected function _toHtml()
    {
        return '<div id="listing_product_variation_progress_bar"></div>' .
        '<div id="listing_product_variation_action_messages_container"></div>' .
        '<div id="listing_product_variation_errors_summary_container" class="errors_summary" style="display: none;">
        </div>' .
        '<div id="listing_product_variation_content_container">' .
        parent::_toHtml() .
        '</div>';
    }

    //########################################

    public function getGridHtml()
    {
        $this->js->add(
            <<<JS
    require([
        'M2ePro/Amazon/Listing/Product/Variation/Manage/Tabs/Variations/Grid'
    ], function(){

        ListingProductVariationManageVariationsGridObj = new AmazonListingProductVariationManageTabsVariationsGrid(
            '{$this->getChildBlock('grid')->getId()}',
            {$this->getListingProduct()->getListingId()}
        );

        ListingProductVariationManageVariationsGridObj.actionHandler
            .setProgressBar('listing_product_variation_progress_bar');
        ListingProductVariationManageVariationsGridObj.actionHandler
            .setGridWrapper('listing_product_variation_content_container');
        ListingProductVariationManageVariationsGridObj.actionHandler
            .setErrorsSummaryContainer('listing_product_variation_errors_summary_container');
        ListingProductVariationManageVariationsGridObj.actionHandler
            .setActionMessagesContainer('listing_product_variation_action_messages_container');
    });
JS
        );

        return parent::getGridHtml();
    }

    protected function getSettingsButtonDropDownItems()
    {
        $items = [];

        $backUrl = $this->dataHelper->makeBackUrlParam('*/amazon_listing/view', [
            'id' => $this->getListingProduct()->getListingId()
        ]);

        // ---------------------------------------
        $url = $this->getUrl('*/amazon_listing/edit', [
            'id' => $this->getListingProduct()->getListingId(),
            'back' => $backUrl,
            'tab' => 'selling'
        ]);
        $items[] = [
            'label' => $this->__('Selling'),
            'onclick' => 'window.open(\'' . $url . '\',\'_blank\');',
            'default' => true
        ];
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/amazon_listing/edit', [
            'id' => $this->getListingProduct()->getListingId(),
            'back' => $backUrl,
            'tab' => 'search'
        ]);
        $items[] = [
            'label' => $this->__('Search'),
            'onclick' => 'window.open(\'' . $url . '\',\'_blank\');'
        ];
        // ---------------------------------------

        // ---------------------------------------
        $items[] = [
            'onclick' => 'ListingAutoActionObj.loadAutoActionHtml();',
            'label' => $this->__('Auto Add/Remove Rules')
        ];
        // ---------------------------------------

        return $items;
    }

    //########################################

    public function getAddProductsDropDownItems()
    {
        $items = [];

        $backUrl = $this->dataHelper->makeBackUrlParam('*/amazon_listing/view', [
            'id' => $this->getListingProduct()->getListingId()
        ]);

        // ---------------------------------------
        $url = $this->getUrl('*/amazon_listing_product_add/index', [
            'id' => $this->getListingProduct()->getListingId(),
            'back' => $backUrl,
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'clear' => 1,
            'step' => 2,
            'source' => \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode::MODE_PRODUCT
        ]);
        $items[] = [
            'label' => $this->__('From Products List'),
            'onclick' => "setLocation('" . $url . "')",
            'default' => true
        ];
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/amazon_listing_product_add/index', [
            'id' => $this->getListingProduct()->getListingId(),
            'back' => $backUrl,
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'clear' => 1,
            'step' => 2,
            'source' => \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode::MODE_CATEGORY
        ]);
        $items[] = [
            'label' => $this->__('From Categories'),
            'onclick' => "setLocation('" . $url . "')"
        ];
        // ---------------------------------------

        return $items;
    }

    //########################################
}
