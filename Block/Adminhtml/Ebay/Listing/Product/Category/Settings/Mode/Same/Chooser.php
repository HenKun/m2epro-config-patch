<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Same;

use Ess\M2ePro\Block\Adminhtml\Listing\View\Header;

class Chooser extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $_listing;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = [])
    {
        parent::__construct($context, $data);
        $this->dataHelper = $dataHelper;
    }

    public function _construct()
    {
        parent::_construct();

        $this->_headerText = $this->__('eBay Categories');
        $this->_listing =$this->activeRecordFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('id')
        );

        $this->addButton('back', [
            'label'   => $this->__('Back'),
            'class'   => 'back',
            'onclick' => 'setLocation(\'' . $this->getUrl('*/*/*', ['_current' => true, 'step' => 1]) . '\');'
        ]);

        $onClick = <<<JS
EbayListingCategoryObj.modeSameSubmitData(
    '{$this->getUrl('*/*/*', array('step' => 2,'_current' => true))}'
);
JS;
        $this->addButton('next', [
            'label'   => $this->__('Continue'),
            'class'   => 'action-primary forward',
            'onclick' => $onClick
        ]);
    }

    //########################################

    public function getHeaderWidth()
    {
        return 'width:50%;';
    }

    //########################################

    protected function _toHtml()
    {
        $parentHtml = parent::_toHtml();

        // ---------------------------------------
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions(
            'Ebay_Listing_Product_Category_Settings',
            [
                '_current' => true
            ]
        ));

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Ebay_Category', ['_current' => true]));

        $this->jsUrl->add($this->getUrl('*/ebay_listing_product_category_settings', [
            'step' => 3,
            '_current' => true
        ]), 'ebay_listing_product_category_settings');

        $this->jsUrl->add($this->getUrl('*/ebay_listing/review', [
            '_current' => true
        ]), 'ebay_listing/review');
        // ---------------------------------------

        // ---------------------------------------
        $viewHeaderBlock = $this->getLayout()->createBlock(Header::class, '', [
            'data' => ['listing' => $this->_listing]
        ]);
        // ---------------------------------------

        // ---------------------------------------

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser $chooserBlock */
        $chooserBlock = $this->getLayout()
                             ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser::class);
        $chooserBlock->setMarketplaceId($this->_listing->getMarketplaceId());
        $chooserBlock->setAccountId($this->_listing->getAccountId());
        $chooserBlock->setCategoriesData($this->getData('categories_data'));

        // ---------------------------------------

        $this->js->addOnReadyJs(
            <<<JS
require([
    'M2ePro/Ebay/Listing/Category',
    'M2ePro/Ebay/Template/Category/Chooser'
], function(){
    window.EbayListingCategoryObj = new EbayListingCategory(null);

    EbayTemplateCategoryChooserObj.confirmSpecificsCallback = function() {
        var typeMain = M2ePro.php.constant('Ess_M2ePro_Helper_Component_Ebay_Category::TYPE_EBAY_MAIN');
        this.selectedCategories[typeMain].specific = this.selectedSpecifics;
    }.bind(EbayTemplateCategoryChooserObj);

    EbayTemplateCategoryChooserObj.resetSpecificsCallback = function() {
        var typeMain = M2ePro.php.constant('Ess_M2ePro_Helper_Component_Ebay_Category::TYPE_EBAY_MAIN');
        this.selectedCategories[typeMain].specific = this.selectedSpecifics;
    }.bind(EbayTemplateCategoryChooserObj);

})
JS
        );

        return <<<HTML
{$viewHeaderBlock->toHtml()}
<div id="ebay_category_chooser">{$chooserBlock->toHtml()}</div>
{$parentHtml}
HTML;
    }

    //########################################
}
