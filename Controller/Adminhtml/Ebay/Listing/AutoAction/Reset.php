<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction\Reset
 */
class Reset extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->ebayFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );

        $data = [
            'auto_mode'                          => \Ess\M2ePro\Model\Listing::AUTO_MODE_NONE,
            'auto_global_adding_mode'            => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'auto_global_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_global_adding_template_category_id'                 => null,
            'auto_global_adding_template_category_secondary_id'       => null,
            'auto_global_adding_template_store_category_id'           => null,
            'auto_global_adding_template_store_category_secondary_id' => null,

            'auto_website_adding_mode'            => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'auto_website_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_website_adding_template_category_id'                 => null,
            'auto_website_adding_template_category_secondary_id'       => null,
            'auto_website_adding_template_store_category_id'           => null,
            'auto_website_adding_template_store_category_secondary_id' => null,

            'auto_website_deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE
        ];

        $listing->addData($data);
        $listing->getChildObject()->addData($data);
        $listing->save();

        foreach ($listing->getAutoCategoriesGroups(true) as $autoCategoryGroup) {
            /**@var \Ess\M2ePro\Model\Listing\Auto\Category\Group $autoCategoryGroup */
            $autoCategoryGroup->delete();
        }
    }
}
