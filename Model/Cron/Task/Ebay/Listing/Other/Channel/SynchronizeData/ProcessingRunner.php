<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\Channel\SynchronizeData;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other\Channel\SynchronizeData\ProcessingRunner
 */
class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Partial\Runner
{
    const MAX_LIFETIME = 90720;
    const PENDING_REQUEST_MAX_LIFE_TIME = 86400;

    const LOCK_ITEM_PREFIX = 'synchronization_ebay_other_listings_update';

    //##################################

    protected function setLocks()
    {
        parent::setLocks();

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItem */
        $lockItem = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => self::LOCK_ITEM_PREFIX.'_'. $params['account_id']
        ]);
        $lockItem->create();

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Account',
            $params['account_id']
        );

        $account->addProcessingLock(null, $this->getProcessingObject()->getId());
        $account->addProcessingLock('synchronization', $this->getProcessingObject()->getId());
        $account->addProcessingLock('synchronization_ebay', $this->getProcessingObject()->getId());
        $account->addProcessingLock(self::LOCK_ITEM_PREFIX, $this->getProcessingObject()->getId());
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\Lock\Item\Manager $lockItem */
        $lockItem = $this->modelFactory->getObject('Lock_Item_Manager', [
            'nick' => self::LOCK_ITEM_PREFIX.'_'. $params['account_id']
        ]);
        $lockItem->remove();

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Account',
            $params['account_id']
        );

        $account->deleteProcessingLocks(null, $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks('synchronization', $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks('synchronization_ebay', $this->getProcessingObject()->getId());
        $account->deleteProcessingLocks(self::LOCK_ITEM_PREFIX, $this->getProcessingObject()->getId());
    }

    //##################################
}
