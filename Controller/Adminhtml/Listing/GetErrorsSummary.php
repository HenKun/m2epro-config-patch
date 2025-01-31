<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing;

use Ess\M2ePro\Controller\Adminhtml\Listing;

class GetErrorsSummary extends Listing
{
    public function execute()
    {
        $blockParams = [
            'action_ids' => $this->getRequest()->getParam('action_ids'),
            'table_name' => $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getMainTable(),
            'type_log'   => 'listing'
        ];
        $block = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\Log\ErrorsSummary::class,
            '',
            ['data' => $blockParams]
        );
        $this->setAjaxContent($block);

        return $this->getResult();
    }
}
