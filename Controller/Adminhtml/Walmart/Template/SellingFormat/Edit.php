<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\SellingFormat;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template;

class Edit extends Template
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->globalData = $globalData;
        $this->dataHelper = $dataHelper;
    }

    public function execute()
    {
        $template = $this->walmartFactory->getObject('Template\SellingFormat');
        if ($id = $this->getRequest()->getParam('id')) {
            $template->load($id);
        }

        if (!$template->getId() && $id) {
            $this->messageManager->addError($this->__('Policy does not exist'));
            return $this->_redirect('*/walmart_template/index');
        }

        $this->globalData->setValue('tmp_template', $template);

        $headerTextEdit = $this->__('Edit Selling Policy');
        $headerTextAdd = $this->__('Add Selling Policy');

        if ($template !== null
            && $template->getId()
        ) {
            $headerText = $headerTextEdit;
            $headerText .= ' "'.$this->dataHelper->escapeHtml($template->getTitle()).'"';
        } else {
            $headerText = $headerTextAdd;
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Policies'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Selling Policies'));
        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);

        $this->setPageHelpLink('x/cv1IB');
        $this->addContent(
            $this->getLayout()
                 ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Template\SellingFormat\Edit::class)
        );

        return $this->getResultPage();
    }
}
