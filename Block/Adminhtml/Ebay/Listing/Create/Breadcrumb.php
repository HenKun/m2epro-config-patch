<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Create;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Create\Breadcrumb
 */
class Breadcrumb extends \Ess\M2ePro\Block\Adminhtml\Widget\Breadcrumb
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingBreadcrumb');

        $this->setSteps(
            [
                [
                    'id'          => 1,
                    'title'       => $this->__('Step 1'),
                    'description' => $this->__('General Settings')
                ],
                [
                    'id'          => 2,
                    'title'       => $this->__('Step 2'),
                    'description' => $this->__('Policies')
                ]
            ]
        );
    }

    //########################################
}
