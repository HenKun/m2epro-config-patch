<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing;

class View extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionDataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Data\Session $sessionDataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->sessionDataHelper = $sessionDataHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->globalDataHelper->getValue('view_listing');

        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Switcher $viewModeSwitcher */
        $viewModeSwitcher = $this->getLayout()
                                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Switcher::class);

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingView');
        $this->_controller = 'adminhtml_amazon_listing_view_' . $viewModeSwitcher->getSelectedParam();
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('add');
        // ---------------------------------------
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/autoAction.css');
        $this->css->addFile('amazon/listing/view.css');
        $this->css->addFile('amazon/listing/product/variation/grid.css');

        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->appendHelpBlock([
                'content' => $this->__(
                    '<p>M2E Pro Listing is a group of Magento Products sold on a certain Marketplace from a
                    particular Account. M2E Pro has several options to display the content of Listings
                    referring to different data details. Each of the view options contains a unique set of
                    available Actions accessible in the Mass Actions drop-down.</p>'
                )
            ]);

            $this->setPageActionsBlock(
                'Amazon_Listing_View_Switcher',
                'amazon_listing_view_switcher'
            );
        }

        // ---------------------------------------
        $this->addButton('back', [
            'label'   => $this->__('Back'),
            'onclick' => 'setLocation(\''.$this->getUrl('*/amazon_listing/index') . '\');',
            'class'   => 'back'
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('view_logs', [
            'label'   => $this->__('Logs & Events'),
            'onclick' => 'window.open(\''.$this->getUrl('*/amazon_log_listing_product/index', [
                \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD =>
                    $this->listing->getId()
            ]) . '\');',
            'class'   => '',
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('edit_settings', [
            'label'   => $this->__('Edit Settings'),
            'onclick' => '',
            'class'   => 'drop_down edit_default_settings_drop_down primary',
            'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown::class,
            'options' => $this->getSettingsButtonDropDownItems()
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $this->addButton('add_products', [
            'id'        => 'add_products',
            'label'     => $this->__('Add Products'),
            'class'     => 'add',
            'button_class' => '',
            'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\DropDown::class,
            'options' => $this->getAddProductsDropDownItems(),
        ]);
        // ---------------------------------------

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        return '<div id="listing_view_progress_bar"></div>' .
            '<div id="listing_container_errors_summary" class="errors_summary" style="display: none;"></div>' .
            '<div id="listing_view_content_container">' .
            parent::_toHtml() .
            '</div>';
    }

    public function getGridHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return parent::getGridHtml();
        }

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Listing::class)
        );
        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::class
        ));

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Amazon\Account::class)
        );

        $showAutoAction = $this->dataHelper->jsonEncode((bool)$this->getRequest()->getParam('auto_actions'));

        // ---------------------------------------
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions(
            'Amazon_Listing_AutoAction',
            ['listing_id' => $this->getRequest()->getParam('id')]
        ));

        $path = 'amazon_listing_autoAction/getDescriptionTemplatesList';
        $this->jsUrl->add($this->getUrl('*/' . $path, [
            'marketplace_id' => $this->listing->getMarketplaceId(),
            'is_new_asin_accepted' => 1
        ]), $path);

        $path = 'amazon_log_listing_product/index';
        $this->jsUrl->add($this->getUrl('*/' . $path), $path);

        $path = 'amazon_listing/duplicateProducts';
        $this->jsUrl->add($this->getUrl('*/' . $path), $path);

        $path = 'amazon_listing/transferring/index';
        $this->jsUrl->add($this->getUrl('*/' . $path, [
            'listing_id' => $this->listing->getId()
        ]), $path);

        $path = 'amazon_listing_transferring/getMarketplace';
        $this->jsUrl->add($this->getUrl('*/' . $path), $path);

        $path = 'amazon_listing_transferring/getListings';
        $this->jsUrl->add($this->getUrl('*/' . $path), $path);

        $this->jsUrl->add($this->getUrl('*/amazon_log_listing_product/index', [
            \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD => $this->listing['id'],
        ]), 'logViewUrl');

        $this->jsUrl->add($this->getUrl('*/listing/getErrorsSummary'), 'getErrorsSummary');

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon\Listing'));

        $this->jsUrl->addUrls([
            'runListProducts' => $this->getUrl('*/amazon_listing/runListProducts'),
            'runRelistProducts' => $this->getUrl('*/amazon_listing/runRelistProducts'),
            'runReviseProducts' => $this->getUrl('*/amazon_listing/runReviseProducts'),
            'runStopProducts' => $this->getUrl('*/amazon_listing/runStopProducts'),
            'runStopAndRemoveProducts' => $this->getUrl('*/amazon_listing/runStopAndRemoveProducts'),
            'runDeleteAndRemoveProducts' => $this->getUrl('*/amazon_listing/runDeleteAndRemoveProducts'),
        ]);

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon_Listing_Product'));
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon_Listing_Product_Fulfillment'));
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon_Listing_Product_Search'));
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Amazon_Listing_Product_Template_Description')
        );
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Amazon_Listing_Product_Template_Shipping')
        );
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Amazon_Listing_Product_Template_ProductTaxCode')
        );
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon_Listing_Product_Variation'));
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Amazon_Listing_Product_Variation_Manage')
        );
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Amazon_Listing_Product_Variation_Vocabulary')
        );
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Amazon_Listing_Product_Variation_Individual')
        );

        $this->jsUrl->add($this->getUrl('*/listing_moving/moveToListingGrid'), 'moveToListingGridHtml');
        $this->jsUrl->add($this->getUrl('*/listing_moving/prepareMoveToListing'), 'prepareData');
        $this->jsUrl->add($this->getUrl('*/listing_moving/moveToListing'), 'moveToListing');

        $this->jsUrl->add($this->getUrl(
            '*/listing_mapping/mapProductPopupHtml',
            [
                'account_id'     => $this->listing->getAccountId(),
                'marketplace_id' => $this->listing->getMarketplaceId()
            ]
        ), 'mapProductPopupHtml');
        $this->jsUrl->add($this->getUrl('*/listing_mapping/remap'), 'listing_mapping/remap');

        $this->jsUrl->add($this->getUrl('*/amazon_marketplace/index'), 'marketplaceSynchUrl');

        $this->jsUrl->add($this->getUrl('*/listing/saveListingAdditionalData', [
            'id' => $this->listing['id']
        ]), 'saveListingAdditionalData');

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions(
            'Amazon_Listing_Product_Repricing',
            [
                'id' => $this->listing['id'],
                'account_id' => $this->listing['account_id']
            ]
        ));

        // ---------------------------------------

        $component = \Ess\M2ePro\Helper\Component\Amazon::NICK;
        $gridId = $this->getChildBlock('grid')->getId();
        $ignoreListings = $this->dataHelper->jsonEncode([$this->listing['id']]);
        $marketplace = $this->dataHelper->jsonEncode(array_merge(
            $this->listing->getMarketplace()->getData(),
            $this->listing->getMarketplace()->getChildObject()->getData()
        ));
        $isNewAsinAvailable = $this->dataHelper->jsonEncode(
            $this->listing->getMarketplace()->getChildObject()->isNewAsinAvailable()
        );

        $temp = $this->sessionDataHelper->getValue('products_ids_for_list', true);
        $productsIdsForList = empty($temp) ? '' : $temp;

        $templateDescriptionPopupTitle = $this->__('Assign Description Policy');

        $popupTitle = $this->__('Moving Amazon Items');

        $taskCompletedMessage = $this->__('Task completed. Please wait ...');
        $taskCompletedSuccessMessage = $this->__('"%task_title%" Task has submitted to be processed.');
        $taskCompletedWarningMessage = $this->__(
            '"%task_title%" Task has completed with warnings. <a target="_blank" href="%url%">View Log</a> for details.'
        );
        $taskCompletedErrorMessage = $this->__(
            '"%task_title%" Task has completed with errors. <a target="_blank" href="%url%">View Log</a> for details.'
        );

        $sendingDataToAmazonMessage = $this->__('Sending %product_title% Product(s) data on Amazon.');
        $viewAllProductLogMessage = $this->__('View Full Product Log');

        $listingLockedMessage = $this->__('The Listing was locked by another process. Please try again later.');
        $listingEmptyMessage = $this->__('Listing is empty.');

        $listingAllItemsMessage = $this->__('Listing All Items On Amazon');
        $listingSelectedItemsMessage = $this->__('Listing Selected Items On Amazon');
        $revisingSelectedItemsMessage = $this->__('Revising Selected Items On Amazon');
        $relistingSelectedItemsMessage = $this->__('Relisting Selected Items On Amazon');
        $stoppingSelectedItemsMessage = $this->__('Stopping Selected Items On Amazon');
        $stoppingAndRemovingSelectedItemsMessage = $this->__(
            'Stopping On Amazon And Removing From Listing Selected Items'
        );
        $deletingAndRemovingSelectedItemsMessage = $this->__('Removing From Amazon And Listing Selected Items');
        $removingSelectedItemsMessage = $this->__('Removing From Listing Selected Items');

        $selectItemsMessage = $this->__('Please select the Products you want to perform the Action on.');
        $selectActionMessage = $this->__('Please select Action.');

        $assignString = $this->__('Assign');

        $templateShippingPopupTitle = $this->__('Assign Shipping Template Policy');
        $templateProductTaxCodePopupTitle   = $this->__('Assign Product Tax Code Policy');

        $enterProductSearchQueryMessage = $this->__('Please enter Product Title or ASIN/ISBN/UPC/EAN.');
        $autoMapAsinSearchProducts = $this->__('Search %product_title% Product(s) on Amazon.');
        $autoMapAsinProgressTitle = $this->__('Automatic Assigning ASIN/ISBN to Item(s)');
        $autoMapAsinErrorMessage = $this->__('Server is currently unavailable. Please try again later.');
        $newAsinNotAvailable = $this->__(
            'The new ASIN/ISBN creation functionality is not available in %code% Marketplace yet.'
        );
        $notSynchronizedMarketplace = $this->__(
            'In order to use New ASIN/ISBN functionality, please re-synchronize Marketplace data.'
        ) . ' ' .
            $this->__('Press "Save And Update" Button after redirect on Marketplace Page.');

        $noVariationsLeftText = $this->__('All variations are already added.');

        $notSet = $this->__('Not Set');
        $setAttributes = $this->__('Set Attributes');
        $variationManageMatchedAttributesError = $this->__('Please choose valid Attributes.');
        $variationManageMatchedAttributesErrorDuplicateSelection =
            $this->__('You can not choose the same Attribute twice.');

        $variationManageSkuPopUpTitle =
            $this->__('Enter Amazon Parent Product SKU');

        $switchToIndividualModePopUpTitle = $this->__('Change "Manage Variations" Mode');
        $switchToParentModePopUpTitle = $this->__('Change "Manage Variations" Mode');

        $emptySkuError = $this->__('Please enter Amazon Parent Product SKU.');

        $this->jsTranslator->addTranslations([
            'Remove Category' => $this->__('Remove Category'),
            'Add New Rule' => $this->__('Add New Rule'),
            'Add/Edit Categories Rule' => $this->__('Add/Edit Categories Rule'),
            'Auto Add/Remove Rules' => $this->__('Auto Add/Remove Rules'),
            'Based on Magento Categories' => $this->__('Based on Magento Categories'),
            'You must select at least 1 Category.' => $this->__('You must select at least 1 Category.'),
            'Rule with the same Title already exists.' => $this->__('Rule with the same Title already exists.'),
            'Sell on Another Marketplace' => $this->__('Sell on Another Marketplace'),
            'Create new' => $this->__('Create new'),
            'Linking Product' => $this->__('Linking Product'),

            'Add New Shipping Policy' => $this->__('Add New Shipping Policy'),
            'Add New Product Tax Code Policy'  => $this->__('Add New Product Tax Code Policy'),
            'Add New Listing' => $this->__('Add New Listing'),

            'Clear Search Results' => $this->__('Clear Search Results'),

            'popup_title' => $popupTitle,

            'task_completed_message' => $taskCompletedMessage,
            'task_completed_success_message' => $taskCompletedSuccessMessage,
            'task_completed_warning_message' => $taskCompletedWarningMessage,
            'task_completed_error_message' => $taskCompletedErrorMessage,

            'sending_data_message' => $sendingDataToAmazonMessage,
            'view_all_product_log_message' => $viewAllProductLogMessage,

            'listing_locked_message' => $listingLockedMessage,
            'listing_empty_message' => $listingEmptyMessage,

            'listing_all_items_message' => $listingAllItemsMessage,
            'listing_selected_items_message' => $listingSelectedItemsMessage,
            'revising_selected_items_message' => $revisingSelectedItemsMessage,
            'relisting_selected_items_message' => $relistingSelectedItemsMessage,
            'stopping_selected_items_message' => $stoppingSelectedItemsMessage,
            'stopping_and_removing_selected_items_message' => $stoppingAndRemovingSelectedItemsMessage,
            'deleting_and_removing_selected_items_message' => $deletingAndRemovingSelectedItemsMessage,
            'removing_selected_items_message' => $removingSelectedItemsMessage,

            'select_items_message' => $selectItemsMessage,
            'select_action_message' => $selectActionMessage,

            'templateDescriptionPopupTitle' => $templateDescriptionPopupTitle,

            'templateShippingPopupTitle' => $templateShippingPopupTitle,
            'templateProductTaxCodePopupTitle'   => $templateProductTaxCodePopupTitle,

            'assign' => $assignString,

            'enter_productSearch_query' => $enterProductSearchQueryMessage,
            'automap_asin_search_products' => $autoMapAsinSearchProducts,
            'automap_asin_progress_title' => $autoMapAsinProgressTitle,
            'automap_error_message' => $autoMapAsinErrorMessage,

            'new_asin_not_available' => $newAsinNotAvailable,
            'not_synchronized_marketplace' => $notSynchronizedMarketplace,

            'no_variations_left' => $noVariationsLeftText,

            'not_set' => $notSet,
            'set_attributes' => $setAttributes,
            'variation_manage_matched_attributes_error' => $variationManageMatchedAttributesError,
            'variation_manage_matched_attributes_error_duplicate' =>
                $variationManageMatchedAttributesErrorDuplicateSelection,

            'error_changing_product_options' => $this->__('Please Select Product Options.'),

            'variation_manage_matched_sku_popup_title' => $variationManageSkuPopUpTitle,
            'empty_sku_error' => $emptySkuError,

            'switch_to_individual_mode_popup_title' => $switchToIndividualModePopUpTitle,
            'switch_to_parent_mode_popup_title' => $switchToParentModePopUpTitle,

            'Add New Description Policy' => $this->__('Add New Description Policy'),
            'Add New Child Product' => $this->__('Add New Child Product')
        ]);

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'M2ePro/Amazon/Listing/View/Grid',
        'M2ePro/Amazon/Listing/AutoAction',
        'M2ePro/Amazon/Listing/Product/Variation',
        'M2ePro/Amazon/Listing/Product/Repricing/Price'
    ], function(){

        M2ePro.productsIdsForList = '{$productsIdsForList}';

        M2ePro.customData.componentMode = '{$component}';
        M2ePro.customData.gridId = '{$gridId}';
        M2ePro.customData.ignoreListings = '{$ignoreListings}';

        M2ePro.customData.marketplace = {$marketplace};
        M2ePro.customData.isNewAsinAvailable = {$isNewAsinAvailable};

        ListingGridObj = new AmazonListingViewGrid(
            '{$gridId}',
            {$this->listing['id']}
        );
        ListingGridObj.afterInitPage();

        ListingGridObj.movingHandler.setProgressBar('listing_view_progress_bar');
        ListingGridObj.movingHandler.setGridWrapper('listing_view_content_container');

        ListingGridObj.actionHandler.setProgressBar('listing_view_progress_bar');
        ListingGridObj.actionHandler.setGridWrapper('listing_view_content_container');

        AmazonListingProductVariationObj = new AmazonListingProductVariation(ListingGridObj);

        if (M2ePro.productsIdsForList) {
            ListingGridObj.getGridMassActionObj().checkedString = M2ePro.productsIdsForList;
            ListingGridObj.actionHandler.listAction();
        }

        window.ListingAutoActionObj = new AmazonListingAutoAction();
        if ({$showAutoAction}) {
            ListingAutoActionObj.loadAutoActionHtml();
        }

        AmazonListingProductRepricingPriceObj = new AmazonListingProductRepricingPrice();
    });
JS
        );

        $productSearchBlock = $this->getLayout()
                                   ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Search\Main::class);

        // ---------------------------------------
        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            [
            'data' => ['listing' => $this->listing]
            ]
        );
        // ---------------------------------------

        return $viewHeaderBlock->toHtml()
            . $productSearchBlock->toHtml()
            . parent::getGridHtml();
    }

    /**
     * @return array
     */
    protected function getSettingsButtonDropDownItems(): array
    {
        $backUrl = $this->dataHelper->makeBackUrlParam('*/amazon_listing/view', [
            'id' => $this->listing['id'],
        ]);

        $url = $this->getUrl('*/amazon_listing/edit', [
            'id'   => $this->listing['id'],
            'back' => $backUrl,
        ]);

        return [
            [
                'label'   => $this->__('Selling'),
                'onclick' => "window.open('$url', '_blank');",
                'default' => true,
            ],
            [
                'onclick' => 'ListingAutoActionObj.loadAutoActionHtml();',
                'label'   => $this->__('Auto Add/Remove Rules'),
            ],
        ];
    }

    public function getAddProductsDropDownItems()
    {
        $items = [];

        $backUrl = $this->dataHelper->makeBackUrlParam('*/amazon_listing/view', [
            'id' => $this->listing['id']
        ]);

        // ---------------------------------------
        $url = $this->getUrl('*/amazon_listing_product_add/index', [
            'id' => $this->listing['id'],
            'back' => $backUrl,
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'clear' => 1,
            'step' => 2,
            'source' => \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode::MODE_PRODUCT
        ]);
        $items[] = [
            'id' => 'add_products_mode_product',
            'label' => $this->__('From Products List'),
            'onclick' => "setLocation('" . $url . "')",
            'default' => true
        ];
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/amazon_listing_product_add/index', [
            'id' => $this->listing['id'],
            'back' => $backUrl,
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'clear' => 1,
            'step' => 2,
            'source' => \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode::MODE_CATEGORY
        ]);
        $items[] = [
            'id' => 'add_products_mode_category',
            'label' => $this->__('From Categories'),
            'onclick' => "setLocation('" . $url . "')"
        ];
        // ---------------------------------------

        return $items;
    }
}
