<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode;

abstract class AbstractWebsite extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    public $formData = [];

    protected $listing;

    /** @var \Ess\M2ePro\Helper\Data */
    protected $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    /** @var \Ess\M2ePro\Helper\Magento\Store */
    private $magentoStoreHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Magento\Store $magentoStoreHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->magentoStoreHelper = $magentoStoreHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->setId('listingAutoActionModeWebsite');
        $this->formData = $this->getFormData();
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $form->addField(
            'auto_mode',
            'hidden',
            [
                'name' => 'auto_mode',
                'value' => \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE
            ]
        );

        $fieldSet = $form->addFieldset('auto_website_fieldset_container', []);

        $fieldSet->addField(
            'auto_website_adding_mode',
            self::SELECT,
            [
                'name' => 'auto_website_adding_mode',
                'label' => $this->__('Product Added to Website'),
                'title' => $this->__('Product Added to Website'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE, 'label' => $this->__('No Action')],
                ],
                'value' => $this->formData['auto_website_adding_mode'],
                'tooltip' => $this->__(
                    'Action which will be applied automatically.'
                ),
                'style' => 'width: 350px'
            ]
        );

        $fieldSet->addField(
            'auto_website_adding_add_not_visible',
            self::SELECT,
            [
                'name' => 'auto_website_adding_add_not_visible',
                'label' => $this->__('Add not Visible Individually Products'),
                'title' => $this->__('Add not Visible Individually Products'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_NO, 'label' => $this->__('No')],
                    [
                        'value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
                        'label' => $this->__('Yes')
                    ],
                ],
                'value' => $this->formData['auto_website_adding_add_not_visible'],
                'field_extra_attributes' => 'id="auto_website_adding_add_not_visible_field"',
                'tooltip' => $this->__(
                    'Set to <strong>Yes</strong> if you want the Magento Products with
                    Visibility \'Not visible Individually\' to be added to the Listing
                    Automatically.<br/>
                    If set to <strong>No</strong>, only Variation (i.e.
                    Parent) Magento Products will be added to the Listing Automatically,
                    excluding Child Products.'
                )
            ]
        );

        $fieldSet->addField(
            'auto_website_deleting_mode',
            self::SELECT,
            [
                'name' => 'auto_website_deleting_mode',
                'label' => $this->__('Product Deleted from Website'),
                'title' => $this->__('Product Deleted from Website'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE,
                        'label' => $this->__('No Action')],
                    ['value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP,
                        'label' => $this->__('Stop on Channel')],
                    ['value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE,
                        'label' => $this->__('Stop on Channel and Delete from Listing')],
                ],
                'value' => $this->formData['auto_website_deleting_mode'],
                'style' => 'width: 350px'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function hasFormData()
    {
        return $this->getListing()->getData('auto_mode') == \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE;
    }

    public function getFormData()
    {
        $formData = $this->getListing()->getData();
        $formData = array_merge($formData, $this->getListing()->getChildObject()->getData());
        $default = $this->getDefault();
        return array_merge($default, $formData);
    }

    public function getDefault()
    {
        return [
            'auto_website_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'auto_website_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_website_deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE,
        ];
    }

    /**
     * @return \Ess\M2ePro\Model\Listing
     * @throws \Exception
     */
    public function getListing()
    {
        if ($this->listing === null) {
            $this->listing = $this->activeRecordFactory->getCachedObjectLoaded(
                'Listing',
                $this->getRequest()->getParam('listing_id')
            );
        }

        return $this->listing;
    }

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Listing::class)
        );

        $hasFormData = $this->hasFormData() ? 'true' : 'false';

        $this->js->add(<<<JS
        $('auto_website_adding_mode')
            .observe('change', ListingAutoActionObj.addingModeChange)
            .simulate('change');

        if ({$hasFormData}) {
            $('website_reset_button').show();
        }
JS
        );

        return parent::_afterToHtml($html);
    }

    protected function _toHtml()
    {
        return '<div id="additional_autoaction_title_text" style="display: none">' . $this->getBlockTitle() . '</div>'
                . '<div id="block-content-wrapper"><div id="data_container">'.parent::_toHtml().'</div></div>';
    }

    protected function getBlockTitle()
    {
        return $this->__('Website') . ": {$this->getWebsiteName()}";
    }

    public function getWebsiteName()
    {
        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->globalDataHelper->getValue('listing');
        return $this->magentoStoreHelper->getWebsiteName($listing->getStoreId());
    }
}
