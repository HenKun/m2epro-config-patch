<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\GetGrid
 */
class GetGrid extends Account
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        if (empty($id)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        $response = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\Feedback\Grid::class)
                                      ->toHtml();

        $this->setAjaxContent($response);

        return $this->getResult();
    }
}
