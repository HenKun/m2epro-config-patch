<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit
 */
class Edit extends AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $wizardHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param \Ess\M2ePro\Helper\Module\Wizard $wizardHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Module\Wizard $wizardHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->wizardHelper = $wizardHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonAccountEdit');
        $this->_controller = 'adminhtml_amazon_account';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        if ($this->wizardHelper->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK)) {
            // ---------------------------------------
            $this->addButton('save_and_continue', [
                'label'   => $this->__('Save And Continue Edit'),
                'onclick' => 'AmazonAccountObj.saveAndEditClick(\'\',\'amazonAccountEditTabs\')',
                'class'   => 'action-primary',
            ]);
            // ---------------------------------------

            if ($this->getRequest()->getParam('id')) {
                // ---------------------------------------
                $url = $this->getUrl('*/amazon_account/new', ['wizard' => true]);
                $this->addButton('add_new_account', [
                    'label'   => $this->__('Add New Account'),
                    'onclick' => 'setLocation(\'' . $url . '\')',
                    'class'   => 'action-primary',
                ]);
                // ---------------------------------------
            }
        } else {
            if ((bool)$this->getRequest()->getParam('close_on_save', false)) {
                if ($this->getRequest()->getParam('id')) {
                    $this->addButton('save', [
                        'label'   => $this->__('Save And Close'),
                        'onclick' => 'AmazonAccountObj.saveAndClose()',
                        'class'   => 'action-primary',
                    ]);
                } else {
                    $this->addButton('save_and_continue', [
                        'label'   => $this->__('Save And Continue Edit'),
                        'onclick' => 'AmazonAccountObj.saveAndEditClick(\'\',\'amazonAccountEditTabs\')',
                        'class'   => 'action-primary',
                    ]);
                }

                return;
            }

            // ---------------------------------------
            $url = $this->getUrl('*/amazon_account/index');
            $this->addButton('back', [
                'label'   => $this->__('Back'),
                'onclick' => 'AmazonAccountObj.backClick(\'' . $url . '\')',
                'class'   => 'back',
            ]);
            // ---------------------------------------

            // ---------------------------------------
            if (
                $this->globalDataHelper->getValue('edit_account')
                && $this->globalDataHelper->getValue('edit_account')->getId()
            ) {
                // ---------------------------------------
                $this->addButton('delete', [
                    'label'   => $this->__('Delete'),
                    'onclick' => 'AmazonAccountObj.deleteClick()',
                    'class'   => 'delete M2ePro_delete_button primary',
                ]);
                // ---------------------------------------
            }

            // ---------------------------------------
            $saveButtons = [
                'id'           => 'save_and_continue',
                'label'        => $this->__('Save And Continue Edit'),
                'class'        => 'add',
                'button_class' => '',
                'onclick'      => 'AmazonAccountObj.saveAndEditClick(\'\',\'amazonAccountEditTabs\')',
                'class_name'   => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options'      => [
                    'save' => [
                        'label'   => $this->__('Save And Back'),
                        'onclick' => 'AmazonAccountObj.saveClick()',
                        'class'   => 'action-primary',
                    ],
                ],
            ];

            $this->addButton('save_buttons', $saveButtons);
            // ---------------------------------------
        }
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->jsTranslator->addTranslations([
            'is_ready_for_document_generation' => $this->__(
                <<<HTML
    To use this option, go to <i>Stores > Configuration > General > General > Store Information</i> and fill in the
    following required fields:<br><br>
        <ul style="padding-left: 50px">
            <li>Store Name</li>
            <li>Country</li>
            <li>ZIP/Postal Code</li>
            <li>City</li>
            <li>Street Address</li>
        </ul>
HTML
                ,
                $this->supportHelper->getSupportUrl('/support/solutions/articles/9000219394')
            ),
        ]);

        return parent::_prepareLayout();
    }
}
