<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Account\Issue;

use \Ess\M2ePro\Model\Issue\DataObject as Issue;
use \Magento\Framework\Message\MessageInterface as Message;

class AccessTokens extends \Ess\M2ePro\Model\Issue\Locator\AbstractModel
{
    const CACHE_KEY = __CLASS__;

    protected $activeRecordFactory;
    protected $ebayFactory;
    protected $urlBuilder;
    protected $_localeDate;

    /** @var \Ess\M2ePro\Helper\View\Ebay */
    protected $ebayViewHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Rule\Model\Condition\Context $context,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory         = $ebayFactory;
        $this->urlBuilder          = $urlBuilder;
        $this->_localeDate         = $context->getLocaleDate();
        $this->ebayViewHelper      = $ebayViewHelper;
        $this->helperData          = $helperData;
    }

    public function getIssues()
    {
        if (!$this->isNeedProcess()) {
            return [];
        }

        $messagesData = $this->getHelper('Data_Cache_Permanent')->getValue(self::CACHE_KEY);
        if (empty($messagesData)) {
            /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accounts */
            $accounts = $this->ebayFactory->getObject('Account')->getCollection();
            $accounts->addFieldToFilter('token_session', ['notnull' => true]);

            $messagesData = [];
            foreach ($accounts->getItems() as $account) {
                /** @var \Ess\M2ePro\Model\Account $account */
                // @codingStandardsIgnoreLine
                $messagesData = array_merge(
                    $messagesData,
                    $this->getTradingApiTokenMessages($account),
                    $this->getSellApiTokenMessages($account)
                );
            }

            $this->getHelper('Data_Cache_Permanent')->setValue(
                self::CACHE_KEY,
                $messagesData,
                ['account','ebay'],
                60*60*24
            );
        }

        $issues = [];
        foreach ($messagesData as $messageData) {
            $issues[] = $this->modelFactory->getObject('Issue_DataObject', $messageData);
        }

        return $issues;
    }

    //########################################

    protected function getTradingApiTokenMessages(\Ess\M2ePro\Model\Account $account)
    {
        $currentTimeStamp = $this->helperData->getCurrentTimezoneDate(true);
        $tokenExpirationTimeStamp = (int)$this->helperData
            ->createGmtDateTime($account->getChildObject()->getTokenExpiredDate())
            ->format('U');

        if ($tokenExpirationTimeStamp < $currentTimeStamp) {
            $tempMessage = $this->getHelper('Module\Translation')->__(
                <<<TEXT
Attention! The Trading API token for <a href="%url%" target="_blank">"%name%"</a> eBay account has expired.
You need to generate a new access token to reauthorize M2E Pro.
TEXT
                ,
                $this->urlBuilder->getUrl('m2epro/ebay_account/edit', ['id' => $account->getId()]),
                $this->helperData->escapeHtml($account->getTitle())
            );

            $editHash = sha1(
                self::CACHE_KEY.$account->getId().$tokenExpirationTimeStamp.
                Message::TYPE_ERROR.__METHOD__
            );

            return [[
                Issue::KEY_TYPE  => Message::TYPE_ERROR,
                Issue::KEY_TITLE => $this->getHelper('Module\Translation')->__(
                    'Attention! The Trading API token for "%name%" eBay account has expired.
                    You need to generate a new access token to reauthorize M2E Pro.',
                    $this->helperData->escapeHtml($account->getTitle())
                ),
                Issue::KEY_TEXT  => $tempMessage,
                Issue::KEY_URL   => $this->getHelper('Module_Support')->getSupportUrl('/support/solutions/articles/9000218991') .'/?'.
                                    $editHash
            ]];
        }

        if (($currentTimeStamp + 60*60*24*10) >= $tokenExpirationTimeStamp) {
            $tempMessage = $this->getHelper('Module\Translation')->__(
                <<<TEXT
Attention! The Trading API token for <a href="%url%" target="_blank">"%name%"</a> eBay Account expires on %date%.
You need to generate a new access token to reauthorize M2E Pro.
TEXT
                ,
                $this->urlBuilder->getUrl('m2epro/ebay_account/edit', ['id' => $account->getId()]),
                $this->helperData->escapeHtml($account->getTitle()),
                $this->_localeDate->formatDate(
                    $account->getChildObject()->getTokenExpiredDate(),
                    \IntlDateFormatter::MEDIUM,
                    true
                )
            );

            $editHash = sha1(
                self::CACHE_KEY.$account->getId().$tokenExpirationTimeStamp.
                Message::TYPE_NOTICE.__METHOD__
            );

            return [[
                Issue::KEY_TYPE  => Message::TYPE_NOTICE,
                Issue::KEY_TITLE => $this->getHelper('Module\Translation')->__(
                    'Attention! The Trading API token for "%name%" eBay account is to expire.
                    You need to generate a new access token to reauthorize M2E Pro.',
                    $this->helperData->escapeHtml($account->getTitle())
                ),
                Issue::KEY_TEXT  => $tempMessage,
                Issue::KEY_URL   => $this->getHelper('Module_Support')->getSupportUrl('/support/solutions/articles/9000218991') .'/?'.
                                    $editHash
            ]];
        }

        return [];
    }

    protected function getSellApiTokenMessages(\Ess\M2ePro\Model\Account $account)
    {
        if (empty($account->getChildObject()->getSellApiTokenExpiredDate())) {
            return [];
        }

        $currentTimeStamp = $this->helperData->getCurrentTimezoneDate(true);
        $tokenExpirationTimeStamp = (int)$this->helperData
            ->createGmtDateTime($account->getChildObject()->getSellApiTokenExpiredDate())
            ->format('U');

        if ($tokenExpirationTimeStamp <= 0) {
            return [];
        }

        if ($tokenExpirationTimeStamp < $currentTimeStamp) {
            $tempMessage = $this->getHelper('Module\Translation')->__(
                <<<TEXT
Attention! The Sell API token for <a href="%url%" target="_blank">"%name%"</a> eBay account has expired.
You need to generate a new access token to reauthorize M2E Pro.
TEXT
                ,
                $this->urlBuilder->getUrl('m2epro/ebay_account/edit', ['id' => $account->getId()]),
                $this->helperData->escapeHtml($account->getTitle())
            );

            $editHash = sha1(
                self::CACHE_KEY.$account->getId().$tokenExpirationTimeStamp.
                Message::TYPE_ERROR.__METHOD__
            );

            return [[
                Issue::KEY_TYPE  => Message::TYPE_ERROR,
                Issue::KEY_TITLE => $this->getHelper('Module\Translation')->__(
                    'Attention! The Sell API token for "%name%" eBay account has expired.
                    You need to generate a new access token to reauthorize M2E Pro.',
                    $this->helperData->escapeHtml($account->getTitle())
                ),
                Issue::KEY_TEXT  => $tempMessage,
                Issue::KEY_URL   => $this->getHelper('Module_Support')->getSupportUrl('/support/solutions/articles/9000218991') .'/?'.
                                    $editHash
            ]];
        }

        if (($currentTimeStamp + 60*60*24*10) >= $tokenExpirationTimeStamp) {
            $tempMessage = $this->getHelper('Module\Translation')->__(
                <<<TEXT
Attention! The Sell API token for <a href="%url%" target="_blank">"%name%"</a> eBay Account expires on %date%.
You need to generate a new access token to reauthorize M2E Pro.
TEXT
                ,
                $this->urlBuilder->getUrl('m2epro/ebay_account/edit', ['id' => $account->getId()]),
                $this->helperData->escapeHtml($account->getTitle()),
                $this->_localeDate->formatDate(
                    $account->getChildObject()->getSellApiTokenExpiredDate(),
                    \IntlDateFormatter::MEDIUM,
                    true
                )
            );

            $editHash = sha1(
                self::CACHE_KEY.$account->getId().$tokenExpirationTimeStamp.
                Message::TYPE_NOTICE.__METHOD__
            );

            return [[
                Issue::KEY_TYPE  => Message::TYPE_NOTICE,
                Issue::KEY_TITLE => $this->getHelper('Module\Translation')->__(
                    'Attention! The Sell API token for "%name%" eBay account is to expire.
                    You need to generate a new access token to reauthorize M2E Pro.',
                    $this->helperData->escapeHtml($account->getTitle())
                ),
                Issue::KEY_TEXT  => $tempMessage,
                Issue::KEY_URL   => $this->getHelper('Module_Support')->getSupportUrl('/support/solutions/articles/9000218991') .'/?'.
                                    $editHash
            ]];
        }

        return [];
    }

    public function isNeedProcess()
    {
        return $this->ebayViewHelper->isInstallationWizardFinished() &&
               $this->getHelper('Component\Ebay')->isEnabled();
    }
}
