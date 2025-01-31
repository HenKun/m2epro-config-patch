<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Sections\License;

class Change extends \Ess\M2ePro\Block\Adminhtml\System\Config\Sections
{
    /** @var \Ess\M2ePro\Helper\Module\License */
    private $licenseHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\License $licenseHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\License $licenseHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);

        $this->licenseHelper = $licenseHelper;
        $this->dataHelper = $dataHelper;
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'     => 'edit_form',
                    'method' => 'post',
                    'action' => 'javascript:void(0)'
                ]
            ]
        );

        $fieldSet = $form->addFieldset('change_license', ['legend' => '', 'collapsable' => false]);

        $key = $this->dataHelper->escapeHtml($this->licenseHelper->getKey());
        $fieldSet->addField(
            'new_license_key',
            'text',
            [
                'name'     => 'new_license_key',
                'label'    => $this->__('New License Key'),
                'title'    => $this->__('New License Key'),
                'value'    => $key,
                'required' => true,
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
