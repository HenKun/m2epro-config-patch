<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Ebay\Account;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Account\Edit\Tabs\General
 */
class General extends AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayHelper;
    /** @var \Ess\M2ePro\Model\Ebay\Account\TemporaryStorage */
    private $temporaryStorage;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Account\TemporaryStorage $temporaryStorage,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Ebay $ebayHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->dataHelper = $dataHelper;
        $this->ebayHelper = $ebayHelper;
        $this->temporaryStorage = $temporaryStorage;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    // ----------------------------------------

    protected function _prepareForm()
    {
        $account = $this->globalDataHelper->getValue('edit_account');
        $formData = $account !== null ? array_merge($account->getData(), $account->getChildObject()->getData()) : [];

        if (empty($formData['user_id']) && isset($formData['info']) &&
            $ebayInfo = $this->dataHelper->jsonDecode($formData['info'])
        ) {
            !empty($ebayInfo['UserID']) && $formData['user_id'] = (string)$ebayInfo['UserID'];
        }

        $fillFormDataFunction = static function ($formKey, $tempValue) use (&$formData) {
            if ($tempValue === null) {
                return;
            }
            $formData[$formKey] = $tempValue;
        };

        $fillFormDataFunction('title', $this->temporaryStorage->getAccountTitle());
        $fillFormDataFunction('mode', $this->temporaryStorage->getAccountMode());
        $fillFormDataFunction('token_session', $this->temporaryStorage->getSessionId());
        $fillFormDataFunction('sell_api_token_session', $this->temporaryStorage->getSellApiToken());
        $this->temporaryStorage->deleteAllValues();

        $defaults = $this->modelFactory->getObject('Ebay_Account_Builder')->getDefaultData();
        $formData = array_merge($defaults, $formData);

        $isEdit = (bool)$this->getRequest()->getParam('id');

        $form = $this->_formFactory->create();

        if (!$isEdit) {
            $content = $this->__(<<<HTML
Add an eBay Account to M2E Pro by choosing the eBay Environment and granting access to your eBay Account.<br/><br/>
First choose the <b>Environment</b> of the eBay Account you want to work in.
If you want to add an eBay Account to list for real on Marketplaces,
choose <b>Production (Live)</b>. If you want to add an eBay Sandbox Account that\'s been set up for
test or development purposes,
choose <b>Sandbox (Test)</b>. Then click <b>Get Token</b> to sign in to eBay and
<b>Agree</b> to allow your eBay Account to connect to M2E Pro.<br/><br/>
Once you\'ve authorised M2E Pro to access your Account, the <b>Activated</b>
status will change to \'Yes\' and you can click <b>Save and Continue Edit</b>.<br/><br/>
<b>Note:</b> A Production (Live) eBay Account only works on a live Marketplace.
A Sandbox (Test) Account only works on the eBay Sandbox test Environment.
To register for a Sandbox Account, register at
<a href="https://developer.ebay.com/join/" target="_blank" class="external-link">developer.ebay.com/join</a>.
HTML
            );
        } else {
            $content = $this->__(<<<HTML
This Page shows the Environment for your eBay Account and details of the authorisation for M2E Pro to connect
to your eBay Account.<br/><br/>
If your token has expired or is not activated, click <b>Get Token</b>.<br/><br/>
More detailed information about ability to work with this Page you can find
<a href="%url%" target="_blank" class="external-link">here</a>.
HTML
                , $this->supportHelper->getDocumentationArticleUrl('x/Uv8UB'));
        }

        $form->addField(
            'ebay_accounts_general',
            self::HELP_BLOCK,
            [
                'content' => $content
            ]
        );

        $fieldset = $form->addFieldset(
            'general',
            [
                'legend' => $this->__('General'),
                'collapsable' => false
            ]
        );

        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'class' => 'M2ePro-account-title',
                'label' => $this->__('Title'),
                'required' => true,
                'value' => $formData['title'],
                'tooltip' => $this->__('Title or Identifier of eBay Account for your internal use.')
            ]
        );

        $fieldset = $form->addFieldset(
            'access_detaails',
            [
                'legend' => $this->__('Access Details'),
                'collapsable' => false
            ]
        );

        if ($isEdit) {
            if (!empty($formData['user_id'])) {
                $fieldset->addField(
                    'ebay_user_id',
                    'link',
                    [
                        'label' => $this->__('eBay User ID'),
                        'value' => $formData['user_id'],
                        'href' => $this->ebayHelper->getMemberUrl(
                            $formData['user_id'],
                            $formData['mode']
                        ),
                        'class' => 'control-value external-link',
                        'target' => '_blank',
                        'style' => 'text-decoration: underline;'
                    ]
                );
            } else {
                $fieldset->addField(
                    'ebay_user_id',
                    'label',
                    [
                        'label' => $this->__('eBay User ID'),
                        'value' => $formData['title']
                    ]
                );
            }
        }

        $fieldset->addField(
            'mode',
            'select',
            [
                'label' => $this->__('Environment'),
                'name' => 'mode',
                'values' => [
                    Account::MODE_PRODUCTION => $this->__('Production (Live)'),
                    Account::MODE_SANDBOX => $this->__('Sandbox (Test)'),
                ],
                'value' => $formData['mode'],
                'disabled' => $formData['token_session'] != '',
                'tooltip' => !$isEdit ? $this->__(
                    'Choose \'Production (Live)\' to use an eBay Account to list for real on Marketplaces.
                    <br/>Choose \'Sandbox (Test)\' to use an eBay Sandbox Account for testing purposes.'
                )
                    : $this->__('<b>Production (Live):</b> an eBay Account Listing for real on Marketplaces.
                                <br/><b>Sandbox (Test):</b> an eBay Sandbox Account for testing purposes.')
            ]
        );

        if ($formData['token_session'] != '') {
            $fieldset->addField(
                'mode_hidden',
                'hidden',
                [
                    'name' => 'mode',
                    'value' => $formData['mode']
                ]
            );
        }

        if ($this->isSellApiMode()) {
            $fieldset = $form->addFieldset(
                'trading_api_details',
                [
                    'legend' => $this->__('Trading API Details'),
                    'collapsable' => false
                ]
            );
        }

        $fieldset->addField(
            'grant_access',
            'button',
            [
                'label' => $this->__('Grant Access'),
                'value' => $this->__('Get Token'),
                'class' => 'action-primary',
                'onclick' => 'EbayAccountObj.get_token();',
                'note' => $this->__(
                    'You need to finish the token process within 5 minutes.<br/>
                    If not, just click <b>Get Token</b> and try again.'
                )
            ]
        );

        $fieldset->addField(
            'activated',
            'label',
            [
                'label' => $this->__('Activated'),
                'value' => $formData['token_session'] != '' ? $this->__('Yes') : $this->__('No'),
                'css_class' => !$formData['token_session'] || !$formData['token_expired_date'] ?
                    'no-margin-bottom' : ''
            ]
        );

        if ($formData['token_session'] != '' && $formData['token_expired_date'] != '') {
            $fieldset->addField(
                'expiration_date',
                'label',
                [
                    'label' => $this->__('Expiration Date'),
                    'value' => $formData['token_expired_date']
                ]
            );
        }

        $fieldset->addField(
            'token_expired_date',
            'hidden',
            [
                'name' => 'token_expired_date',
                'value' => $formData['token_expired_date']
            ]
        );

        $fieldset->addField(
            'token_session',
            'text',
            [
                'label' => '',
                'name' => 'token_session',
                'value' => $formData['token_session'],
                'class' => 'M2ePro-account-token-session',
                'style' => 'visibility: hidden'
            ]
        );

        if ($this->isSellApiMode()) {

            $fieldset = $form->addFieldset(
                'Sell API Details',
                [
                    'legend' => $this->__('Sell API Details'),
                    'collapsable' => false
                ]
            );

            $fieldset->addField(
                'grant_access_sell_api',
                'button',
                [
                    'label' => $this->__('Grant Access'),
                    'value' => $this->__('Get Token'),
                    'class' => 'action-primary',
                    'onclick' => 'EbayAccountObj.get_sell_api_token();',
                    'note' => $this->__(
                        'You need to finish the token process within 5 minutes.<br/>
                        If not, just click <b>Get Token</b> and try again.'
                    )
                ]
            );

            $fieldset->addField(
                'activated_sell_api',
                'label',
                [
                    'label' => $this->__('Activated'),
                    'value' => $formData['sell_api_token_session'] != '' ? $this->__('Yes') : $this->__('No'),
                    'css_class' => !$formData['sell_api_token_session'] ||
                    !$formData['token_expired_date'] ? 'no-margin-bottom' : ''
                ]
            );

            if ($formData['sell_api_token_session'] != '' && $formData['sell_api_token_expired_date'] != '') {
                $fieldset->addField(
                    'expiration_date_sell_api',
                    'label',
                    [
                        'label' => $this->__('Expiration Date'),
                        'value' => $formData['sell_api_token_expired_date']
                    ]
                );
            }

            $fieldset->addField(
                'sell_api_token_expired_date',
                'hidden',
                [
                    'name' => 'sell_api_token_expired_date',
                    'value' => $formData['sell_api_token_expired_date']
                ]
            );

            $fieldset->addField(
                'sell_api_token_session',
                'text',
                [
                    'label' => '',
                    'name' => 'sell_api_token_session',
                    'value' => $formData['sell_api_token_session'],
                    'style' => 'visibility: hidden'
                ]
            );

        }

        $this->css->add('label.mage-error[for="token_session"] { top: 0 !important; }');

        $this->setForm($form);

        $this->js->add("M2ePro.formData.mode = '" . $this->dataHelper->escapeJs($formData['mode']) . "';");
        $this->js->add(
            "M2ePro.formData.token_session
             = '" . $this->dataHelper->escapeJs($formData['token_session']) . "';"
        );
        $this->js->add(
            "M2ePro.formData.token_expired_date
            = '" . $this->dataHelper->escapeJs($formData['token_expired_date']) . "';"
        );

        $this->js->add(
            "M2ePro.formData.sell_api_token_session
             = '" . $this->dataHelper->escapeJs($formData['sell_api_token_session']) . "';"
        );
        $this->js->add(
            "M2ePro.formData.sell_api_token_expired_date
            = '" . $this->dataHelper->escapeJs($formData['sell_api_token_expired_date']) . "';"
        );

        $id = $this->getRequest()->getParam('id');
        $this->js->add("M2ePro.formData.id = '$id';");

        $this->js->add(<<<JS
    require([
        'M2ePro/Ebay/Account',
    ], function(){
        window.EbayAccountObj = new EbayAccount('{$id}');
        EbayAccountObj.initObservers();
    });
JS
        );

        return parent::_prepareForm();
    }

    //########################################

    public function isSellApiMode()
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->globalDataHelper->getValue('edit_account');

        if (empty($account) || !$account->getId()) {
            return $this->getRequest()->getParam('sell_api', false);
        }

        return $this->getRequest()->getParam('sell_api', false) ||
            !empty($account->getChildObject()->getSellApiTokenSession());
    }

    //########################################
}
