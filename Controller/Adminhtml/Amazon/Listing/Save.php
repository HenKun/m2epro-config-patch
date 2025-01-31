<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing
{
    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;

    /**
     * @param \Ess\M2ePro\Helper\Data $helperData
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        $this->helperData = $helperData;
        parent::__construct($amazonFactory, $context);
    }

    // ----------------------------------------

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_listings_m2epro');
    }

    // ----------------------------------------

    public function execute()
    {
        if (!$post = $this->getRequest()->getPost()) {
            $this->_redirect('*/amazon_listing/index');
        }

        $id = $this->getRequest()->getParam('id');
        $listing = $this->amazonFactory->getObjectLoaded('Listing', $id, null, false);

        if ($listing === null && $id) {
            $this->getMessageManager()->addError($this->__('Listing does not exist.'));
            return $this->_redirect('*/amazon_listing/index');
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Amazon_Listing_SnapshotBuilder');
        $snapshotBuilder->setModel($listing);

        $oldData = $snapshotBuilder->getSnapshot();

        // Base prepare
        // ---------------------------------------
        $data = [];
        // ---------------------------------------

        // tab: settings
        // ---------------------------------------
        $keys = [
            'template_selling_format_id',
            'template_synchronization_id',
            'template_shipping_id'
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = (!empty($post[$key])) ? $post[$key] : null;
            }
        }
        // ---------------------------------------

        $listing->addData($data);
        $listing->getChildObject()->addData($data);
        $listing->save();

        $templateData = [];

        // tab: channel settings
        // ---------------------------------------
        $keys = [
            'account_id',
            'marketplace_id',

            'sku_mode',
            'sku_custom_attribute',
            'sku_modification_mode',
            'sku_modification_custom_value',
            'generate_sku_mode',

            'condition_mode',
            'condition_value',
            'condition_custom_attribute',

            'condition_note_mode',
            'condition_note_value',

            'image_main_mode',
            'image_main_attribute',

            'gallery_images_mode',
            'gallery_images_limit',
            'gallery_images_attribute',

            'gift_wrap_mode',
            'gift_wrap_attribute',

            'gift_message_mode',
            'gift_message_attribute',

            'handling_time_mode',
            'handling_time_value',
            'handling_time_custom_attribute',

            'restock_date_mode',
            'restock_date_value',
            'restock_date_custom_attribute'
        ];
        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $templateData[$key] = $post[$key];
            }
        }

        if ($templateData['restock_date_value'] === '') {
            $templateData['restock_date_value'] = \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');
        } else {
            $timestamp = \Ess\M2ePro\Helper\Date::parseDateFromLocalFormat(
                $templateData['restock_date_value'],
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::SHORT
            );
            $templateData['restock_date_value'] = gmdate('Y-m-d H:i:s', $timestamp);
        }
        // ---------------------------------------

        $listing->addData($templateData);
        $listing->getChildObject()->addData($templateData);
        $listing->save();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Amazon_Listing_SnapshotBuilder');
        $snapshotBuilder->setModel($listing);

        $newData = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Diff $diff */
        $diff = $this->modelFactory->getObject('Amazon_Listing_Diff');
        $diff->setNewSnapshot($newData);
        $diff->setOldSnapshot($oldData);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\AffectedListingsProducts $affectedListingsProducts */
        $affectedListingsProducts = $this->modelFactory->getObject('Amazon_Listing_AffectedListingsProducts');
        $affectedListingsProducts->setModel($listing);

        $affectedListingsProductsData = $affectedListingsProducts->getObjectsData(
            ['id', 'status'],
            ['only_physical_units' => true]
        );

        /** @var \Ess\M2ePro\Model\Amazon\Listing\ChangeProcessor $changeProcessor */
        $changeProcessor = $this->modelFactory->getObject('Amazon_Listing_ChangeProcessor');
        $changeProcessor->process($diff, $affectedListingsProductsData);

        $this->processSellingFormatTemplateChange($oldData, $newData, $affectedListingsProductsData);
        $this->processSynchronizationTemplateChange($oldData, $newData, $affectedListingsProductsData);

        $affectedListingsProductsData = $affectedListingsProducts->getObjectsData(
            ['id', 'status'],
            ['only_physical_units' => true, 'template_shipping_id' => true]
        );
        $this->processShippingTemplateChange($oldData, $newData, $affectedListingsProductsData);

        $this->getMessageManager()->addSuccess($this->__('The Listing was saved.'));

        return $this->_redirect($this->helperData->getBackUrl('list', [], ['edit'=>['id'=>$id]]));
    }

    // ----------------------------------------

    protected function processSellingFormatTemplateChange(
        array $oldData,
        array $newData,
        array $affectedListingsProductsData
    ) {
        if (empty($affectedListingsProductsData) ||
            empty($oldData['template_selling_format_id']) || empty($newData['template_selling_format_id'])) {
            return;
        }

        $oldTemplate = $this->amazonFactory->getObjectLoaded(
            'Template_SellingFormat',
            $oldData['template_selling_format_id'],
            null,
            false
        );

        /** @var \Ess\M2ePro\Model\Amazon\Template\SellingFormat\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_SellingFormat_SnapshotBuilder');
        $snapshotBuilder->setModel($oldTemplate);
        $oldSnapshot = $snapshotBuilder->getSnapshot();

        $newTemplate = $this->amazonFactory->getObjectLoaded(
            'Template_SellingFormat',
            $newData['template_selling_format_id'],
            null,
            false
        );

        $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_SellingFormat_SnapshotBuilder');
        $snapshotBuilder->setModel($newTemplate);
        $newSnapshot = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\Amazon\Template\SellingFormat\Diff $diff */
        $diff = $this->modelFactory->getObject('Amazon_Template_SellingFormat_Diff');
        $diff->setNewSnapshot($newSnapshot);
        $diff->setOldSnapshot($oldSnapshot);

        /** @var \Ess\M2ePro\Model\Amazon\Template\SellingFormat\ChangeProcessor $changeProcessor */
        $changeProcessor = $this->modelFactory->getObject('Amazon_Template_SellingFormat_ChangeProcessor');
        $changeProcessor->process($diff, $affectedListingsProductsData);
    }

    protected function processSynchronizationTemplateChange(
        array $oldData,
        array $newData,
        array $affectedListingsProductsData
    ) {
        if (empty($affectedListingsProductsData) ||
            empty($oldData['template_synchronization_id']) || empty($newData['template_synchronization_id'])) {
            return;
        }

        $oldTemplate = $this->amazonFactory->getObjectLoaded(
            'Template_Synchronization',
            $oldData['template_synchronization_id'],
            null,
            false
        );

        /** @var \Ess\M2ePro\Model\Amazon\Template\Synchronization\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_Synchronization_SnapshotBuilder');
        $snapshotBuilder->setModel($oldTemplate);
        $oldSnapshot = $snapshotBuilder->getSnapshot();

        $newTemplate = $this->amazonFactory->getObjectLoaded(
            'Template_Synchronization',
            $newData['template_synchronization_id'],
            null,
            false
        );

        $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_Synchronization_SnapshotBuilder');
        $snapshotBuilder->setModel($newTemplate);
        $newSnapshot = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\Amazon\Template\Synchronization\Diff $diff */
        $diff = $this->modelFactory->getObject('Amazon_Template_Synchronization_Diff');
        $diff->setNewSnapshot($newSnapshot);
        $diff->setOldSnapshot($oldSnapshot);

        /** @var \Ess\M2ePro\Model\Amazon\Template\Synchronization\ChangeProcessor $changeProcessor */
        $changeProcessor = $this->modelFactory->getObject('Amazon_Template_Synchronization_ChangeProcessor');
        $changeProcessor->process($diff, $affectedListingsProductsData);
    }

    protected function processShippingTemplateChange(
        array $oldData,
        array $newData,
        array $affectedListingsProductsData
    ) {
        if (empty($affectedListingsProductsData) ||
            empty($oldData['template_shipping_id']) &&
            empty($newData['template_shipping_id'])) {
            return;
        }

        $oldTemplate = $this->activeRecordFactory->getObject('Amazon_Template_Shipping');
        if (!empty($oldData['template_shipping_id'])) {
            $oldTemplate = $oldTemplate->load($oldData['template_shipping_id']);
        }

        $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_Shipping_SnapshotBuilder');
        $snapshotBuilder->setModel($oldTemplate);
        $oldSnapshot = $snapshotBuilder->getSnapshot();

        $newTemplate = $this->activeRecordFactory->getObject('Amazon_Template_Shipping');
        if (!empty($newData['template_shipping_id'])) {
            $newTemplate = $oldTemplate->load($newData['template_shipping_id']);
        }

        $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_Shipping_SnapshotBuilder');
        $snapshotBuilder->setModel($newTemplate);
        $newSnapshot = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping\Diff $diff */
        $diff = $this->modelFactory->getObject('Amazon_Template_Shipping_Diff');
        $diff->setNewSnapshot($newSnapshot);
        $diff->setOldSnapshot($oldSnapshot);

        /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping\ChangeProcessor $changeProcessor */
        $changeProcessor = $this->modelFactory->getObject('Amazon_Template_Shipping_ChangeProcessor');
        $changeProcessor->process($diff, $affectedListingsProductsData);
    }
}
