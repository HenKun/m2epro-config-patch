<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\Motors;

class ImportMotorsData extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Settings
{
    /** @var \Magento\Framework\File\Csv */
    private $fileCsv;

    /** @var \Magento\Framework\HTTP\PhpEnvironment\Request */
    private $phpEnvironmentRequest;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors */
    private $componentEbayMotors;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Motors $componentEbayMotors,
        \Magento\Framework\File\Csv $fileCsv,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->fileCsv               = $fileCsv;
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        $this->componentEbayMotors   = $componentEbayMotors;
    }

    //########################################

    public function execute()
    {
        $motorsType = $this->getRequest()->getPost('motors_type');

        $uploadedFiles = $this->phpEnvironmentRequest->getFiles()->toArray();

        if (!$motorsType || empty($uploadedFiles['source']) || empty($uploadedFiles['source']['name'])) {
            $this->getMessageManager()->addError($this->__('Some of required fields are not filled up.'));
            return $this->_redirect('*/ebay_settings/index');
        }

        $uploadedFileInfo = $uploadedFiles['source'];

        $tempCsvData = $this->fileCsv->getData($uploadedFileInfo['tmp_name']);

        $csvData = [];
        $headers = array_shift($tempCsvData);
        foreach ($tempCsvData as $csvRow) {
            if (!is_array($csvRow) || count($csvRow) != count($headers)) {
                continue;
            }
            $csvData[] = array_combine($headers, $csvRow);
        }

        $added = 0;
        $existedItems = $this->getExistedMotorsItems();

        $connWrite = $this->resourceConnection->getConnection();
        $tableName = $this->componentEbayMotors->getDictionaryTable($motorsType);

        foreach ($csvData as $csvRow) {
            if (!$insertsData = $this->getPreparedInsertData($csvRow, $existedItems)) {
                continue;
            }

            $added++;
            $connWrite->insert($tableName, $insertsData);
        }

        $this->getMessageManager()->addSuccess("Added '{$added}' compatibility records.");
        return $this->_redirect('*/ebay_settings/index');
    }

    private function getExistedMotorsItems()
    {
        $helper = $this->componentEbayMotors;
        $motorsType = $this->getRequest()->getParam('motors_type');

        $selectStmt = $this->resourceConnection->getConnection('core/read')
            ->select()
            ->from(
                $helper->getDictionaryTable($motorsType),
                [$helper->getIdentifierKey($motorsType)]
            );

        if ($helper->isTypeBasedOnEpids($motorsType)) {
            $selectStmt->where('scope = ?', $helper->getEpidsScopeByType($motorsType));
        }

        $result = [];
        $queryStmt = $selectStmt->query();

        while ($id = $queryStmt->fetchColumn()) {
            $result[] = $id;
        }

        return $result;
    }

    private function getPreparedInsertData($csvRow, $existedItems)
    {
        $helper = $this->componentEbayMotors;
        $motorsType = $this->getRequest()->getParam('motors_type');

        $idCol = $this->componentEbayMotors->getIdentifierKey($motorsType);

        if (!isset($csvRow[$idCol]) || in_array($csvRow[$idCol], $existedItems)) {
            return false;
        }

        if ($motorsType == \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_KTYPE) {
            if (strlen($csvRow['ktype']) > 10) {
                return false;
            }

            if (!is_numeric($csvRow['ktype'])) {
                return false;
            }

            return [
                'ktype'      => (int)$csvRow['ktype'],
                'make'       => (isset($csvRow['make']) ? $csvRow['make'] : null),
                'model'      => (isset($csvRow['model']) ? $csvRow['model'] : null),
                'variant'    => (isset($csvRow['variant']) ? $csvRow['variant'] : null),
                'body_style' => (isset($csvRow['body_style']) ? $csvRow['body_style'] : null),
                'type'       => (isset($csvRow['type']) ? $csvRow['type'] : null),
                'from_year'  => (isset($csvRow['from_year']) ? (int)$csvRow['from_year'] : null),
                'to_year'    => (isset($csvRow['to_year']) ? (int)$csvRow['to_year'] : null),
                'engine'     => (isset($csvRow['engine']) ? $csvRow['engine'] : null),
                'is_custom'  => 1
            ];
        }

        $requiredColumns = ['epid','product_type','make','model','year'];
        foreach ($requiredColumns as $columnName) {
            if (!isset($csvRow[$columnName])) {
                return false;
            }
        }

        return [
            'epid'         => $csvRow['epid'],
            'product_type' => (int)$csvRow['product_type'],
            'make'         => $csvRow['make'],
            'model'        => $csvRow['model'],
            'year'         => (int)$csvRow['year'],
            'trim'         => (isset($csvRow['trim']) ? $csvRow['trim'] : null),
            'engine'       => (isset($csvRow['engine']) ? $csvRow['engine'] : null),
            'submodel'     => (isset($csvRow['submodel']) ? $csvRow['submodel'] : null),
            'street_name'  => (isset($csvRow['street_name']) ? $csvRow['street_name'] : null),
            'is_custom'    => 1,
            'scope'        => $helper->getEpidsScopeByType($motorsType)
        ];
    }
}
