<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay;

class Category extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('ebayCategory');
        $this->_controller = 'adminhtml_ebay_category';

        $this->_headerText = '';

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $this->setTemplate('Ess_M2ePro::magento/grid/container/only_content.phtml');
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'content' => $this->__(<<<HTML
This page shows eBay Categories that are currently used in your M2E Pro Listings.<br/><br/>
You can see Category Status in a grid:<br/><br/>
<strong>Active</strong> —  category is currently available on eBay<br/>
<strong>Removed</strong> —  category was removed by eBay<br/><br/>
Any changes you make on this page will affect M2E Pro Listings where these Categories are used.
Read the <a href="%url%" target="_blank">article</a> to learn how to manage eBay Categories.
HTML
                ,
                $this->supportHelper->getDocumentationArticleUrl('x/PX5qB')
            )
        ]);

        return parent::_prepareLayout();
    }

    //########################################
}
