<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add\GetCategoriesJson
 */
class GetCategoriesJson extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add
{

    public function execute()
    {
        $tempSession = $this->getSessionValue('source_categories');
        $selectedProductsIds = !isset($tempSession['products_ids']) ? [] : $tempSession['products_ids'];

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category\Tree $treeBlock */
        $treeBlock = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category\Tree::class);
        $treeBlock->setSelectedIds($selectedProductsIds);

        $this->setAjaxContent($treeBlock->getCategoryChildrenJson($this->getRequest()->getParam('category')), false);

        return $this->getResult();
    }
}
