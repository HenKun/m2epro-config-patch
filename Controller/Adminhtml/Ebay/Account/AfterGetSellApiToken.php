<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\AfterGetToken
 */
class AfterGetSellApiToken extends Account
{
    /** @var \Ess\M2ePro\Model\Ebay\Account\TemporaryStorage */
    private $temporaryStorage;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Account\TemporaryStorage $temporaryStorage,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($storeCategoryUpdate, $componentEbayCategoryStore, $ebayFactory, $context);
        $this->temporaryStorage = $temporaryStorage;
    }

    public function execute()
    {
        // Get eBay session id
        // ---------------------------------------
        $sessionId = base64_decode((string)$this->getRequest()->getParam('code'));
        if ($sessionId === '') {
            $this->_redirect('*/*/index');
        }
        // ---------------------------------------

        // Get account form data
        // ---------------------------------------
        $this->temporaryStorage->setSellApiToken($sessionId);
        // ---------------------------------------

        // Goto account add or edit page
        // ---------------------------------------
        $accountId = (int)$this->temporaryStorage->getAccountId();

        if ($accountId === 0) {
            $this->_redirect('*/*/index');
        }

        $this->getMessageManager()->addSuccessMessage($this->__('Sell API token was obtained'));
        $this->_redirect('*/*/edit', ['id' => $accountId, '_current' => true]);
        // ---------------------------------------
    }
}
