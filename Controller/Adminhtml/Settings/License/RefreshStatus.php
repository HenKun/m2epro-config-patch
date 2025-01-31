<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\License;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Settings\License\RefreshStatus
 */
class RefreshStatus extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    public function execute()
    {
        try {
            $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
                \Ess\M2ePro\Model\Servicing\Task\License::NAME
            );
        } catch (\Exception $e) {
            $this->messageManager->addError(
                $this->__($e->getMessage())
            );

            $this->setJsonContent([
                'success' => false,
                'message' => $this->__($e->getMessage())
            ]);
            return $this->getResult();
        }

        $this->setJsonContent([
            'success' => true,
            'message' => $this->__('The License has been refreshed.')
        ]);
        return $this->getResult();
    }
}
