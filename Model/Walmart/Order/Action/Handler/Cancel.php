<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order\Action\Handler;

use Ess\M2ePro\Model\Walmart\Order\Item as OrderItem;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order\Action\Handler\Cancel
 */
class Cancel extends \Ess\M2ePro\Model\Walmart\Order\Action\Handler\AbstractModel
{
    //########################################

    public function isNeedProcess()
    {
        if (!$this->getWalmartOrder()->isCreated() &&
            !$this->getWalmartOrder()->isUnshipped() &&
            !$this->getWalmartOrder()->isPartiallyShipped()) {
            return false;
        }

        $orderItemCollection = $this->getOrder()->getItemsCollection();
        $orderItemCollection->addFieldToFilter(
            'status',
            [
                'in' => [
                    OrderItem::STATUS_ACKNOWLEDGED,
                    OrderItem::STATUS_CREATED
                ]
            ]
        );

        if ($orderItemCollection->getSize() == 0) {
            return false;
        }

        return true;
    }

    //########################################

    protected function getServerCommand()
    {
        return ['orders', 'cancel', 'entity'];
    }

    protected function getRequestData()
    {
        $resultItems = [];
        $params = $this->orderChange->getParams();

        foreach ($params['items'] as $itemData) {
            /** @var \Ess\M2ePro\Model\Order\Item $orderItem */
            $orderItem = $this->walmartFactory->getObject('Order_Item')
                ->getCollection()
                ->addFieldToFilter('order_id', $this->getOrder()->getId())
                ->addFieldToFilter('walmart_order_item_id', $itemData['item_id'])
                ->getFirstItem();

            if ($orderItem->getId() !== null &&
                ($orderItem->getChildObject()->getStatus() != OrderItem::STATUS_ACKNOWLEDGED &&
                 $orderItem->getChildObject()->getStatus() != OrderItem::STATUS_CREATED)
            ) {
                continue;
            }

            $entry = [
                'number' => $itemData['item_id'],
                'qty'    => $itemData['qty'],
            ];
            if (!empty($itemData['is_buyer_cancellation'])) {
                $entry['is_buyer_cancellation'] = true;
            }

            $resultItems[] = $entry;
        }

        return [
            'channel_order_id' => $this->getWalmartOrder()->getWalmartOrderId(),
            'items'            => $resultItems,
        ];
    }

    protected function processResult(array $responseData)
    {
        if (!isset($responseData['result']) || !$responseData['result']) {
            $this->processError();
            return;
        }

        $itemsStatuses = [];
        $params = $this->orderChange->getParams();

        foreach ($params['items'] as $itemData) {
            /** @var \Ess\M2ePro\Model\Order\Item $orderItem */
            $orderItem = $this->walmartFactory->getObject('Order_Item')
                ->getCollection()
                ->addFieldToFilter('order_id', $this->getOrder()->getId())
                ->addFieldToFilter('walmart_order_item_id', $itemData['item_id'])
                ->getFirstItem();

            /**
             * Walmart returns the same Order Item more than one time with single QTY. That data was merged.
             * So walmart_order_item_id of real OrderItem and walmart_order_item_id in request may be different.
             * Real walmart_order_item_id will match with the ID in request when the last item will be cancelled.
             */
            if ($orderItem->getId()) {
                $orderItem->getChildObject()->setData('status', OrderItem::STATUS_CANCELLED)->save();
                $itemsStatuses[$itemData['item_id']] = OrderItem::STATUS_CANCELLED;
            }
        }

        foreach ($this->getOrder()->getItemsCollection() as $item) {
            if (!array_key_exists($item->getChildObject()->getData('walmart_order_item_id'), $itemsStatuses)) {
                $itemsStatuses[$item->getChildObject()->getData('walmart_order_item_id')] =
                    $item->getChildObject()->getData('status');
            }
        }

        $orderStatus = $this->modelFactory->getObject('Walmart_Order_Helper')->getOrderStatus($itemsStatuses);
        $this->getOrder()->getChildObject()->setData('status', $orderStatus);
        $this->getOrder()->getChildObject()->save();
        $this->getOrder()->save();

        $this->orderChange->delete();
        $this->getOrder()->addSuccessLog(
            $this->helperFactory->getObject('Module\Translation')->__('Order was cancelled.')
        );
    }

    /**
     * @param \Ess\M2ePro\Model\Connector\Connection\Response\Message[] $messages
     */
    protected function processError(array $messages = [])
    {
        if (empty($messages)) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                $this->helperFactory->getObject('Module\Translation')->__(
                    'Order was not cancelled due to Walmart error.'
                ),
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $messages = [$message];
        }

        foreach ($messages as $message) {
            $this->getOrder()->getLog()->addServerResponseMessage($this->getOrder(), $message);
        }
    }

    //########################################
}
