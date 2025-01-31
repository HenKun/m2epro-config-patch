<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics;

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry
 */
class Registry extends \Ess\M2ePro\Model\AbstractModel
{
    const STORAGE_KEY = 'servicing/analytics';

    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;

    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->helperData = $helperData;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function isPlannedNow()
    {
        $plannedAt  = $this->getPlannedAt();
        $startedAt  = $this->getStartedAt();
        $finishedAt = $this->getFinishedAt();

        $currentTimestamp = $this->helperData->getCurrentGmtDate(true);
        if (empty($plannedAt) ||
            (int)$this->helperData->createGmtDateTime($plannedAt)->format('U') > $currentTimestamp
        ) {
            return false;
        }

        if (!empty($startedAt) && !empty($finishedAt)) {
            return false;
        }

        return true;
    }

    public function getPlannedAt()
    {
        $regData = $this->getStoredData();
        return isset($regData['planned_at']) ? $regData['planned_at'] : null;
    }

    public function getStartedAt()
    {
        $regData = $this->getStoredData();
        return isset($regData['started_at']) ? $regData['started_at'] : null;
    }

    public function getFinishedAt()
    {
        $regData = $this->getStoredData();
        return isset($regData['finished_at']) ? $regData['finished_at'] : null;
    }

    // ---------------------------------------

    public function markPlannedAt($date)
    {
        $regData = $this->getStoredData();

        $regData['planned_at'] = $date;
        unset($regData['started_at'], $regData['finished_at'], $regData['progress']);

        $this->setStoredData($regData);
    }

    public function markStarted()
    {
        $regData = $this->getStoredData();

        $regData['started_at'] = $this->helperData->getCurrentGmtDate(false, 'Y-m-d H:i:s');
        $regData['progress'] = [];

        $this->setStoredData($regData);
    }

    public function markFinished()
    {
        $regData = $this->getStoredData();
        $regData['finished_at'] = $this->helperData->getCurrentGmtDate(false, 'Y-m-d H:i:s');

        $this->setStoredData($regData);
    }

    //########################################

    public function getProgressData($nick, $progressDataKey)
    {
        $regData = $this->getStoredData();
        return isset($regData['progress'][$nick][$progressDataKey]) ? $regData['progress'][$nick][$progressDataKey]
                                                                    : null;
    }

    public function setProgressData($nick, $progressDataKey, $progressDataValue)
    {
        $regData = $this->getStoredData();
        $regData['progress'][$nick][$progressDataKey] = $progressDataValue;

        $this->setStoredData($regData);
    }

    //########################################

    protected function setStoredData($data)
    {
        return $this->getHelper('Module')->getRegistry()->setValue(self::STORAGE_KEY, $data);
    }

    protected function getStoredData()
    {
        return $this->getHelper('Module')->getRegistry()->getValueFromJson(self::STORAGE_KEY);
    }

    //########################################
}
