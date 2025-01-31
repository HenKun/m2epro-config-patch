<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order\MerchantFulfillment;

use Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment\Configuration;
use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\MerchantFulfillment\GetPopup
 */
class GetPopup extends Order
{
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        if ($orderId === null) {
            return $this->getResponse()->setBody('You should specify order ID');
        }

        /** @var \Ess\M2ePro\Model\Order $order */
        $order = $this->amazonFactory->getObjectLoaded(
            'Order',
            $orderId
        );


        $orderFulfillmentData = $order->getChildObject()->getMerchantFulfillmentData();
        $orderItems = $order->getItemsCollection()->getItems();

        $responseData = [
            'status' => true,
            'html'   => ''
        ];

        if (!empty($orderFulfillmentData)) {
            $popUp = $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment\Information::class);

            $popUp->setData('fulfillment_details', $orderFulfillmentData);
            $popUp->setData('order_items', $orderItems);
            $popUp->setData('fulfillment_not_wizard', true);
        } elseif (!$order->getMarketplace()->getChildObject()->isMerchantFulfillmentAvailable()) {
            $popUp = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment\Message::class);
            $popUp->setData('message', 'marketplaceError');
            $responseData['status'] = false;
        } elseif ($order->getChildObject()->isFulfilledByAmazon()) {
            $popUp = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment\Message::class);
            $popUp->setData('message', 'fbaError');
            $responseData['status'] = false;
        } elseif ($order->getChildObject()->isCanceled() || $order->getChildObject()->isPending() ||
            $order->getChildObject()->isShipped()) {
            $popUp = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Order\MerchantFulfillment\Message::class);
            $popUp->setData('message', 'statusError');
            $responseData['status'] = false;
        } else {
            $popUp = $this->getLayout()->createBlock(
                Configuration::class,
                '',
                [
                    'data' => [
                        'order' => $order,
                        'order_items' => $orderItems,
                        'order_currency' => $order->getChildObject()->getCurrency(),
                        'declared_value' => $order->getChildObject()->getSubtotalPrice(),
                        'delivery_date_to' => $order->getChildObject()->getDeliveryDateTo()
                    ]
                ]
            );
        }

        $responseData['html'] = $popUp->toHtml();

        $this->setJsonContent($responseData);

        return $this->getResult();
    }
}
