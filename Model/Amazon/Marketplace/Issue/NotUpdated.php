<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Marketplace\Issue;

use \Ess\M2ePro\Model\Issue\DataObject as Issue;
use \Magento\Framework\Message\MessageInterface as Message;

class NotUpdated extends \Ess\M2ePro\Model\Issue\Locator\AbstractModel
{
    const CACHE_KEY = __CLASS__;

    protected $amazonFactory;
    protected $urlBuilder;
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Helper\View\Amazon */
    protected $amazonViewHelper;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);
        $this->amazonFactory      = $amazonFactory;
        $this->urlBuilder         = $urlBuilder;
        $this->resourceConnection = $resourceConnection;
        $this->amazonViewHelper   = $amazonViewHelper;
    }

    public function getIssues()
    {
        if (!$this->isNeedProcess()) {
            return [];
        }

        $outdatedMarketplaces = $this->getHelper('Data_Cache_Permanent')->getValue(self::CACHE_KEY);
        if (empty($outdatedMarketplaces)) {
            $tableName = $this->getHelper('Module_Database_Structure')
                ->getTableNameWithPrefix('m2epro_amazon_dictionary_marketplace');

            $queryStmt = $this->resourceConnection->getConnection()
                ->select()
                ->from($tableName, ['marketplace_id', 'server_details_last_update_date'])
                ->where('client_details_last_update_date IS NOT NULL')
                ->where('server_details_last_update_date IS NOT NULL')
                ->where('client_details_last_update_date < server_details_last_update_date')
                ->query();

            $dictionaryData = [];
            while ($row = $queryStmt->fetch()) {
                $dictionaryData[(int)$row['marketplace_id']] = $row['server_details_last_update_date'];
            }

            $marketplacesCollection = $this->amazonFactory->getObject('Marketplace')->getCollection()
                ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
                ->addFieldToFilter('id', ['in' => array_keys($dictionaryData)])
                ->setOrder('sorder', 'ASC');

            $outdatedMarketplaces = [];
            foreach ($marketplacesCollection as $marketplace) {
                /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
                $outdatedMarketplaces[$marketplace->getTitle()] = $dictionaryData[$marketplace->getId()];
            }

            $this->getHelper('Data_Cache_Permanent')->setValue(
                self::CACHE_KEY,
                $outdatedMarketplaces,
                ['amazon', 'marketplace'],
                60*60*24
            );
        }

        if (empty($outdatedMarketplaces)) {
            return [];
        }

        $tempTitle = $this->getHelper('Module\Translation')->__(
            'M2E Pro requires action: Amazon marketplace data needs to be synchronized.
            Please update Amazon marketplaces.'
        );
        $textToTranslate = <<<TEXT
%marketplace_title% data was changed on Amazon. You need to resynchronize the marketplace(s) to correctly
associate your products with Amazon catalog.<br>
Please go to Amazon Integration > Configuration >
<a href="%url%" target="_blank">Marketplaces</a> and press <b>Update All Now</b>.
TEXT;

        $tempMessage = $this->getHelper('Module\Translation')->__(
            $textToTranslate,
            implode(', ', array_keys($outdatedMarketplaces)),
            $this->urlBuilder->getUrl('m2epro/amazon_marketplace/index')
        );

        $editHash = sha1(self::CACHE_KEY . $this->getHelper('Data')->jsonEncode($outdatedMarketplaces));
        $messageUrl = $this->urlBuilder->getUrl(
            'm2epro/amazon_marketplace/index',
            ['_query' => ['hash' => $editHash]]
        );

        return [
            $this->modelFactory->getObject('Issue_DataObject', [
                Issue::KEY_TYPE  => Message::TYPE_NOTICE,
                Issue::KEY_TITLE => $tempTitle,
                Issue::KEY_TEXT  => $tempMessage,
                Issue::KEY_URL   => $messageUrl
            ])
        ];
    }

    public function isNeedProcess()
    {
        return $this->amazonViewHelper->isInstallationWizardFinished() &&
            $this->getHelper('Component\Amazon')->isEnabled();
    }
}
