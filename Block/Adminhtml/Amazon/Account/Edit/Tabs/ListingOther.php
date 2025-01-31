<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

use Ess\M2ePro\Model\Amazon\Account;

class ListingOther extends AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    protected $magentoAttributeHelper;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->supportHelper = $supportHelper;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $allAttributes = $this->magentoAttributeHelper->getAll();

        $attributes = $this->magentoAttributeHelper->filterByInputTypes(
            $allAttributes,
            [
                'text', 'textarea', 'select'
            ]
        );

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->globalDataHelper->getValue('edit_account');
        $formData = $account !== null ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        if (isset($formData['other_listings_mapping_settings'])) {
            $formData['other_listings_mapping_settings'] = (array)\Ess\M2ePro\Helper\Json::decode(
                $formData['other_listings_mapping_settings']
            );
        }

        $defaults = $this->modelFactory->getObject('Amazon_Account_Builder')->getDefaultData();

        $formData = array_merge($defaults, $formData);

        $form->addField(
            'amazon_accounts_other_listings',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
<p>Under this tab you can manage the Unmanaged Listings - Items that were listed directly via your Seller Central
Account or via some other Unmanaged software. Specify whether you would like to import the Unmanaged Listings,
configure the automatic linking and moving settings.</p><br>
<p>More detailed information you can find <a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
                    ,
                    $this->supportHelper->getDocumentationArticleUrl('x/KP8UB')
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'general',
            [
                'legend' => $this->__('General'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'other_listings_synchronization',
            'select',
            [
                'name' => 'other_listings_synchronization',
                'label' => $this->__('Import Unmanaged Listings'),
                'values' => [
                    1 => $this->__('Yes'),
                    0 => $this->__('No'),
                ],
                'value' => $formData['other_listings_synchronization'],
                'tooltip' => $this->__('Allows importing Unmanaged Listings.')
            ]
        );

        $fieldset->addField(
            'related_store_id',
            self::STORE_SWITCHER,
            [
                'container_id' => 'other_listings_store_view_tr',
                'name' => 'related_store_id',
                'label' => $this->__('Related Store View'),
                'value' => $formData['related_store_id'],
                'tooltip' => $this->__(
                    'Store View, which will be associated with chosen Marketplace of the current Account.'
                )
            ]
        );

        $fieldset->addField(
            'other_listings_mapping_mode',
            'select',
            [
                'container_id' => 'other_listings_mapping_mode_tr',
                'name' => 'other_listings_mapping_mode',
                'class' => 'M2ePro-require-select-attribute',
                'label' => $this->__('Product Linking'),
                'values' => [
                    1 => $this->__('Yes'),
                    0 => $this->__('No'),
                ],
                'value' => $formData['other_listings_mapping_mode'],
                'tooltip' => $this->__(
                    'Choose whether imported Amazon Listings should automatically link to a
                    Product in your Magento Inventory.'
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_accounts_other_listings_product_mapping',
            [
                'legend' => $this->__('Magento Product Linking Settings'),
                'collapsable' => false,
                'tooltip' => $this->__(
                    '<p>In this section you can provide settings for automatic Linking of the newly imported
                    Unmanaged Listings to the appropriate Magento Products. </p><br>
                    <p>The imported Items are linked based on the correspondence between Amazon Item values and
                    Magento Product Attribute values. </p>'
                )
            ]
        );

        $mappingSettings = $formData['other_listings_mapping_settings'];

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (isset($mappingSettings['sku']['mode'])
                && $mappingSettings['sku']['mode'] == Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE
                && $mappingSettings['sku']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $mappingSkuPriority = isset($mappingSettings['sku']['priority'])
            ? (int)$mappingSettings['sku']['priority'] : Account::OTHER_LISTINGS_MAPPING_SKU_DEFAULT_PRIORITY;
        $fieldset->addField(
            'mapping_sku_mode',
            self::SELECT,
            [
                'name' => 'mapping_sku_mode',
                'label' => $this->__('SKU'),
                'class' => 'attribute-mode-select',
                'style' => 'float:left; margin-right: 15px;',
                'values' => [
                    Account::OTHER_LISTINGS_MAPPING_SKU_MODE_NONE => $this->__('None'),
                    Account::OTHER_LISTINGS_MAPPING_SKU_MODE_DEFAULT => $this->__('Product SKU'),
                    Account::OTHER_LISTINGS_MAPPING_SKU_MODE_PRODUCT_ID => $this->__('Product ID'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => isset($mappingSettings['sku']['mode'])
                    && $mappingSettings['sku']['mode'] != Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE
                    ? $mappingSettings['sku']['mode'] : '',
                'create_magento_attribute' => true,
            ]
        )->setAfterElementHtml(<<<HTML
<div id="mapping_sku_priority_td">
    {$this->__('Priority')}: <input style="width: 50px;"
                                    name="mapping_sku_priority"
                                    value="$mappingSkuPriority"
                                    type="text"
                                    class="input-text admin__control-text required-entry _required">
</div>
HTML
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select');

        $fieldset->addField(
            'mapping_sku_attribute',
            'hidden',
            [
                'name' => 'mapping_sku_attribute',
                'value' => isset($mappingSettings['sku']['attribute']) ? $mappingSettings['sku']['attribute'] : '',
            ]
        );

        $modeCustomAttribute = Account::OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_CUSTOM_ATTRIBUTE;

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (isset($mappingSettings['general_id']['mode'])
                && $mappingSettings['general_id']['mode'] == $modeCustomAttribute
                && $mappingSettings['general_id']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Account::OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $mappingGeneralIdPriority = isset($mappingSettings['general_id']['priority'])
            ? (int)$mappingSettings['general_id']['priority']
            : Account::OTHER_LISTINGS_MAPPING_GENERAL_ID_DEFAULT_PRIORITY;
        $fieldset->addField(
            'mapping_general_id_mode',
            self::SELECT,
            [
                'name' => 'mapping_general_id_mode',
                'label' => $this->__('ASIN / ISBN'),
                'class' => 'attribute-mode-select',
                'style' => 'float:left; margin-right: 15px;',
                'values' => [
                    Account::OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_NONE => $this->__('None'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => isset($mappingSettings['general_id']['mode'])
                    && $mappingSettings['general_id']['mode'] != $modeCustomAttribute
                    ? $mappingSettings['general_id']['mode'] : '',
                'create_magento_attribute' => true,
            ]
        )->setAfterElementHtml(<<<HTML
<div id="mapping_general_id_priority_td">
    {$this->__('Priority')}: <input style="width: 50px;"
                                    name="mapping_general_id_priority"
                                    value="$mappingGeneralIdPriority"
                                    type="text"
                                    class="input-text admin__control-text required-entry _required">
</div>
HTML
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select');

        $fieldset->addField(
            'mapping_general_id_attribute',
            'hidden',
            [
                'name' => 'mapping_general_id_attribute',
                'value' => isset($mappingSettings['general_id']['attribute'])
                    ? $mappingSettings['general_id']['attribute'] : ''
            ]
        );

        $preparedAttributes = [];
        foreach ($attributes as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if (isset($mappingSettings['title']['mode'])
                && $mappingSettings['title']['mode'] == Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE
                && $mappingSettings['title']['attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $mappingTitlePriority = isset($mappingSettings['title']['priority'])
            ? (int)$mappingSettings['title']['priority'] : Account::OTHER_LISTINGS_MAPPING_TITLE_DEFAULT_PRIORITY;
        $fieldset->addField(
            'mapping_title_mode',
            self::SELECT,
            [
                'name' => 'mapping_title_mode',
                'label' => $this->__('Listing Title'),
                'class' => 'attribute-mode-select',
                'style' => 'float:left; margin-right: 15px;',
                'values' => [
                    Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_NONE => $this->__('None'),
                    Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_DEFAULT => $this->__('Product Name'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => isset($mappingSettings['title']['mode'])
                    && $mappingSettings['title']['mode'] != Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE
                    ? $mappingSettings['title']['mode'] : '',
                'create_magento_attribute' => true,
            ]
        )->setAfterElementHtml(<<<HTML
<div id="mapping_title_priority_td">
    {$this->__('Priority')}: <input style="width: 50px;"
                                    name="mapping_title_priority"
                                    value="$mappingTitlePriority"
                                    type="text"
                                    class="input-text admin__control-text required-entry _required">
</div>
HTML
        )->addCustomAttribute('allowed_attribute_types', 'text,textarea,select');

        $fieldset->addField(
            'mapping_title_attribute',
            'hidden',
            [
                'name' => 'mapping_title_attribute',
                'value' => isset($mappingSettings['title']['attribute']) ? $mappingSettings['title']['attribute'] : '',
            ]
        );

        $this->setForm($form);

        $this->jsTranslator->add(
            'If Yes is chosen, you must select at least one Attribute for Product Linking.',
            $this->__('If Yes is chosen, you must select at least one Attribute for Product Linking.')
        );

        return parent::_prepareForm();
    }
}
