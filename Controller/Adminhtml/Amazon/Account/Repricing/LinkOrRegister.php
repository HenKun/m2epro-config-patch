<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account\Repricing;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Account\Repricing\LinkOrRegister
 */
class LinkOrRegister extends Account
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
        $accountId = $this->getRequest()->getParam('id');

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $accountId, null, false);

        if ($accountId && $account === null) {
            $this->getMessageManager()->addError($this->__('Account does not exist.'));
            return $this->_redirect('*/amazon_account/index');
        }

        $backUrl = $this->getUrl(
            '*/amazon_account_repricing/link',
            ['id' => $account->getId()]
        );

        /** @var \Ess\M2ePro\Model\Amazon\Repricing\Action\Account $repricingAction */
        $repricingAction = $this->modelFactory->getObject('Amazon_Repricing_Action_Account');
        $repricingAction->setAccount($account);
        $serverRequestToken = $repricingAction->sendLinkActionData($backUrl);

        if ($serverRequestToken === false) {
            $this->getMessageManager()->addError($this->__(
                'M2E Pro cannot to connect to the Amazon Repricing Service. Please try again later.'
            ));
            return $this->_redirect($this->getUrl('*/amazon_account/edit/', ['id' => $accountId]));
        }

        return $this->_redirect(
            $this->helperAmazonRepricing->prepareActionUrl(
                \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_ACCOUNT_LINK,
                $serverRequestToken
            )
        );
    }
}
