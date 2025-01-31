<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Repricing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Repricing\OpenShowDetails
 */
class OpenShowDetails extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Repricing */
    private $helperAmazonRepricing;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Repricing $helperAmazonRepricing,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperAmazonRepricing = $helperAmazonRepricing;
    }

    public function execute()
    {
        $listingId   = $this->getRequest()->getParam('id');
        $accountId   = $this->getRequest()->getParam('account_id');
        $productsIds = $this->getRequest()->getParam('products_ids');

        if (!is_array($productsIds)) {
            $productsIds = explode(',', $productsIds);
        }

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $accountId, null, false);

        if (!$account->getId()) {
            $this->getMessageManager()->addError($this->__('Account does not exist.'));
            return $this->_redirect($this->getUrl('*/amazon_listing/view', ['id' => $listingId]));
        }

        if (empty($productsIds)) {
            $this->getMessageManager()->addError($this->__('Products not selected.'));
            return $this->_redirect($this->getUrl('*/amazon_listing/view', ['id' => $listingId]));
        }

        $backUrl = $this->getUrl(
            '*/amazon_listing_product_repricing/showDetails',
            ['id' => $listingId, 'account_id' => $accountId]
        );

        /** @var \Ess\M2ePro\Model\Amazon\Repricing\Action\Product $repricingAction */
        $repricingAction = $this->modelFactory->getObject('Amazon_Repricing_Action_Product');
        $repricingAction->setAccount($account);
        $serverRequestToken = $repricingAction->sendShowProductsDetailsActionData($productsIds, $backUrl);

        if ($serverRequestToken === false) {
            $this->getMessageManager()->addError(
                $this->__('The selected Amazon Products cannot be Managed by Amazon Repricing Tool.')
            );
            return $this->_redirect($this->getUrl('*/amazon_listing/view', ['id' => $listingId]));
        }

        return $this->_redirect(
            $this->helperAmazonRepricing->prepareActionUrl(
                \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_OFFERS_DETAILS,
                $serverRequestToken
            )
        );
    }
}
