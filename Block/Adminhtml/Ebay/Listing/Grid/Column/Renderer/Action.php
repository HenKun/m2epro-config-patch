<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Grid\Column\Renderer;

use Ess\M2ePro\Block\Adminhtml\Magento\Renderer;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Grid as ListingGrid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Grid\Column\Renderer\Action
 */
class Action extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action
{
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        Renderer\CssRenderer $css,
        Renderer\JsPhpRenderer $jsPhp,
        Renderer\JsRenderer $js,
        Renderer\JsTranslatorRenderer $jsTranslatorRenderer,
        Renderer\JsUrlRenderer $jsUrlRenderer,
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        parent::__construct(
            $helperFactory,
            $css,
            $jsPhp,
            $js,
            $jsTranslatorRenderer,
            $jsUrlRenderer,
            $context,
            $jsonEncoder,
            $dataHelper,
            $data
        );
        $this->ebayFactory = $ebayFactory;
    }

    //########################################

    protected function _toOptionHtml($action, \Magento\Framework\DataObject $row)
    {
        $marketplace = $this->ebayFactory->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'));

        if (!$marketplace->getChildObject()->isMultiMotorsEnabled() &&
            isset($action['action_id']) &&
            $action['action_id'] == ListingGrid::MASS_ACTION_ID_EDIT_PARTS_COMPATIBILITY) {
            return '';
        }

        return parent::_toOptionHtml($action, $row);
    }

    //########################################
}
