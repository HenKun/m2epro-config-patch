<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Exception;

/**
 * Class \Ess\M2ePro\Model\Exception\Connection
 */
class Connection extends \Ess\M2ePro\Model\Exception
{
    //########################################

    const CONNECTION_ERROR_REPEAT_TIMEOUT = 180;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;
    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;
    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;

    //########################################

    public function __construct(
        $message,
        $additionalData = []
    ) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->activeRecordFactory = $objectManager->get(\Ess\M2ePro\Model\ActiveRecord\Factory::class);
        $this->helperFactory = $objectManager->get(\Ess\M2ePro\Helper\Factory::class);
        $this->helperData = $objectManager->get(\Ess\M2ePro\Helper\Data::class);

        parent::__construct($message, $additionalData);
    }

    //########################################

    /**
     * @param string $key
     *
     * @return bool
     */
    public function handleRepeatTimeout($key)
    {
        $currentDate = $this->helperFactory->getObject('Data')->getCurrentGmtDate();

        $firstConnectionErrorDate = $this->getFirstConnectionErrorDate($key);
        if (empty($firstConnectionErrorDate)) {
            $this->setFirstConnectionErrorDate($key, $currentDate);

            return true;
        }

        $currentDateTimeStamp = (int)$this->helperData
            ->createGmtDateTime($currentDate)
            ->format('U');
        $errorDateTimeStamp = (int)$this->helperData
            ->createGmtDateTime($firstConnectionErrorDate)
            ->format('U');
        if ($currentDateTimeStamp - $errorDateTimeStamp < self::CONNECTION_ERROR_REPEAT_TIMEOUT) {
            return true;
        }

        if (!empty($firstConnectionErrorDate)) {
            $this->removeFirstConnectionErrorDate($key);
        }

        return false;
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    protected function getFirstConnectionErrorDate($key)
    {
        return $this->getHelper('Module')->getRegistry()->getValue($key);
    }

    /**
     * @param string $key
     * @param string $date
     *
     */
    protected function setFirstConnectionErrorDate($key, $date)
    {
        $this->getHelper('Module')->getRegistry()->setValue($key, $date);
    }

    /**
     * @param string $key
     *
     */
    protected function removeFirstConnectionErrorDate($key)
    {
        $this->getHelper('Module')->getRegistry()->deleteValue($key);
    }

    //########################################

    /**
     * @param $helperName
     * @param array $arguments
     * @return \Magento\Framework\App\Helper\AbstractHelper
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getHelper($helperName, array $arguments = [])
    {
        return $this->helperFactory->getObject($helperName, $arguments);
    }

    //########################################
}
