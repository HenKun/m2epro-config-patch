<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Order;

use Ess\M2ePro\Controller\Adminhtml\Order;

class NoteGrid extends Order
{
    public function execute()
    {
        $grid = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Order\Note\Grid::class);
        $this->setAjaxContent($grid->toHtml());
        return $this->getResult();
    }
}
