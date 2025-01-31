<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class BusinessHours extends AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->helperData = $helperData;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayAccountPickupStoreEditTabsBusinessHours');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $formData = $this->getFormData();

        $form->addField(
            'block_notice_ebay_accounts_pickup_store_business_hours',
            self::HELP_BLOCK,
            [
                'content' => $this->__('
                 On this Tab, you can <strong>specify the Work Hours</strong> as well as the Special Work Hours of
                 your Store.<br/>
                 So, you can set up a common working schedule and select the days and the timestamp when your Store
                 is available for Buyers.<br/>
                 In the Special Work Hours section you can specify the
                 <strong>working time</strong> on a particular special date.
             ')
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_account_pickup_store_form_data_business_hours_work_hours',
            [
                'legend' => $this->__('Work Hours'), 'collapsable' => false
            ]
        );

        $fieldset->addField(
            'work_hours_custom_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->getLayout()
           ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs\BusinessHours\WorkHours::class)
           ->setData(['form_data' => $formData])
           ->toHtml(),
                'style' => 'width: 100%'
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_ebay_account_pickup_store_form_data_business_hours_special_hours',
            [
                'legend' => $this->__('Special Work Hours'), 'collapsable' => true
            ]
        );

        $fieldset->addField(
            'special_hours_custom_container',
            self::CUSTOM_CONTAINER,
            [
                'text' => $this->getLayout()
        ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs\BusinessHours\SpecialHours::class)
                    ->setData(['form_data' => $formData])
                    ->toHtml()
            ]
        );

        $this->setForm($form);
        return parent::_prepareForm();
    }

    //########################################

    public function getFormData()
    {
        $default = [
            'business_hours' => ['week_settings' => [], 'week_days' => []],
            'special_hours' => ['date_settings' => []]
        ];

        $formData = [];
        $model = $this->globalDataHelper->getValue('temp_data');
        if ($model !== null) {
            $formData = $model->toArray();
        }

        if (!empty($formData['business_hours'])) {
            $formData['business_hours'] = $this->prepareHoursData(
                $formData['business_hours'],
                'week_settings'
            );
        }

        if (!empty($formData['special_hours'])) {
            $formData['special_hours'] = $this->prepareHoursData(
                $formData['special_hours'],
                'date_settings'
            );
        }

        return array_merge($default, $formData);
    }

    //########################################

    protected function prepareHoursData($hoursData, $key)
    {
        $data = [];

        if (!empty($hoursData)) {
            $data = $this->helperData->jsonDecode($hoursData);

            if (!isset($data[$key])) {
                return $data;
            }

            $parsedSettings = [];
            foreach ($data[$key] as $day => $daySettings) {
                $openDateTime = $this->helperData->createGmtDateTime($daySettings['open']);
                $closeDateTime = $this->helperData->createGmtDateTime($daySettings['close']);

                $fromHours = $openDateTime->format('G');
                $fromMinutes = $openDateTime->format('i');

                $toHours = $closeDateTime->format('G');
                $toMinutes = $closeDateTime->format('i');

                $parsedSettings[$day] = [
                    'from_hours'   => $fromHours == 0 ? 24 : $fromHours,
                    'from_minutes' => $fromMinutes,

                    'to_hours'   => $toHours == 0 ? 24 : $toHours,
                    'to_minutes' => $toMinutes,
                ];
            }

            $data[$key] = $parsedSettings;
        }

        return $data;
    }

    //########################################
}
