<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

class Order extends AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_amazon_order';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $this->addButton(
            'upload_by_user',
            [
                'label'     => $this->__('Order Reimport'),
                'onclick'   => 'UploadByUserObj.openPopup()',
                'class'     => 'action-primary'
            ]
        );
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(
                <<<HTML
                <p>In this section, you can find the list of the Orders imported from Amazon.</p><br>

                <p>An Amazon Order, for which Magento Order is created, contains a value in
                <strong>Magento Order </strong>column of the grid. You can find the corresponding Magento Order
                in Sales > Orders section of your Magento.</p><br>
                <p>To manage the imported Amazon Orders, you can use Mass Action options available in the
                Actions bulk: Reserve QTY, Cancel QTY Reserve, Mark Order(s) as Shipped
                and Resend Shipping Information.</p><br>
                <p>Also, you can view the detailed Order information by clicking on the appropriate
                row of the grid.</p>
                <p><strong>Note:</strong> Automatic creation of Magento Orders, Invoices, and Shipments is
                performed in accordance with the Order settings specified in <br>
                <strong>Account Settings (Amazon Integration > Configuration > Accounts)</strong>.</p>
HTML
            )
        ]);

        $this->setPageActionsBlock('Amazon_Order_PageActions');

        return parent::_prepareLayout();
    }

    public function getGridHtml()
    {
        return $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Order\Item\Edit::class)->toHtml() .
               parent::getGridHtml();
    }

    protected function _beforeToHtml()
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Controller\Adminhtml\Order\EditItem::class)
        );

        $this->js->addRequireJs(['upload' => 'M2ePro/Order/UploadByUser'], <<<JS
UploadByUserObj = new UploadByUser('amazon', 'orderUploadByUserPopupGrid');
JS
        );

        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Order_UploadByUser')
        );

        $this->jsTranslator->addTranslations(
            [
                'Order Reimport'               => $this->__('Order Reimport'),
                'Order importing in progress.' => $this->__('Order importing in progress.'),
                'Order importing is canceled.' => $this->__('Order importing is canceled.')
            ]
        );

        return parent::_beforeToHtml();
    }

    //########################################
}
