<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Sections\License;

class Create extends \Ess\M2ePro\Block\Adminhtml\System\Config\Sections
{
    /** @var \Magento\Config\Model\Config\Source\Locale\Country */
    private $country;
    /** @var \Magento\Backend\Model\Auth\Session */
    private $authSession;
    /** @var \Magento\User\Model\User */
    private $user;
    /** @var \Ess\M2ePRo\Helper\Magento\Store */
    private $magentoStoreHelper;

    /**
     * @param \Ess\M2ePRo\Helper\Magento\Store $magentoStoreHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Config\Model\Config\Source\Locale\Country $country
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\User\Model\User $user
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePRo\Helper\Magento\Store $magentoStoreHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\Locale\Country $country,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\User\Model\User $user,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);

        $this->magentoStoreHelper = $magentoStoreHelper;
        $this->country = $country;
        $this->authSession = $authSession;
        $this->user = $user;
    }

    protected function _prepareForm()
    {
        $defaultStoreId = $this->magentoStoreHelper->getDefaultStoreId();

        // ---------------------------------------
        $userId = $this->authSession->getUser()->getId();
        $userInfo = $this->user->load($userId)->getData();

        $tempPath = defined('Mage_Shipping_Model_Config::XML_PATH_ORIGIN_CITY')
            ? \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_CITY : 'shipping/origin/city';
        $userInfo['city'] = $this->_storeManager->getStore($defaultStoreId)->getConfig($tempPath);

        $tempPath = defined('Mage_Shipping_Model_Config::XML_PATH_ORIGIN_POSTCODE')
            ? \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_POSTCODE : 'shipping/origin/postcode';
        $userInfo['postal_code'] = $this->_storeManager->getStore($defaultStoreId)->getConfig($tempPath);

        $userInfo['country'] = $this->_storeManager->getStore($defaultStoreId)->getConfig('general/country/default');
        // ---------------------------------------

        $licenseFormData = $userInfo;

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id'     => 'edit_form',
                    'method' => 'post',
                    'action' => $this->getUrl('*/*/save')
                ]
            ]
        );

        $fieldSet = $form->addFieldset('create_new_license', ['legend' => '', 'collapsable' => false]);

        $fieldSet->addField(
            'create_new_license_email',
            'text',
            [
                'name'     => 'email',
                'label'    => $this->__('Email'),
                'title'    => $this->__('Email'),
                'class'    => 'M2ePro-validate-email validate-length maximum-length-80',
                'value'    => isset($licenseFormData['email']) ? $licenseFormData['email'] : '',
                'required' => true,
            ]
        );

        $fieldSet->addField(
            'create_new_license_firstname',
            'text',
            [
                'name'     => 'firstname',
                'label'    => $this->__('First Name'),
                'title'    => $this->__('First Name'),
                'class'    => 'validate-length maximum-length-40',
                'value'    => isset($licenseFormData['firstname']) ? $licenseFormData['firstname'] : '',
                'required' => true,
            ]
        );

        $fieldSet->addField(
            'create_new_license_lastname',
            'text',
            [
                'name'     => 'lastname',
                'label'    => $this->__('Last Name'),
                'title'    => $this->__('Last Name'),
                'class'    => 'validate-length maximum-length-40',
                'value'    => isset($licenseFormData['lastname']) ? $licenseFormData['lastname'] : '',
                'required' => true,
            ]
        );

        $fieldSet->addField(
            'create_new_license_phone',
            'text',
            [
                'name'     => 'phone',
                'label'    => $this->__('Phone'),
                'title'    => $this->__('Phone'),
                'class'    => 'validate-length maximum-length-40',
                'value'    => '',
                'required' => true,
            ]
        );

        $fieldSet->addField(
            'create_new_license_country',
            self::SELECT,
            [
                'name'     => 'country',
                'label'    => $this->__('Country'),
                'title'    => $this->__('Country'),
                'class'    => 'validate-length maximum-length-40',
                'values'   => $this->country->toOptionArray(),
                'value'    => isset($licenseFormData['country']) ? $licenseFormData['country'] : '',
                'required' => true,
            ]
        );

        $fieldSet->addField(
            'create_new_license_city',
            'text',
            [
                'name'     => 'city',
                'label'    => $this->__('City'),
                'title'    => $this->__('City'),
                'class'    => 'validate-length maximum-length-40',
                'value'    => isset($licenseFormData['city']) ? $licenseFormData['city'] : '',
                'required' => true,
            ]
        );

        $fieldSet->addField(
            'create_new_license_postal_code',
            'text',
            [
                'name'     => 'postal_code',
                'label'    => $this->__('Postal Code'),
                'title'    => $this->__('Postal Code'),
                'class'    => 'validate-length maximum-length-40',
                'value'    => isset($licenseFormData['postal_code']) ? $licenseFormData['postal_code'] : '',
                'required' => true,
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
