<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Tabs;

use Ess\M2ePro\Model\Ebay\Template\Synchronization;
use Ess\M2ePro\Model\Template\Synchronization as TemplateSynchronization;

class ListRules extends AbstractTab
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        parent::__construct($globalDataHelper, $context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $default = $this->modelFactory->getObject('Ebay_Template_Synchronization_Builder')->getDefaultData();
        $formData = $this->getFormData();

        $formData = array_merge($default, $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'synchronization_id',
            'hidden',
            [
                'name' => 'synchronization[id]',
                'value' => (!$this->isCustom() && isset($formData['id'])) ? (int)$formData['id'] : ''
            ]
        );

        $form->addField(
            'synchronization_title',
            'hidden',
            [
                'name' => 'synchronization[title]',
                'value' => $this->getTitle()
            ]
        );

        $form->addField(
            'synchronization_is_custom_template',
            'hidden',
            [
                'name' => 'synchronization[is_custom_template]',
                'value' => $this->isCustom() ? 1 : 0
            ]
        );

        $form->addField(
            'ebay_template_synchronization_form_data_list',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
                    <p><strong>List Action</strong> - this Action can be executed for each Item in M2E Pro Listings
                    which has <strong>Not Listed</strong> Status and which Settings meet the List Condition. If an
                    Item was not initially Listed for some reason, automatic synchronization will attempt to list
                    it again only if there is a change of Product Status, Stock Availability or Quantity
                    in Magento.</p><br>

                    <p><strong>Note:</strong> M2E Pro Listings Synchronization must be enabled in
                    Synchronization <strong>(eBay Integration > Configuration > Settings > Synchronization)</strong>.
                    Otherwise, Synchronization Policy Rules will not take effect.</p><br>

                    <p>More detailed information about how to work with this Page you can find
                    <a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
                    ,
                    $this->supportHelper->getDocumentationArticleUrl('x/Zf8UB')
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_synchronization_form_data_list',
            [
                'legend' => $this->__('General'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'list_mode',
            self::SELECT,
            [
                'name' => 'synchronization[list_mode]',
                'label' => $this->__('List Action'),
                'value' => $formData['list_mode'],
                'values' => [
                    0 => $this->__('Disabled'),
                    1 => $this->__('Enabled'),
                ]
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_synchronization_form_data_list_rules',
            [
                'legend' => $this->__('List Conditions'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'list_status_enabled',
            self::SELECT,
            [
                'name' => 'synchronization[list_status_enabled]',
                'label' => $this->__('Product Status'),
                'value' => $formData['list_status_enabled'],
                'values' => [
                    0 => $this->__('Any'),
                    1 => $this->__('Enabled'),
                ],
                'tooltip' => $this->__(
                    '<p><strong>Enabled:</strong> List Items on eBay automatically if they have status Enabled
                    in Magento Product. (Recommended)</p>
                    <p><strong>Any:</strong> List Items with any Magento Product status on eBay automatically</p>'
                )
            ]
        );

        $fieldset->addField(
            'list_is_in_stock',
            self::SELECT,
            [
                'name' => 'synchronization[list_is_in_stock]',
                'label' => $this->__('Stock Availability'),
                'value' => $formData['list_is_in_stock'],
                'values' => [
                    0 => $this->__('Any'),
                    1 => $this->__('In Stock'),
                ],
                'tooltip' => $this->__(
                    '<b>In Stock:</b> List Items automatically if Products are in Stock. (Recommended.)<br/>
                    <b>Any:</b> List Items automatically, regardless of Stock availability.'
                )
            ]
        );

        $fieldset->addField(
            'list_qty_calculated',
            self::SELECT,
            [
                'name' => 'synchronization[list_qty_calculated]',
                'label' => $this->__('Quantity'),
                'value' => $formData['list_qty_calculated'],
                'values' => [
                    TemplateSynchronization::QTY_MODE_NONE => $this->__('Any'),
                    TemplateSynchronization::QTY_MODE_YES => $this->__('More or Equal'),
                ],
                'tooltip' => $this->__(
                    '<p><strong>Any:</strong> List Items automatically with any Quantity available.</p>
                    <p><strong>More or Equal:</strong> List Items automatically if the Quantity
                    according to the Selling Policy is at least equal to the number you set.
                    (Recommended)</p>'
                )
            ]
        )->setAfterElementHtml(<<<HTML
<input name="synchronization[list_qty_calculated_value]" id="list_qty_calculated_value"
       value="{$formData['list_qty_calculated_value']}" type="text"
       style="width: 72px; margin-left: 10px;"
       class="input-text admin__control-text required-entry validate-digits _required" />
HTML
            );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_template_synchronization_list_advanced_filters',
            [
                'legend' => $this->__('Advanced Conditions'),
                'collapsable' => false,
                'tooltip' => $this->__(
                    '<p>Define Magento Attribute value(s) based on which a product must be listed on the Channel.<br>
                    Once both List Conditions and Advanced Conditions are met, the product will be listed.</p>'
                )
            ]
        );

        $fieldset->addField(
            'list_advanced_rules_filters_warning',
            self::MESSAGES,
            [
                'messages' => [[
                    'type' => \Magento\Framework\Message\MessageInterface::TYPE_WARNING,
                    'content' => $this->__(
                        'Please be very thoughtful before enabling this option as this functionality
                        can have a negative impact on the Performance of your system.<br> It can decrease the speed
                        of running in case you have a lot of Products with the high number of changes made to them.'
                    )
                ]]
            ]
        );

        $fieldset->addField(
            'list_advanced_rules_mode',
            self::SELECT,
            [
                'name' => 'synchronization[list_advanced_rules_mode]',
                'label' => $this->__('Mode'),
                'value' => $formData['list_advanced_rules_mode'],
                'values' => [
                    0 => $this->__('Disabled'),
                    1 => $this->__('Enabled'),
                ],
            ]
        );

        $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule')->setData(
            ['prefix' => Synchronization::LIST_ADVANCED_RULES_PREFIX]
        );

        if (!empty($formData['list_advanced_rules_filters'])) {
            $ruleModel->loadFromSerialized($formData['list_advanced_rules_filters']);
        }

        $ruleBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule::class)
                                       ->setData(['rule_model' => $ruleModel]);

        $fieldset->addField(
            'advanced_filter',
            self::CUSTOM_CONTAINER,
            [
                'container_id' => 'list_advanced_rules_filters_container',
                'label'        => $this->__('Conditions'),
                'text'         => $ruleBlock->toHtml(),
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
