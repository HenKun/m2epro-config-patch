<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

class ResubmitShippingInfo extends \Ess\M2ePro\Controller\Adminhtml\Order
{
    /** @var \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory */
    private $orderShipmentCollectionFactory;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $orderShipmentCollectionFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->orderShipmentCollectionFactory = $orderShipmentCollectionFactory;
    }

    public function execute()
    {
        $ids = $this->getRequestIds();

        $isFail = false;

        foreach ($ids as $id) {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->activeRecordFactory->getObjectLoaded('Order', $id);
            $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);

            $shipmentsCollection = $this->orderShipmentCollectionFactory->create();
            $shipmentsCollection->setOrderFilter($order->getMagentoOrderId());

            foreach ($shipmentsCollection->getItems() as $shipment) {
                /** @var \Magento\Sales\Model\Order\Shipment $shipment */
                if (!$shipment->getId()) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Order\Shipment\Handler $handler */
                $componentMode = ucfirst($order->getComponentMode());
                $handler = $this->modelFactory->getObject("{$componentMode}_Order_Shipment_Handler");
                $result  = $handler->handle($order, $shipment);

                if ($result == \Ess\M2ePro\Model\Order\Shipment\Handler::HANDLE_RESULT_FAILED) {
                    $isFail = true;
                }
            }
        }

        if ($isFail) {
            $errorMessage = $this->__('Shipping Information was not resend.');
            if (count($ids) > 1) {
                $errorMessage = $this->__('Shipping Information was not resend for some Orders.');
            }

            $this->messageManager->addError($errorMessage);
        } else {
            $this->messageManager->addSuccess(
                $this->__('Shipping Information has been resend.')
            );
        }

        return $this->_redirect($this->redirect->getRefererUrl());
    }
}
