<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add;

use Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode as SourceModeBlock;

class Review extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected $source;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingProductReview');
        // ---------------------------------------

        $this->setTemplate('walmart/listing/product/add/review.phtml');
    }

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------

        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->globalDataHelper->getValue('listing_for_products_add');

        $viewHeaderBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Listing\View\Header::class,
            '',
            ['data' => ['listing' => $listing]]
        );

        $this->setChild('view_header', $viewHeaderBlock);

        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/*/viewListing', [
            '_current' => true,
            'id' => $this->getRequest()->getParam('id')
        ]);

        $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
            ->setData([
                'id'   => $this->__('go_to_the_listing'),
                'label'   => $this->__('Go To The Listing'),
                'onclick' => 'setLocation(\''.$url.'\');',
                'class' => 'action primary'
            ]);
        $this->setChild('review', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        $url = $this->getUrl('*/*/viewListingAndList', [
            '_current' => true,
            'id' => $this->getRequest()->getParam('id')
        ]);

        $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
            ->setData([
                'label'   => $this->__('List Added Products Now'),
                'onclick' => 'setLocation(\''.$url.'\');',
                'class' => 'action primary'
            ]);
        $this->setChild('list', $buttonBlock);
        // ---------------------------------------

        // ---------------------------------------
        if ($this->getSource() === SourceModeBlock::MODE_OTHER) {
            $url = $this->getUrl('*/walmart_listing_other/view', [
                'account'     => $listing->getAccountId(),
                'marketplace' => $listing->getMarketplaceId(),
            ]);

            $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                ->setData([
                    'label'   => $this->__('Back to Unmanaged Listing'),
                    'onclick' => 'setLocation(\''.$url.'\');',
                    'class' => 'action primary'
                ]);
            $this->setChild('back_to_listing_other', $buttonBlock);
        }
        // ---------------------------------------
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }
}
