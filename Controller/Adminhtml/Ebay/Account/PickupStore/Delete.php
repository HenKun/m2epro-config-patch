<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore\Delete
 */
class Delete extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($storeCategoryUpdate, $componentEbayCategoryStore, $ebayFactory, $context);

        $this->helperException = $helperException;
    }

    //########################################

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id', 0);

        if (!$id) {
            $this->getMessageManager()->addErrorMessage(
                $this->__('Store does not exist.')
            );
            return $this->_redirect('*/*/index');
        }

        /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Ebay_Account_PickupStore', $id);
        $params = ['account_id' => $model->getAccountId()];

        if (!$model->getId()) {
            $this->getMessageManager()->addErrorMessage($this->__('Store does not exists.'));
            return $this->_redirect('*/ebay_account_pickupStore/index', $params);
        }

        if ($model->isLocked()) {
            $this->getMessageManager()->addErrorMessage($this->__('Store used in Listing.'));
            return $this->_redirect('*/ebay_account_pickupStore/index', $params);
        }

        try {
            $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'store',
                'delete',
                'entity',
                [
                    'location_id' => $model->getLocationId()
                ],
                null,
                null,
                $model->getAccountId()
            );

            $dispatcherObject->process($connectorObj);
        } catch (\Exception $exception) {
            $this->helperException->process($exception);

            $this->getMessageManager()->addErrorMessage($this->__(
                'The Store has not been deleted. Reason: %error_message%',
                $exception->getMessage()
            ));

            return $this->_redirect('*/ebay_account_pickupStore/index', $params);
        }

        $model->delete();

        $this->getMessageManager()->addSuccessMessage($this->__(
            'Store was deleted.'
        ));

        return $this->_redirect('*/ebay_account_pickupStore/index', $params);
    }

    //########################################
}
