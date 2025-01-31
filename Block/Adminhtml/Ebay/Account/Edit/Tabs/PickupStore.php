<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class PickupStore extends AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $account = $this->globalDataHelper->getValue('edit_account');
        $additionalData = $this->dataHelper->jsonDecode($account->getData('additional_data'));

        $form = $this->_formFactory->create();

        $form->addField(
            'help_block_pickup_store_mode',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    '<p>The In-Store Pickup is the Service which offers Buyers an opportunity to save on
                    shipping costs and pick up the Items they purchased on eBay in particular local Stores.
                    It is available for 3 Marketplaces - United States, United Kingdom and Australia.</p><br>
                    <p>You should select Enabled value for the Store Management option if you would like to
                    enable this Service for your Account. Once it is done, the My Stores link will become
                    available in the Accounts grid and Assign Products to Stores button will become available
                    in the Listing created from this Account for according marketplace.</p>'
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
            'pickup_store_mode',
            'select',
            [
                'html_id' => 'pickup_store_mode',
                'name' => 'pickup_store_mode',
                'label' => $this->__('Store Management'),
                'values' => [
                    1 => $this->__('Enabled'),
                    0 => $this->__('Disabled'),
                ],
                'value' => (int)!empty($additionalData['bopis']),
                'tooltip' => $this->__(
                    '<p>You should select Enabled value for the Store Management option if you would like
                    to enable this Service for your Account.</p>
                    <p>Once it is done, the My Stores link will
                    become available in the Accounts grid and Assign Products to Stores button will become
                    available in the Listing created from this Account for according marketplace.</p>'
                )
            ]
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}
