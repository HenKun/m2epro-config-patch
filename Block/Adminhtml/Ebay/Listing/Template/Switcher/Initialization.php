<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template\Switcher;

class Initialization extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('EbayListingTemplateSwitcherInitialization');
        // ---------------------------------------
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();
    }

    //########################################

    protected function _toHtml()
    {
        // ---------------------------------------
        $urls = [];

        // initiate account param
        // ---------------------------------------
        $account = $this->globalDataHelper->getValue('ebay_account');
        $params['account_id'] = $account->getId();
        // ---------------------------------------

        // initiate marketplace param
        // ---------------------------------------
        $marketplace = $this->globalDataHelper->getValue('ebay_marketplace');
        $params['marketplace_id'] = $marketplace->getId();
        // ---------------------------------------

        // initiate attribute sets param
        // ---------------------------------------
        if ($this->getMode() == \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template\Switcher::MODE_LISTING_PRODUCT) {
            $attributeSets = $this->globalDataHelper->getValue('ebay_attribute_sets');
            $params['attribute_sets'] = implode(',', $attributeSets);
        }
        // ---------------------------------------

        // initiate display use default option param
        // ---------------------------------------
        $displayUseDefaultOption = $this->globalDataHelper->getValue('ebay_display_use_default_option');
        $params['display_use_default_option'] = (int)(bool)$displayUseDefaultOption;
        // ---------------------------------------

        $path = 'ebay_template/getTemplateHtml';
        $urls[$path] = $this->getUrl('*/' . $path, $params);
        //------------------------------

        //------------------------------
        $path = 'ebay_template/isTitleUnique';
        $urls[$path] = $this->getUrl('*/' . $path);

        $path = 'ebay_template/newTemplateHtml';
        $urls[$path] = $this->getUrl('*/' . $path);

        $path = 'ebay_template/edit';
        $urls[$path] = $this->getUrl(
            '*/ebay_template/edit',
            ['wizard' => (bool)$this->getRequest()->getParam('wizard', false)]
        );
        //------------------------------

        $this->jsUrl->addUrls($urls);
        $this->jsUrl->add(
            $this->getUrl(
                '*/template/checkMessages',
                ['component_mode' => \Ess\M2ePro\Helper\Component\Ebay::NICK]
            ),
            'templateCheckMessages'
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Ebay\Template\Manager::class)
        );

        $this->jsTranslator->addTranslations([
            'Customized' => $this->__('Customized'),
            'Policies' => $this->__('Policies'),
            'Policy with the same Title already exists.' => $this->__('Policy with the same Title already exists.'),
            'Please specify Policy Title' => $this->__('Please specify Policy Title'),
            'Save New Policy' => $this->__('Save New Policy'),
            'Save as New Policy' => $this->__('Save as New Policy'),
        ]);

        $store = $this->globalDataHelper->getValue('ebay_store');
        $marketplace = $this->globalDataHelper->getValue('ebay_marketplace');

        $this->js->add(<<<JS
    define('Switcher/Initialization',[
        'M2ePro/Ebay/Listing/Template/Switcher',
        'M2ePro/TemplateManager'
    ], function(){
        window.TemplateManagerObj = new TemplateManager();

        window.EbayListingTemplateSwitcherObj = new EbayListingTemplateSwitcher();
        EbayListingTemplateSwitcherObj.storeId = {$store->getId()};
        EbayListingTemplateSwitcherObj.marketplaceId = {$marketplace->getId()};
        EbayListingTemplateSwitcherObj.listingProductIds = '{$this->getRequest()->getParam('ids')}';

    });
JS
        );

        return parent::_toHtml();
    }

    //########################################
}
