<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add;

class GetTreeInfo extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Add
{
    public function execute()
    {
        $tempSession = $this->getSessionValue('source_categories');
        $tempSession['products_ids'] = !isset($tempSession['products_ids']) ? [] : $tempSession['products_ids'];

        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category\Tree $treeBlock */
        $treeBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\SourceMode\Category\Tree::class,
            '',
            [
            'data' => [
                'tree_settings' => [
                    'show_products_amount' => true,
                    'hide_products_this_listing' => true
                ]
            ]
            ]
        );
        $treeBlock->setSelectedIds($tempSession['products_ids']);

        $this->setAjaxContent($treeBlock->getInfoJson(), false);

        return $this->getResult();
    }
}
