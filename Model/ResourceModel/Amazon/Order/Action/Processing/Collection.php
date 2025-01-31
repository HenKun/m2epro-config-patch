<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Order\Action\Processing;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Amazon\Order\Action\Processing::class,
            \Ess\M2ePro\Model\ResourceModel\Amazon\Order\Action\Processing::class
        );
    }

    //########################################

    public function setRequestPendingSingleIdFilter($requestPendingSingleIds)
    {
        if (!is_array($requestPendingSingleIds)) {
            $requestPendingSingleIds = [$requestPendingSingleIds];
        }

        $this->addFieldToFilter('request_pending_single_id', ['in' => $requestPendingSingleIds]);
        return $this;
    }

    public function setNotProcessedFilter()
    {
        $this->addFieldToFilter('request_pending_single_id', ['null' => true]);
        return $this;
    }

    public function setInProgressFilter()
    {
        $this->addFieldToFilter('request_pending_single_id', ['notnull' => true]);
        return $this;
    }

    //########################################
}
