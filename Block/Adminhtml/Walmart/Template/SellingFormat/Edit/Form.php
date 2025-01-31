<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Button;
use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\Edit\Form\Promotions;
use Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\Edit\Form\ShippingOverrideRules;
use Ess\M2ePro\Model\Walmart\Template\SellingFormat;

class Form extends AbstractForm
{
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    public $templateModel;
    public $formData = [];

    public $allAttributesByInputTypes     = [];
    public $allAttributes             = [];

    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    protected $magentoAttributeHelper;

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->supportHelper = $supportHelper;
        $this->databaseHelper = $databaseHelper;
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->walmartHelper = $walmartHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartTemplateSellingFormatEditForm');
        // ---------------------------------------

        $this->templateModel   = $this->globalDataHelper->getValue('tmp_template');
        $this->formData        = $this->getFormData();

        $this->allAttributes = $this->magentoAttributeHelper->getAll();
        $this->allAttributesByInputTypes = [
            'text'        => $this->magentoAttributeHelper
                ->filterByInputTypes($this->allAttributes, ['text']),
            'text_select' => $this->magentoAttributeHelper
                ->filterByInputTypes($this->allAttributes, ['text', 'select']),
            'text_price'  => $this->magentoAttributeHelper
                ->filterByInputTypes($this->allAttributes, ['text', 'price']),
            'text_date'   => $this->magentoAttributeHelper
                ->filterByInputTypes($this->allAttributes, ['text', 'date']),
            'text_weight' => $this->magentoAttributeHelper
                ->filterByInputTypes($this->allAttributes, ['text', 'weight']),
            'boolean'     => $this->magentoAttributeHelper
                ->filterByInputTypes($this->allAttributes, ['boolean']),
        ];
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(['data' => [
            'id'      => 'edit_form',
            'method'  => 'post',
            'action'  => $this->getUrl('*/*/save'),
            'enctype' => 'multipart/form-data'
        ]]);

        $form->addField(
            'walmart_template_selling_format_help',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
                    <p>Selling Policy contains conditions based on which you are going to sell your Item on the
                    Channel, e.g. Item Price, Quantity, Shipping and Product Tax Code settings, etc.
                    Take Magento Price and Quantity values as they are or use the Price Change box and
                    Conditional/Percentage Quantity options to modify the related Magento data.
                    By creating Promotion rules, you can price your Items at reduced values during the
                    specified period. Use the Shipping Overrides option when you need to override the
                    global shipping setting. If you would like to limit your Item availability on Walmart to the
                    certain period, define Start/End Dates.</p><br>
                    <p><strong>Note:</strong> Selling Policy is created per marketplace that cannot be changed
                    after the Policy is assigned to M2E Pro Listing.</p><br>
                    <p><strong>Note:</strong> Selling Policy is required when you create a
                    new offer on Walmart.</p><br>
HTML
                )
            ]
        );

        // ---------------------------------------

        $fieldset = $form->addFieldset(
            'general_fieldset',
            ['legend' => __('General'), 'collapsable' => false]
        );

        // ---------------------------------------

        $fieldset->addField(
            'title',
            'text',
            [
                'name'     => 'title',
                'label'    => $this->__('Title'),
                'title'    => $this->__('Title'),
                'value'    => $this->formData['title'],
                'class'    => 'input-text M2ePro-price-tpl-title',
                'required' => true,
                'tooltip'  => $this->__('Policy Title for your internal use.')
            ]
        );

        // ---------------------------------------

        $isLockedMarketplace = !empty($this->formData['marketplace_id']);

        $fieldset->addField(
            'marketplace_id',
            'select',
            [
                'name'     => 'marketplace_id',
                'label'    => $this->__('Marketplace'),
                'title'    => $this->__('Marketplace'),
                'values'   => $this->getMarketplaceDataToOptions(),
                'value'    => $this->formData['marketplace_id'],
                'required' => true,
                'disabled' => $isLockedMarketplace,
            ]
        );

        if ($isLockedMarketplace) {
            $fieldset->addField(
                'marketplace_id_hidden',
                'hidden',
                [
                    'name'  => 'marketplace_id',
                    'value' => $this->formData['marketplace_id'],
                ]
            );
        }

        // ---------------------------------------

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_selling_format_qty',
            [
                'legend' => $this->__('Quantity'),
                'class' => 'm2epro-marketplace-depended-block',
                'collapsable' => false
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if (in_array($this->formData['qty_mode'], [\Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT,
                                                   \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_NUMBER])
        ) {
            $defaultValue = $this->formData['qty_mode'];
        }

        $fieldset->addField(
            'qty_mode',
            self::SELECT,
            [
                'name'                     => 'qty_mode',
                'container_id'             => 'qty_mode_tr',
                'label'                    => $this->__('Quantity'),
                'values'                   => $this->getQtyOptions(),
                'value'                    => $defaultValue,
                'create_magento_attribute' => true,
                'tooltip'                  => $this->__('Item Quantity available on Walmart.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'qty_custom_attribute',
            'hidden',
            [
                'name' => 'qty_custom_attribute',
                'value' => $this->formData['qty_custom_attribute']
            ]
        );

        $fieldset->addField(
            'qty_custom_value',
            'text',
            [
                'name'         => 'qty_custom_value',
                'container_id' => 'qty_custom_value_tr',
                'label'        => $this->__('Quantity Value'),
                'value'        => $this->formData['qty_custom_value'],
                'class'        => 'validate-digits M2ePro-required-when-visible',
                'required'     => true,
                'field_extra_attributes' => 'style="display: none;"',
            ]
        );

        // ---------------------------------------

        $preparedAttributes = [];
        for ($i = 100; $i >= 5; $i -= 5) {
            $preparedAttributes[] = [
                'value' => $i,
                'label' => $i . ' %'
            ];
        }

        $fieldset->addField(
            'qty_percentage',
            self::SELECT,
            [
                'name' => 'qty_percentage',
                'container_id' => 'qty_percentage_tr',
                'label' => $this->__('Quantity Percentage'),
                'values' => $preparedAttributes,
                'value' => $this->formData['qty_percentage'],
                'tooltip' => $this->__(
                    'Specify what percentage of Magento Product Quantity has to be submitted to Walmart.<br>
                    <strong>For example</strong>, if QTY Percentage is set to 10% and
                    Magento Product Quantity is 100,<br>
                    the Item Quantity available on Walmart will be calculated as 100 * 10% = 10.'
                )
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'qty_modification_mode',
            self::SELECT,
            [
                'name' => 'qty_modification_mode',
                'container_id' => 'qty_modification_mode_tr',
                'label' => $this->__('Conditional Quantity'),
                'values' => [
                    SellingFormat::QTY_MODIFICATION_MODE_OFF => $this->__('Disabled'),
                    SellingFormat::QTY_MODIFICATION_MODE_ON => $this->__('Enabled'),
                ],
                'value' => $this->formData['qty_modification_mode'],
                'tooltip' => $this->__('Enable to set the minimum and maximum Item Quantity
                                        values that will be submitted to Walmart.')
            ]
        );

        $fieldset->addField(
            'qty_min_posted_value',
            'text',
            [
                'name' => 'qty_min_posted_value',
                'container_id' => 'qty_min_posted_value_tr',
                'label' => $this->__('Minimum Quantity to Be Listed'),
                'value' => $this->formData['qty_min_posted_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'field_extra_attributes' => 'style="display: none;"',
                'tooltip' => $this->__(
                    'If Magento Product QTY is 2 and you set the Minimum Quantity to Be Listed of 5,
                    the Item will not be listed on Walmart.<br>
                    To be submitted, Magento Product QTY has to be equal or more than the value set
                    for Minimum Quantity to Be Listed.'
                )
            ]
        );

        $fieldset->addField(
            'qty_max_posted_value',
            'text',
            [
                'name' => 'qty_max_posted_value',
                'container_id' => 'qty_max_posted_value_tr',
                'label' => $this->__('Maximum Quantity to Be Listed'),
                'value' => $this->formData['qty_max_posted_value'],
                'class' => 'M2ePro-validate-qty',
                'required' => true,
                'field_extra_attributes' => 'style="display: none;"',
                'tooltip' => $this->__(
                    'If Magento Product QTY is 5 and you set the Maximum Quantity to Be Listed of 2,
                     the listed Item will have maximum Quantity of 2.<br>
                     If Magento Product QTY is 1 and you set the Maximum Quantity to Be Listed of 3,
                     the listed Item will have maximum Quantity of 1.'
                )
            ]
        );

        // ---------------------------------------

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_selling_format_prices',
            [
                'legend' => $this->__('Price'),
                'class' => 'm2epro-marketplace-depended-block',
                'collapsable' => false
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if (in_array($this->formData['price_mode'], [\Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE,
                                                     \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT,
                                                     \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL])
        ) {
            $defaultValue = $this->formData['price_mode'];
        }

        $priceRegularCoefficient = $this->elementFactory->create('text', ['data' => [
            'html_id' => 'price_coefficient',
            'name' => 'price_coefficient',
            'label' => '',
            'value' => $this->formData['price_coefficient'],
            'class' => 'M2ePro-validate-price-coefficient',
        ]]);
        $priceRegularCoefficient->setForm($form);

        $tooltipRegularPriceMode = $this->getTooltipHtml(
            $this->__('Item Price displayed on Walmart.')
        );

        $tooltipRegularPriceCoefficient = $this->getTooltipHtml(
            $this->__('Absolute figure (+8,-3), percentage (+15%, -20%) or Currency rate (1.44)')
        );

        $fieldset->addField(
            'price_mode',
            self::SELECT,
            [
                'name' => 'price_mode',
                'label' => $this->__('Price'),
                'values' => $this->getPriceOptions(),
                'value' => $defaultValue,
                'class' => 'select-main',
                'create_magento_attribute' => true,
                'after_element_html' => $tooltipRegularPriceMode
                                        . '<span id="price_coefficient_td">'
                                        . $priceRegularCoefficient->toHtml()
                                        . $tooltipRegularPriceCoefficient . '</span>'
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField(
            'price_custom_attribute',
            'hidden',
            [
                'name' => 'price_custom_attribute',
                'value' => $this->formData['price_custom_attribute']
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'price_variation_mode',
            self::SELECT,
            [
                'name' => 'price_variation_mode',
                'label' => $this->__('Variation Price Source'),
                'class' => 'select-main',
                'values' => [
                    SellingFormat::PRICE_VARIATION_MODE_PARENT => $this->__('Main Product'),
                    SellingFormat::PRICE_VARIATION_MODE_CHILDREN => $this->__('Associated Products')
                ],
                'value' => $this->formData['price_variation_mode'],
                'tooltip' => $this->__(
                    'Select where the price for Configurable Product Variations should be taken from.'
                )
            ]
        );

        // ---------------------------------------

        $defaultValue = '';
        if (in_array($this->formData['map_price_mode'], [\Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE,
                                                     \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT,
                                                     \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL])
        ) {
            $defaultValue = $this->formData['map_price_mode'];
        }

        $fieldset->addField(
            'map_price_mode',
            self::SELECT,
            [
                'name' => 'map_price_mode',
                'label' => $this->__('Minimum Advertised Price'),
                'class' => 'select-main',
                'values' => $this->getMapPriceOptions(),
                'value' => $defaultValue,
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'It is the lowest Price that retailer can advertise the Product for sale.<br>
                    <strong>Note:</strong> If your Item Price is below the manufacturer\'s Minimum Advertised Price,
                    it will not be displayed on the Walmart Item page.<br>
                    Buyers will see your retail Price only after they add the Item to the cart.'
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField(
            'map_price_custom_attribute',
            'hidden',
            [
                'name' => 'map_price_custom_attribute',
                'value' => $this->formData['map_price_custom_attribute']
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'promotions_mode',
            self::SELECT,
            [
                'name' => 'promotions_mode',
                'label' => $this->__('Promotions'),
                'class' => 'select-main',
                'values' => [
                    SellingFormat::PROMOTIONS_MODE_NO => $this->__('Disabled'),
                    SellingFormat::PROMOTIONS_MODE_YES => $this->__('Enabled')
                ],
                'value' => $this->formData['promotions_mode'],
                'required' => true,
                'tooltip' => $this->__(
                    'Enable to add up to 10 Promotion rules. Price your Items at
                    reduced values during the specified period.<br>
                    Comparison Price is used to calculate savings on Items. Remain the current
                    Item Prices or submit a new values.<br>
                    <strong>Note:</strong> Make sure that your Promotion dates do not overlap.'
                )
            ]
        );

        $fieldset->addField(
            'promotions_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->getPromotionsHtml($form),
                'css_class' => 'm2epro-fieldset-table',
                'field_extra_attributes' => 'id="promotions_tr"'
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'price_increase_vat_percent',
            self::SELECT,
            [
                'name' => 'price_increase_vat_percent',
                'label' => $this->__('Add VAT Percentage'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'required' => true,
                'value' => (int)($this->formData['price_vat_percent'] > 0),
                'tooltip' => $this->__(
                    <<<HTML
Enable this option to add a specified VAT percent value to the Price when a Product is listed on Walmart.
<br/>
<br/>
The final product price on Walmart will be calculated according to the following formula:
<br/>
<br/>
(Product Price + Price Change) + VAT Rate
<br/>
<br/>
<strong>Note:</strong> Walmart considers the VAT rate value sent by M2E Pro as an additional price increase,
not as a proper VAT rate.
HTML
                )
            ]
        );

        $fieldset->addField(
            'price_vat_percent',
            'text',
            [
                'name' => 'price_vat_percent',
                'container_id' => 'price_vat_percent_tr',
                'label' => $this->__('VAT Rate, %'),
                'value' => $this->formData['price_vat_percent'],
                'class' => 'M2ePro-validate-vat-percent input-text',
                'required' => true,
                'field_extra_attributes' => 'style="display: none;"'
            ]
        );

        // ---------------------------------------

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_selling_format_details',
            [
                'legend' => $this->__('Details'),
                'class' => 'm2epro-marketplace-depended-block',
                'collapsable' => false
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'lag_time_custom_attribute',
            'hidden',
            [
                'name' => 'lag_time_custom_attribute',
                'value' => $this->formData['lag_time_custom_attribute']
            ]
        );

        $fieldset->addField(
            'lag_time_value',
            'hidden',
            [
                'name' => 'lag_time_value',
                'value' => $this->formData['lag_time_value']
            ]
        );

        $fieldset->addField(
            'lag_time_mode',
            self::SELECT,
            [
                'name' => 'lag_time_mode',
                'label' => $this->__('Lag Time'),
                'values' => $this->getLagTimeOptions(),
                'value' => '',
                'required' => true,
                'tooltip' => $this->__('
                    The number of days it takes to prepare the Item for shipment.<br><br>

                    <strong>Note:</strong> Sellers are required to provide up to 1 day Lag Time. Otherwise,
                    the Lag Time Exception must be requested.<br>
                    The details can be found
                    <a href="https://sellerhelp.walmart.com/seller/s/guide?article=000005986" target="_blank">here</a>.
                    <br><br>

                    <strong>Note:</strong> any changes to the Lag Time value may take up to 24 hours to be reflected on
                    the Channel due to the Walmart limitations.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        // ---------------------------------------

        $fieldset->addField(
            'product_tax_code_custom_attribute',
            'hidden',
            [
                'name' => 'product_tax_code_custom_attribute',
                'value' => $this->formData['product_tax_code_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['product_tax_code_mode'] == SellingFormat::PRODUCT_TAX_CODE_MODE_VALUE) {
            $defaultValue = $this->formData['product_tax_code_mode'];
        }

        $fieldset->addField(
            'product_tax_code_mode',
            self::SELECT,
            [
                'name' => 'product_tax_code_mode',
                'label' => $this->__('Product Tax Code'),
                'title' => $this->__('Product Tax Code'),
                'values' => $this->getProductTaxCodeOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'required' => true,
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'Provide a Product Tax Code, which is assigned to a taxable item so Walmart can automatically
                    calculate taxes on each sale. Find the current Sales Tax Codes
                    <a href="javascript:void(0)" onclick="%onclick%">here</a>.',
                    'WalmartTemplateSellingFormatObj.openTaxCodePopup(true);'
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        $fieldset->addField(
            'product_tax_code_custom_value',
            'text',
            [
                'name' => 'product_tax_code_custom_value',
                'container_id' => 'product_tax_code_custom_value_tr',
                'label' => $this->__('Product Tax Code Value'),
                'value' => $this->formData['product_tax_code_custom_value'],
                'class' => 'M2ePro-required-when-visible M2ePro-validation-int M2ePro-validation-walmart-tax-code',
                'required' => true,
                'style' => 'width: 65%',
                'field_extra_attributes' => 'style="display: none;"',
                'after_element_html' => '<span id="tax_codes">' . $this->getLayout()->createBlock(Button::class)
                                     ->setData('label', $this->__('Show Sales Tax Codes'))
                                     ->addData([
                                         'onclick' => 'WalmartTemplateSellingFormatObj.openTaxCodePopup(false)',
                                         'class'   => 'add bt_tax_codes primary'
                                     ])->toHtml() . '</span>'
            ]
        );

        // ---------------------------------------

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_selling_format_shipping',
            [
                'legend' => $this->__('Shipping'),
                'class' => 'm2epro-marketplace-depended-block',
                'collapsable' => false
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'item_weight_custom_attribute',
            'hidden',
            [
                'name' => 'item_weight_custom_attribute',
                'value' => $this->formData['item_weight_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['item_weight_mode'] == SellingFormat::WEIGHT_MODE_CUSTOM_VALUE) {
            $defaultValue = $this->formData['item_weight_mode'];
        }

        $fieldset->addField(
            'item_weight_mode',
            self::SELECT,
            [
                'name' => 'item_weight_mode',
                'label' => $this->__('Weight'),
                'title' => $this->__('Weight'),
                'values' => $this->getItemWeightModeOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('The weight of the Item when it is packaged to ship.<br>
                                <strong>Note:</strong> The Shipping Weight is set in pounds by default.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'item_weight_custom_value',
            'text',
            [
                'name' => 'item_weight_custom_value',
                'label' => $this->__('Weight Value'),
                'title' => $this->__('Weight Value'),
                'value' => $this->formData['item_weight_custom_value'],
                'class' => 'input-text M2ePro-required-when-visible',
                'required' => true,
                'field_extra_attributes' => 'id="item_weight_custom_value_tr" style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'must_ship_alone_custom_attribute',
            'hidden',
            [
                'name' => 'must_ship_alone_custom_attribute',
                'value' => $this->formData['must_ship_alone_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['must_ship_alone_mode'] == SellingFormat::MUST_SHIP_ALONE_MODE_NONE) {
            $defaultValue = $this->formData['must_ship_alone_mode'];
        }

        $fieldset->addField(
            'must_ship_alone_mode',
            self::SELECT,
            [
                'name' => 'must_ship_alone_mode',
                'label' => $this->__('Must Ship Alone'),
                'title' => $this->__('Must Ship Alone'),
                'values' => $this->getMustShipAloneOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => $this->__(
                    'Specify whether the Item must be shipped alone or not. <br><br>
                     <strong>Note:</strong> If the option is enabled, Walmart order will be created for each
                     ordered item. It is because Walmart will recognize each item as a different shipment. For example,
                     if a buyer orders one product with a quantity of 3,
                     there will be 3 separate Walmart orders for each item.
                    '
                )
            ]
        )->addCustomAttribute('allowed_attribute_types', 'boolean');

        // ---------------------------------------

        $fieldset->addField(
            'ships_in_original_packaging_custom_attribute',
            'hidden',
            [
                'name' => 'ships_in_original_packaging_custom_attribute',
                'value' => $this->formData['ships_in_original_packaging_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['ships_in_original_packaging_mode'] == SellingFormat::SHIPS_IN_ORIGINAL_PACKAGING_MODE_NONE
        ) {
            $defaultValue = $this->formData['ships_in_original_packaging_mode'];
        }

        $fieldset->addField(
            'ships_in_original_packaging_mode',
            self::SELECT,
            [
                'name' => 'ships_in_original_packaging_mode',
                'label' => $this->__('Ships In Original Packaging'),
                'title' => $this->__('Ships In Original Packaging'),
                'values' => $this->getShipsInOriginalPackagingOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true,
                'tooltip' => $this->__('Specify whether the Item can be shipped in original
                packaging without being put in an outer box or not.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'boolean');

        // ---------------------------------------

        $fieldset->addField(
            'shipping_override_rule_mode',
            self::SELECT,
            [
                'name' => 'shipping_override_rule_mode',
                'label' => $this->__('Override Mode'),
                'class' => 'select-main',
                'values' => [
                    SellingFormat::SHIPPING_OVERRIDE_RULE_MODE_NO => $this->__('Disabled'),
                    SellingFormat::SHIPPING_OVERRIDE_RULE_MODE_YES => $this->__('Enabled')
                ],
                'value' => $this->formData['shipping_override_rule_mode'],
                'tooltip' => $this->__(
                    'Enable to add Shipping Overrides.<br>
                    <strong>Note:</strong> When you set an override for one shipping method,
                    the other shipping methods will<br>
                    still be taken from the global shipping settings in your Seller Center.'
                )
            ]
        );

        $fieldset->addField(
            'shipping_override_rule_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->getShippingOverrideRuleHtml($form),
                'css_class' => 'm2epro-fieldset-table',
                'field_extra_attributes' => 'id="shipping_override_rule_tr"'
            ]
        );

        // ---------------------------------------

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_selling_format_sale_time',
            [
                'legend' => $this->__('Offer Start/End Date'),
                'class' => 'm2epro-marketplace-depended-block',
                'collapsable' => false,
                'tooltip' => $this->__(
                    '
                        It is highly recommended to keep the default Offer Start/End Date values, i.e.
                        Immediate and Endless. Your offer will become visible on
                        Walmart as soon as it is in stock.<br><br>

                        Defining an offer start/end date may only be reasonable if you plan
                        to sell an Item during a certain period, e.g. start selling on
                        20th November and stop selling on 30th November. <br><br>

                        Read more details <a href="%url%" target="_blank">here</a>.',
                    $this->supportHelper->getDocumentationArticleUrl('x/cv1IB')
                )
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'sale_time_start_date_custom_attribute',
            'hidden',
            [
                'name' => 'sale_time_start_date_custom_attribute',
                'value' => $this->formData['sale_time_start_date_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['sale_time_start_date_mode'] == SellingFormat::DATE_NONE ||
            $this->formData['sale_time_start_date_mode'] == SellingFormat::DATE_VALUE
        ) {
            $defaultValue = $this->formData['sale_time_start_date_mode'];
        }

        $fieldset->addField(
            'sale_time_start_date_mode',
            self::SELECT,
            [
                'name' => 'sale_time_start_date_mode',
                'label' => $this->__('Start Date'),
                'title' => $this->__('Start Date'),
                'values' => $this->getSaleTimeStartDateOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,date');

        $fieldset->addField(
            'sale_time_start_date_value',
            'date',
            [
                'name' => 'sale_time_start_date_value',
                'container_id' => 'sale_time_start_date_value_tr',
                'label' => $this->__('Start Date Value'),
                'value' => $this->formData['sale_time_start_date_value'],
                'date_format' => $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT),
                'field_extra_attributes' => 'style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'sale_time_end_date_custom_attribute',
            'hidden',
            [
                'name' => 'sale_time_end_date_custom_attribute',
                'value' => $this->formData['sale_time_end_date_custom_attribute']
            ]
        );

        $defaultValue = '';
        if ($this->formData['sale_time_end_date_mode'] == SellingFormat::DATE_NONE ||
            $this->formData['sale_time_end_date_mode'] == SellingFormat::DATE_VALUE
        ) {
            $defaultValue = $this->formData['sale_time_end_date_mode'];
        }

        $fieldset->addField(
            'sale_time_end_date_mode',
            self::SELECT,
            [
                'name' => 'sale_time_end_date_mode',
                'label' => $this->__('End Date'),
                'title' => $this->__('End Date'),
                'values' => $this->getSaleTimeEndDateOptions(),
                'value' => $defaultValue,
                'class' => 'select',
                'create_magento_attribute' => true
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,date');

        $fieldset->addField(
            'sale_time_end_date_value',
            'date',
            [
                'name' => 'sale_time_end_date_value',
                'container_id' => 'sale_time_end_date_value_tr',
                'label' => $this->__('End Date Value'),
                'value' => $this->formData['sale_time_end_date_value'],
                'date_format' => $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT),
                'field_extra_attributes' => 'style="display: none;"',
            ]
        );

        // ---------------------------------------

        $fieldset = $form->addFieldset(
            'magento_block_walmart_template_selling_format_attributes',
            [
                'legend' => $this->__('Attributes'),
                'class' => 'm2epro-marketplace-depended-block',
                'collapsable' => false
            ]
        );

        // ---------------------------------------

        $fieldset->addField(
            'attributes_mode',
            self::SELECT,
            [
                'name' => 'attributes_mode',
                'label' => $this->__('Attributes'),
                'title' => $this->__('Attributes'),
                'values' => [
                    ['value' => SellingFormat::ATTRIBUTES_MODE_NONE, 'label' => $this->__('None')],
                    ['value' => SellingFormat::ATTRIBUTES_MODE_CUSTOM, 'label' => $this->__('Custom Value')],
                ],
                'value' => $this->formData['attributes_mode'],
                'tooltip' => $this->__('Specify up to 5 additional features that describe your Item.')
            ]
        );

        $this->appendAttributesFields($fieldset, 5, 'attributes');

        // ---------------------------------------

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    // ---------------------------------------

    public function getMarketplaceDataToOptions()
    {
        $optionsResult = [
            ['value' => '', 'label' => '', 'style' => 'display: none;']
        ];

        foreach ($this->walmartHelper->getMarketplacesAvailableForApiCreation() as $marketplace) {
            $optionsResult[] = [
                'value' => $marketplace->getId(),
                'label' => $this->escapeHtml($marketplace->getTitle())
            ];
        }

        return $optionsResult;
    }

    public function getQtyOptions()
    {
        $optionsResult = [
            [
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT,
                'label' => $this->__('Product Quantity')
            ],
            [
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_NUMBER,
                'label' => $this->__('Custom Value')
            ],
        ];

        $attributeOptions = $this->getAttributeOptions(
            \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_ATTRIBUTE,
            'qty_custom_attribute',
            'text'
        );

        $tmpOption = [
            'value' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED,
            'label' => $this->__('QTY')
        ];

        if ($this->formData['qty_mode'] == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED) {
            $tmpOption['attrs']['selected'] = 'selected';
        }

        array_unshift($attributeOptions[count($attributeOptions)-1]['value'], $tmpOption);

        return array_merge($optionsResult, $attributeOptions);
    }

    public function getPriceOptions()
    {
        $optionsResult = [
            [
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT,
                'label' => $this->__('Product Price')
            ],
            [
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL,
                'label' => $this->__('Special Price')
            ]
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE,
            'price_custom_attribute',
            'text_price'
        ));
    }

    public function getMapPriceOptions()
    {
        $optionsResult = [
            [
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE,
                'label' => $this->__('None')
            ],
            [
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT,
                'label' => $this->__('Product Price')
            ],
            [
                'value' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL,
                'label' => $this->__('Special Price')
            ]
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE,
            'map_price_custom_attribute',
            'text_price'
        ));
    }

    public function getLagTimeOptions()
    {
        $optionsResult = [
            [
                'value' => SellingFormat::LAG_TIME_MODE_RECOMMENDED,
                'label' => $this->__('Same Day'),
                'attrs' => ['attribute_code' => 0]
            ],
        ];

        $recommendedOptions = [
            'value' => [],
            'label' => 'Recommended Value'
        ];
        for ($i = 1; $i <= 30; $i++) {
            $option = [
                'value' => SellingFormat::LAG_TIME_MODE_RECOMMENDED,
                'label' => $this->__($i . ' day(s)'),
                'attrs' => ['attribute_code' => $i]
            ];

            if ($this->formData['lag_time_value'] == $i) {
                $option['attrs']['selected'] = 'selected';
            }

            $recommendedOptions['value'][] = $option;
        }

        $optionsResult[] = $recommendedOptions;

        return array_merge($optionsResult, $this->getAttributeOptions(
            SellingFormat::LAG_TIME_MODE_CUSTOM_ATTRIBUTE,
            'lag_time_custom_attribute',
            'text_select'
        ));
    }

    public function getProductTaxCodeOptions()
    {
        $optionsResult = [
            [
                'value' => SellingFormat::PRODUCT_TAX_CODE_MODE_VALUE,
                'label' => $this->__('Custom Value')
            ]
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            SellingFormat::PRODUCT_TAX_CODE_MODE_ATTRIBUTE,
            'product_tax_code_custom_attribute',
            'text_price'
        ));
    }

    public function getItemWeightModeOptions()
    {
        $optionsResult = [
            [
                'value' => SellingFormat::WEIGHT_MODE_CUSTOM_VALUE,
                'label' => $this->__('Custom Value')
            ]
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            SellingFormat::WEIGHT_MODE_CUSTOM_ATTRIBUTE,
            'item_weight_custom_attribute',
            'text_weight'
        ));
    }

    public function getMustShipAloneOptions()
    {
        $options = [
            [
                'value' => SellingFormat::MUST_SHIP_ALONE_MODE_YES,
                'label' => $this->__('Yes')
            ],
            [
                'value' => SellingFormat::MUST_SHIP_ALONE_MODE_NO,
                'label' => $this->__('No'),
            ],
        ];

        foreach ($options as $option) {
            if ($this->formData['must_ship_alone_mode'] == $option['value']) {
                $option['attrs']['selected'] = 'selected';
            }
        }

        $optionsResult = [
            [
                'value' => SellingFormat::MUST_SHIP_ALONE_MODE_NONE,
                'label' => $this->__('None')
            ],
            [
                'value' => $options,
                'label' => 'Custom Value'
            ]
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            SellingFormat::MUST_SHIP_ALONE_MODE_CUSTOM_ATTRIBUTE,
            'must_ship_alone_custom_attribute',
            'boolean'
        ));
    }

    public function getShipsInOriginalPackagingOptions()
    {
        $options = [
            [
                'value' => SellingFormat::SHIPS_IN_ORIGINAL_PACKAGING_MODE_NO,
                'label' => $this->__('Yes')
            ],
            [
                'value' => SellingFormat::MUST_SHIP_ALONE_MODE_NO,
                'label' => $this->__('No'),
            ],
        ];

        foreach ($options as $option) {
            if ($this->formData['ships_in_original_packaging_mode'] == $option['value']) {
                $option['attrs']['selected'] = 'selected';
            }
        }

        $optionsResult = [
            [
                'value' => SellingFormat::SHIPS_IN_ORIGINAL_PACKAGING_MODE_NONE,
                'label' => $this->__('None')
            ],
            [
                'value' => $options,
                'label' => 'Custom Value'
            ]
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            SellingFormat::SHIPS_IN_ORIGINAL_PACKAGING_MODE_CUSTOM_ATTRIBUTE,
            'ships_in_original_packaging_custom_attribute',
            'boolean'
        ));
    }

    public function getSaleTimeStartDateOptions()
    {
        $optionsResult = [
            [
                'value' => SellingFormat::DATE_NONE,
                'label' => $this->__('Immediate')
            ],
            [
                'value' => SellingFormat::DATE_VALUE,
                'label' => $this->__('Custom Value')
            ]
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            SellingFormat::DATE_ATTRIBUTE,
            'sale_time_start_date_custom_attribute',
            'text_date'
        ));
    }

    public function getSaleTimeEndDateOptions()
    {
        $optionsResult = [
            [
                'value' => SellingFormat::DATE_NONE,
                'label' => $this->__('Endless')
            ],
            [
                'value' => SellingFormat::DATE_VALUE,
                'label' => $this->__('Custom Value')
            ]
        ];

        return array_merge($optionsResult, $this->getAttributeOptions(
            SellingFormat::DATE_ATTRIBUTE,
            'sale_time_end_date_custom_attribute',
            'text_date'
        ));
    }

    public function getPromotionsHtml($form)
    {
        return $this->getLayout()->createBlock(Promotions::class)
                    ->setParentForm($form)
                    ->setAttributesByInputType('text_date', $this->allAttributesByInputTypes['text_date'])
                    ->setAttributesByInputType('text_price', $this->allAttributesByInputTypes['text_price'])
                    ->toHtml();
    }

    public function getShippingOverrideRuleHtml($form)
    {
        return $this->getLayout()->createBlock(ShippingOverrideRules::class)
                     ->setParentForm($form)
                     ->setAllAttributes($this->allAttributes)
                     ->toHtml();
    }

    public function appendAttributesFields(
        \Magento\Framework\Data\Form\Element\Fieldset $fieldSet,
        $fieldCount,
        $name
    ) {
        $helper = $this->dataHelper;
        for ($i = 0; $i < $fieldCount; $i++) {
            $value = '';
            if (!empty($this->formData[$name][$i]['name'])) {
                $value = $helper->escapeHtml($this->formData[$name][$i]['name']);
            }

            $nameBlock = $this->elementFactory->create(
                'text',
                [
                    'data' => [
                        'name'  => $name . '_name[]',
                        'value' => $value,
                        'onkeyup' => 'WalmartTemplateSellingFormatObj.multi_element_keyup(\'' . $name . '\',this)',
                        'class' => 'M2ePro-required-when-visible',
                        'css_class' => $name . '_tr no-margin-bottom',
                        'after_element_html' => ' /',
                    ]
                ]
            );
            $nameBlock->setId($name . '_name_' . $i);
            $nameBlock->setForm($fieldSet->getForm());

            $value = '';
            if (!empty($this->formData[$name][$i]['value'])) {
                $value = $helper->escapeHtml($this->formData[$name][$i]['value']);
            }

            $valueBlock = $this->elementFactory->create(
                'text',
                [
                    'data' => [
                        'name' => $name . '_value[]',
                        'value' => $value,
                        'onkeyup' => 'WalmartTemplateSellingFormatObj.multi_element_keyup(\'' . $name . '\',this)',
                        'class' => 'M2ePro-required-when-visible',
                        'css_class' => $name . '_tr no-margin-bottom',
                        'tooltip' => $this->__('Max. 100 characters.'),
                    ]
                ]
            );
            $valueBlock->setId($name . '_value_' . $i);
            $valueBlock->setForm($fieldSet->getForm());

            $button = $this->getLayout()
                           ->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button\MagentoAttribute::class)
                           ->addData([
                'label' => $this->__('Insert'),
                'destination_id' => $name . '_value_' . $i,
                'magento_attributes' => $this->getClearAttributesByInputTypesOptions(),
                'on_click_callback' => "WalmartTemplateSellingFormatObj.multi_element_keyup
                                        ('{$name}',$('{$name}_value_{$i}'));",
                'class' => 'primary attributes-container-td',
                'style' => 'display: inline-block; margin-left: 5px;',
            ]);

            $selectAttrBlock = $this->elementFactory->create(self::SELECT, [
                'data' => [
                    'values' => $this->getClearAttributesByInputTypesOptions('text_select'),
                    'class'  => 'M2ePro-required-when-visible magento-attribute-custom-input',
                    'create_magento_attribute' => true
                ]
            ])->addCustomAttribute('allowed_attribute_types', 'text,select')
                ->addCustomAttribute('apply_to_all_attribute_sets', 'false');

            $selectAttrBlock->setId('selectAttr_' . $name . '_value_' . $i);
            $selectAttrBlock->setForm($fieldSet->getForm());

            $fieldSet->addField(
                'attributes_container_' . $i,
                self::CUSTOM_CONTAINER,
                [   'container_id' => 'custom_title_tr',
                    'label' => $this->__('Attributes (name / value) #%number%', $i + 1),
                    'title' => $this->__('Attributes (name / value) #%number%', $i + 1),
                    'style' => 'display: inline-block;',
                    'text' => $nameBlock->toHtml() . $valueBlock->toHtml(),
                    'after_element_html' => $selectAttrBlock->toHtml() . $button->toHtml(),
                    'css_class' => 'attributes_tr',
                    'field_extra_attributes' => 'style="display: none;"'
                ]
            );
        }

        $fieldSet->addField(
            $name . '_actions',
            self::CUSTOM_CONTAINER,
            [
                'label' => '',
                'text' => <<<HTML
                <a id="show_{$name}_action"
                   href="javascript: void(0);"
                   onclick="WalmartTemplateSellingFormatObj.showElement('{$name}');">
                   {$this->__('Add New')}
                </a>
                        /
                <a id="hide_{$name}_action"
                   href="javascript: void(0);"
                   onclick="WalmartTemplateSellingFormatObj.hideElement('{$name}');">
                   {$this->__('Remove')}
                </a>
HTML
                ,
                'field_extra_attributes' => 'id="' . $name . '_actions_tr" style="display: none;"',
            ]
        );
    }

    protected function _beforeToHtml()
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Template\SellingFormat::class)
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Walmart\Template\SellingFormat::class)
        );

        $this->jsPhp->addConstants(
            $this->dataHelper
                ->getClassConstants(\Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion::class)
        );

        $this->jsPhp->addConstants(
            $this->dataHelper
                 ->getClassConstants(\Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride::class)
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Walmart::class)
        );

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Walmart_Template_SellingFormat'));
        $this->jsUrl->addUrls([
            'formSubmit'    => $this->getUrl(
                '*/walmart_template_sellingFormat/save',
                ['_current' => true]
            ),
            'formSubmitNew' => $this->getUrl('*/walmart_template_sellingFormat/save'),
            'deleteAction'  => $this->getUrl(
                '*/walmart_template_sellingFormat/delete',
                ['_current' => true]
            ),

            'm2epro_skin_url' => $this->getViewFileUrl('Ess_M2ePro')
        ]);

        $this->jsTranslator->addTranslations([
            'QTY' => $this->__('QTY'),
            'Price' => $this->__('Price'),

            'The Price, at which you want to sell your Product(s) at specific time.' => $this->__(
                'The Price, at which you want to sell your Product(s) at specific time.'
            ),
            'The Price, at which you want to sell your Product(s) at specific time.<br/>
            <b>Note:</b> The Final Price is only used for Simple Products.' => $this->__(
                'The Price, at which you want to sell your Product(s) at specific time.<br/>
                <b>Note:</b> The Final Price is only used for Simple Products.'
            ),

            'Add Selling Policy' => $this->__('Add Selling Policy'),
            'The specified Title is already used for other Policy. Policy Title must be unique.' => $this->__(
                'The specified Title is already used for other Policy. Policy Title must be unique.'
            ),
            'You should select Attribute Sets first and press Confirm Button.' => $this->__(
                'You should select Attribute Sets first and press Confirm Button.'
            ),
            'Coefficient is not valid.' => $this->__('Coefficient is not valid.'),
            'Date range is not valid.' => $this->__('Incorrect Promotion Dates.'),

            'Wrong value. Only integer numbers.' => $this->__('Wrong value. Only integer numbers.'),
            'wrong_value_more_than_30' => $this->__(
                'Wrong value. Must be no more than 30. Max applicable length is 6 characters,
                including the decimal (e.g., 12.345).'
            ),
            'At least one Selling Type should be enabled.' => $this->__('At least one Selling Type should be enabled.'),
            'The Quantity value should be unique.' => $this->__('The Quantity value should be unique.'),
            'You should specify a unique pair of Magento Attribute and Price Change value for each Discount Rule.' =>
                $this->__(
                    'You should specify a unique pair of Magento Attribute
                    and Price Change value for each Discount Rule.'
                ),
            'You should add at least one Discount Rule.' => $this->__('You should add at least one Discount Rule.'),
            'Any' => $this->__('Any'),
            'Add Shipping Override Policy.' => $this->__('Add Shipping Override Policy.'),
            'You should specify at least one Promotion.' => $this->__('You should specify at least one Promotion.'),
            'You should specify at least one Override Rule.' => $this->__(
                'You should specify at least one Override Rule.'
            ),
            'Must be a 7-digit code assigned to the taxable Items.' => $this->__(
                'Must be a 7-digit code assigned to the taxable Items.'
            ),
            'Sales Tax Codes' => $this->__('Sales Tax Codes'),
        ]);

        $formData = $this->dataHelper->jsonEncode($this->formData);
        $isEdit = $this->templateModel->getId() ? 'true' : 'false';
        $allAttributes = $this->dataHelper->jsonEncode($this->magentoAttributeHelper->getAll());
        $marketplacesWithTaxCodes = $this->dataHelper
                                         ->jsonEncode($this->getMarketplacesWithTaxCodesDictionary());

        $promotions = $this->dataHelper->jsonEncode($this->formData['promotions']);
        $shippingOverride = $this->dataHelper->jsonEncode($this->formData['shipping_override_rule']);

        $this->js->addRequireJs(
            [
            'jQuery'           => 'jquery',
            'attr'             => 'M2ePro/Attribute',
            'attribute_button' => 'M2ePro/Plugin/Magento/Attribute/Button',
            'sellingFormat'    => 'M2ePro/Walmart/Template/SellingFormat',
        ],
            <<<JS

        M2ePro.formData = {$formData};
        M2ePro.customData.marketplaces_with_tax_codes_dictionary = {$marketplacesWithTaxCodes};

        M2ePro.customData.is_edit = {$isEdit};

        if (typeof AttributeObj === 'undefined') {
            window.AttributeObj = new Attribute();
        }
        window.AttributeObj.setAvailableAttributes({$allAttributes});

        window.WalmartTemplateSellingFormatObj = new WalmartTemplateSellingFormat();
        window.MagentoAttributeButtonObj = new MagentoAttributeButton();

        jQuery(function() {
            WalmartTemplateSellingFormatObj.initObservers();

            if ({$isEdit}) {
                WalmartTemplateSellingFormatObj.renderPromotions({$promotions});
                WalmartTemplateSellingFormatObj.renderRules({$shippingOverride});
            }
        });
JS
        );

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        return '<div id="modal_dialog_message"></div>' . parent::_toHtml();
    }

    public function getMarketplacesWithTaxCodesDictionary()
    {
        $connRead = $this->resourceConnection->getConnection();

        $queryStmt = $connRead->select()
                              ->from(
                                  $this->databaseHelper
                                      ->getTableNameWithPrefix('m2epro_walmart_dictionary_marketplace'),
                                  ['marketplace_id']
                              )
                              ->where('`tax_codes` IS NOT NULL')
                              ->query();

        return (array)$queryStmt->fetchAll(\Zend_Db::FETCH_COLUMN);
    }

    public function getFormData()
    {
        $default = array_merge(
            $this->modelFactory->getObject('Walmart_Template_SellingFormat_Builder')->getDefaultData(),
            [
                'marketplace_id' => $this->getRequest()->getParam('marketplace_id', ''),
            ]
        );

        if (!$this->templateModel || !$this->templateModel->getId()) {
            return $default;
        }

        $data = array_merge(
            $this->templateModel->getData(),
            $this->templateModel->getChildObject()->getData()
        );

        $data['shipping_override_rule'] = $this->templateModel->getChildObject()->getShippingOverrides();
        $data['promotions'] = $this->templateModel->getChildObject()->getPromotions();
        $data['attributes'] = $this->dataHelper->jsonDecode($data['attributes']);

        return array_merge($default, $data);
    }

    protected function getAttributeOptions($attributeMode, $attributeName, $attributeType)
    {
        $optionsResult = [];

        $forceAddedAttributeOption = $this->getForceAddedAttributeOption(
            $this->formData[$attributeName],
            $this->allAttributesByInputTypes[$attributeType],
            $attributeMode
        );

        if ($forceAddedAttributeOption) {
            $optionsResult[] = $forceAddedAttributeOption;
        }

        $optionsResult[] = [
            'value' => $this->getAttributesByInputTypesOptions(
                $attributeMode,
                $attributeType,
                function ($attribute) use ($attributeName) {
                    return $attribute['code'] == $this->formData[$attributeName];
                }
            ),
            'label' => 'Magento Attribute',
            'attrs' => ['is_magento_attribute' => true]
        ];

        return $optionsResult;
    }

    public function getForceAddedAttributeOption($attributeCode, $availableValues, $value = null)
    {
        if (empty($attributeCode) ||
            $this->magentoAttributeHelper->isExistInAttributesArray($attributeCode, $availableValues)) {
            return '';
        }

        $attributeLabel = $this->dataHelper
                               ->escapeHtml($this->magentoAttributeHelper->getAttributeLabel($attributeCode));

        $result = ['value' => $value, 'label' => $attributeLabel];

        if ($value === null) {
            return $result;
        }

        $result['attrs'] = ['attrbiute_code' => $attributeCode];
        return $result;
    }

    public function getAttributesByInputTypesOptions($value, $attributeType, $conditionCallback = false)
    {
        if (!isset($this->allAttributesByInputTypes[$attributeType])) {
            return [];
        }

        $optionsResult = [];
        $helper = $this->dataHelper;

        foreach ($this->allAttributesByInputTypes[$attributeType] as $attribute) {
            $tmpOption = [
                'value' => $value,
                'label' => $helper->escapeHtml($attribute['label']),
                'attrs' => ['attribute_code' => $attribute['code']]
            ];

            if (is_callable($conditionCallback) && $conditionCallback($attribute)) {
                $tmpOption['attrs']['selected'] = 'selected';
            }

            $optionsResult[] = $tmpOption;
        }

        return $optionsResult;
    }

    public function getClearAttributesByInputTypesOptions()
    {
        $optionsResult = [];
        $helper = $this->dataHelper;

        foreach ($this->allAttributesByInputTypes['text_select'] as $attribute) {
            $optionsResult[] = [
                'value' => $attribute['code'],
                'label' => $helper->escapeHtml($attribute['label']),
            ];
        }

        return $optionsResult;
    }
}
