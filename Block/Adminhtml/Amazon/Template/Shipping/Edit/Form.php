<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Shipping\Edit;

use Ess\M2ePro\Model\Amazon\Template\Shipping;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $formData;

    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    protected $magentoAttributeHelper;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->supportHelper = $supportHelper;
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'      => 'edit_form',
                    'method'  => 'post',
                    'action'  => $this->getUrl('*/*/save'),
                    'enctype' => 'multipart/form-data',
                    'class'   => 'admin__scope-old'
                ]
            ]
        );

        $formData = $this->getFormData();

        $attributes = $this->magentoAttributeHelper->getAll();
        $attributesByInputTypes = [
            'text_select' => $this->magentoAttributeHelper->filterByInputTypes($attributes, ['text', 'select'])
        ];

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_shipping_general',
            [
                'legend'      => $this->__('General'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name'     => 'title',
                'label'    => $this->__('Title'),
                'value'    => $formData['title'],
                'class'    => 'M2ePro-shipping-tpl-title',
                'tooltip'  => $this->__('Short meaningful Policy Title for your internal use.'),
                'required' => true,
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_shipping_channel',
            [
                'legend'      => $this->__('Channel'),
                'collapsable' => false
            ]
        );

        $preparedAttributes = [];
        foreach ($attributesByInputTypes['text_select'] as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($formData['template_name_mode'] == Shipping::TEMPLATE_NAME_ATTRIBUTE
                && $formData['template_name_attribute'] == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => Shipping::TEMPLATE_NAME_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'template_name_mode',
            self::SELECT,
            [
                'container_id'             => 'template_name_mode_tr',
                'label'                    => $this->__('Template Name'),
                'class'                    => 'select-main',
                'name'                     => 'template_name_mode',
                'values'                   => [
                    Shipping::TEMPLATE_NAME_VALUE => $this->__('Custom Value'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'create_magento_attribute' => true,
                'tooltip'                  => $this->__('Template Name which you would like to be used.')
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,select');

        $fieldset->addField(
            'template_name_attribute',
            'hidden',
            [
                'name' => 'template_name_attribute',
            ]
        );

        $fieldset->addField(
            'template_name_value',
            'text',
            [
                'container_id' => 'template_name_custom_value_tr',
                'label'        => $this->__('Template Name Value'),
                'name'         => 'template_name_value',
                'value'        => $formData['template_name_value'],
                'required'     => true
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock(
            [
                'content' => $this->__(
                    '
        The Shipping Policy allows to provide Shipping Settings for the Items being listed to Amazon.
        So you should provide a Channel Template Name which you would like to be used.<br />
        More detailed information about ability to work with this Page
        you can find <a target="_blank" href="%url%">here</a>',
                    $this->supportHelper->getDocumentationArticleUrl('x/6-0kB')
                )
            ]
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Amazon::class)
        );

        $this->jsPhp->addConstants(
            $this->dataHelper
                ->getClassConstants(\Ess\M2ePro\Model\Amazon\Template\Shipping::class)
        );

        $this->jsUrl->addUrls(
            [
                'formSubmit'    => $this->getUrl(
                    '*/amazon_template_shipping/save',
                    [
                        '_current'      => $this->getRequest()->getParam('id'),
                        'close_on_save' => $this->getRequest()->getParam('close_on_save')
                    ]
                ),
                'formSubmitNew' => $this->getUrl('*/amazon_template_shipping/save'),
                'deleteAction'  => $this->getUrl(
                    '*/amazon_template_shipping/delete',
                    [
                        'id'            => $this->getRequest()->getParam('id'),
                        'close_on_save' => $this->getRequest()->getParam('close_on_save')
                    ]
                )
            ]
        );

        $this->jsTranslator->addTranslations(
            [
                'Add Shipping Policy' => $this->__(
                    'Add Shipping Policy'
                ),

                'The specified Title is already used for other Policy. Policy Title must be unique.' =>
                    $this->__('The specified Title is already used for other Policy. Policy Title must be unique.'),
            ]
        );

        $formData = $this->getFormData();

        $title = $this->dataHelper->escapeJs($this->dataHelper->escapeHtml($formData['title']));

        $this->js->add(
            <<<JS
M2ePro.formData.id = '{$this->getRequest()->getParam('id')}';
M2ePro.formData.title = '{$title}';

require(['M2ePro/Amazon/Template/Shipping'], function() {
    window.AmazonTemplateShippingObj = new AmazonTemplateShipping();
    window.AmazonTemplateShippingObj.initObservers();
});
JS
        );

        return parent::_prepareLayout();
    }

    protected function getFormData()
    {
        if ($this->formData === null) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping $model */
            $model = $this->globalDataHelper->getValue('tmp_template');

            $this->formData = [];
            if ($model) {
                $this->formData = $model->toArray();
            }

            $default = $this->modelFactory->getObject('Amazon_Template_Shipping_Builder')->getDefaultData();

            $this->formData = array_merge($default, $this->formData);
        }

        return $this->formData;
    }
}
