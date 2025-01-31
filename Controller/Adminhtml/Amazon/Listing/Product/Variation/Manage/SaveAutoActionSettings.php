<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class SaveAutoActionSettings extends Main
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Vocabulary */
    protected $vocabularyHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Vocabulary $vocabularyHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->vocabularyHelper = $vocabularyHelper;
    }

    public function execute()
    {
        $attributeAutoAction = $this->getRequest()->getParam('attribute_auto_action');
        $optionAutoAction = $this->getRequest()->getParam('option_auto_action');

        if ($attributeAutoAction === null || $optionAutoAction === null) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        switch ($attributeAutoAction) {
            case \Ess\M2ePro\Helper\Component\Amazon\Vocabulary::VOCABULARY_AUTO_ACTION_NOT_SET:
                $this->vocabularyHelper->unsetAttributeAutoAction();
                break;
            case \Ess\M2ePro\Helper\Component\Amazon\Vocabulary::VOCABULARY_AUTO_ACTION_NO:
                $this->vocabularyHelper->disableAttributeAutoAction();
                break;
            case \Ess\M2ePro\Helper\Component\Amazon\Vocabulary::VOCABULARY_AUTO_ACTION_YES:
                $this->vocabularyHelper->enableAttributeAutoAction();
                break;
        }

        switch ($optionAutoAction) {
            case \Ess\M2ePro\Helper\Component\Amazon\Vocabulary::VOCABULARY_AUTO_ACTION_NOT_SET:
                $this->vocabularyHelper->unsetOptionAutoAction();
                break;
            case \Ess\M2ePro\Helper\Component\Amazon\Vocabulary::VOCABULARY_AUTO_ACTION_NO:
                $this->vocabularyHelper->disableOptionAutoAction();
                break;
            case \Ess\M2ePro\Helper\Component\Amazon\Vocabulary::VOCABULARY_AUTO_ACTION_YES:
                $this->vocabularyHelper->enableOptionAutoAction();
                break;
        }

        $this->setJsonContent([
            'success' => true
        ]);

        return $this->getResult();
    }
}
