<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Synchronization;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

class Log extends AbstractContainer
{
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('synchronizationLog');
        $this->_controller = 'adminhtml_synchronization_log';
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $this->_headerText = '';
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        // Set template
        // ---------------------------------------
        $this->setTemplate('Ess_M2ePro::magento/grid/container/only_content.phtml');
        // ---------------------------------------
    }

    protected function _toHtml()
    {
        $helpBlock = $this
            ->getLayout()
            ->createBlock(
                \Ess\M2ePro\Block\Adminhtml\HelpBlock::class,
                '',
                [
                    'data' => [
                        'content' => $this->__(
                            'The Log includes information about synchronization of
                             M2E Pro Listings, Orders, Marketplaces, Unmanaged Listings.'
                        )
                    ]
                ]
            );

        return $helpBlock->toHtml() . parent::_toHtml();
    }
}
