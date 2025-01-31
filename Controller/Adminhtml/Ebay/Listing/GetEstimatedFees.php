<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class GetEstimatedFees extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->exceptionHelper = $exceptionHelper;
    }

    public function execute()
    {
        // @codingStandardsIgnoreLine
        session_write_close();

        // ---------------------------------------
        $listingProductId = $this->getRequest()->getParam('listing_product_id');
        // ---------------------------------------

        if (empty($listingProductId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $listingProductId);
        // ---------------------------------------

        $params = [
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER,
            'logs_action_id' => $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getNextActionId()
        ];

        /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');

        /** @var \Ess\M2ePro\Model\Ebay\Connector\Item\Verify\Requester $connector */
        $connector = $dispatcher->getCustomConnector('Ebay_Connector_Item_Verify_Requester', $params);
        $connector->setListingProduct($listingProduct);

        $fees = [];
        try {
            $connector->process();
            $fees = $connector->getPreparedResponseData();
        } catch (\Exception $exception) {
            $this->exceptionHelper->process($exception);
        }

        if ($fees !== null) {
            foreach ($connector->getResponse()->getMessages()->getErrorEntities() as $errorMessage) {
                $connector->getLogger()->logListingProductMessage(
                    $listingProduct,
                    $errorMessage
                );
            }
        }

        $errors = $connector->getLogger()->getStoredMessages();

        // ---------------------------------------
        if (empty($fees)) {
            if (empty($errors)) {
                $this->setJsonContent(['error' => true]);
            } else {
                $errorsBlock = $this->getLayout()
                                    ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Ebay\Fee\Errors::class);
                $errorsBlock->setData('errors', $errors);

                $this->setJsonContent([
                    'title' => $this->__(
                        'Estimated Fee Details For Product: "%title%"',
                        $listingProduct->getMagentoProduct()->getName()
                    ),
                    'html' => $errorsBlock->toHtml()
                ]);
            }
            return $this->getResult();
        }
        // ---------------------------------------

        $details = $this->getLayout()
                        ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Ebay\Fee\Details::class);
        $details->setData('fees', $fees);
        $details->setData('product_name', $listingProduct->getMagentoProduct()->getName());

        $this->setJsonContent([
            'title' => $this->__(
                'Estimated Fee Details For Product: "%title%"',
                $listingProduct->getMagentoProduct()->getName()
            ),
            'html' => $details->toHtml()
        ]);
        return $this->getResult();
    }
}
