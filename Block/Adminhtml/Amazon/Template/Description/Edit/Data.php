<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Edit\Data
 */
class Data extends AbstractBlock
{
    protected $_template = 'template/2_column.phtml';

    protected function _prepareLayout()
    {
        $this->setChild('tabs', $this->getLayout()
                            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Edit\Tabs::class)
        );
        return parent::_prepareLayout();
    }
}
