<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml;

abstract class Wizard extends Main
{
    /** @var \Ess\M2ePro\Helper\Module\Wizard|null */
    protected $wizardHelper;

    /** @var \Magento\Framework\Code\NameBuilder */
    protected $nameBuilder;

    public function __construct(
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        Context $context
    ) {
        $this->nameBuilder = $nameBuilder;
        parent::__construct($context);
    }

    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay') ||
               $this->_authorization->isAllowed('Ess_M2ePro::amazon');
    }

    //########################################

    abstract protected function getNick();

    abstract protected function getMenuRootNodeNick();

    abstract protected function getMenuRootNodeLabel();

    //########################################

    protected function completeAction()
    {
        $this->setStatus(\Ess\M2ePro\Helper\Module\Wizard::STATUS_COMPLETED);

        $this->_redirect('*/*/index');
    }

    protected function congratulationAction()
    {
        if (!$this->isFinished()) {
            return $this->_redirect('*/*/index');
        }

        $this->getHelper('Magento')->clearMenuCache();

        $this->addContent($this->createCongratulationBlock());

        return $this->getResult();
    }

    protected function createCongratulationBlock()
    {
        return $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Wizard\Congratulation::class);
    }

    protected function indexAction()
    {
        if ($this->isNotStarted() || $this->isActive()) {
            $this->installationAction();
            return;
        }

        return $this->congratulationAction();
    }

    protected function installationAction()
    {
        if ($this->isFinished()) {
            return $this->congratulationAction();
        }

        if ($this->isNotStarted()) {
            $this->setStatus(\Ess\M2ePro\Helper\Module\Wizard::STATUS_ACTIVE);
        }

        if (!$this->getCurrentStep() || !in_array($this->getCurrentStep(), $this->getSteps())) {
            $this->setStep($this->getFirstStep());
        }

        $this->_forward($this->getCurrentStep());
    }

    /**
     * @param \Ess\M2ePro\Model\Registration\Manager $manager
     *
     * @return \Magento\Framework\Controller\Result\Raw|\Magento\Framework\View\Result\Page
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function registrationAction(\Ess\M2ePro\Model\Registration\Manager $manager)
    {
        $key = $this->getHelper('Module\License')->getKey();

        if ($manager->isExistInfo() && !empty($key)) {
            $this->setStep($this->getNextStep());
            return $this->renderSimpleStep();
        }

        return $this->renderSimpleStep();
    }

    //########################################

    protected function getWizardHelper()
    {
        if ($this->wizardHelper === null) {
            $this->wizardHelper = $this->getHelper('Module\Wizard');
        }

        return $this->wizardHelper;
    }

    // ---------------------------------------

    protected function setStatus($status)
    {
        $this->getWizardHelper()->setStatus($this->getNick(), $status);
        return $this;
    }

    protected function getStatus()
    {
        return $this->getWizardHelper()->getStatus($this->getNick());
    }

    // ---------------------------------------

    protected function setStep($step)
    {
        $this->getWizardHelper()->setStep($this->getNick(), $step);
        return $this;
    }

    protected function getSteps()
    {
        return $this->getWizardHelper()->getWizard($this->getNick())->getSteps();
    }

    protected function getFirstStep()
    {
        return $this->getWizardHelper()->getWizard($this->getNick())->getFirstStep();
    }

    protected function getPrevStep()
    {
        return $this->getWizardHelper()->getWizard($this->getNick())->getPrevStep();
    }

    protected function getCurrentStep()
    {
        return $this->getWizardHelper()->getStep($this->getNick());
    }

    protected function getNextStep()
    {
        return $this->getWizardHelper()->getWizard($this->getNick())->getNextStep();
    }

    // ---------------------------------------

    protected function isNotStarted()
    {
        return $this->getWizardHelper()->isNotStarted($this->getNick());
    }

    protected function isActive()
    {
        return $this->getWizardHelper()->isActive($this->getNick());
    }

    public function isCompleted()
    {
        return $this->getWizardHelper()->isCompleted($this->getNick());
    }

    public function isSkipped()
    {
        return $this->getWizardHelper()->isSkipped($this->getNick());
    }

    protected function isFinished()
    {
        return $this->getWizardHelper()->isFinished($this->getNick());
    }

    //########################################

    public function setStepAction()
    {
        $step = $this->getRequest()->getParam('step');

        if ($step === null) {
            $this->setJsonContent([
                'type' => 'error',
                'message' => $this->__('Step is invalid')
            ]);

            return $this->getResult();
        }

        $this->setStep($step);

        $this->setJsonContent([
            'type' => 'success'
        ]);

        return $this->getResult();
    }

    public function setStatusAction()
    {
        $status = $this->getRequest()->getParam('status');

        if ($status === null) {
            $this->setJsonContent([
                'type' => 'error',
                'message' => $this->__('Status is invalid')
            ]);

            return $this->getResult();
        }

        $this->setStatus($status);

        $this->setJsonContent([
            'type' => 'success'
        ]);

        return $this->getResult();
    }

    //########################################

    protected function renderSimpleStep()
    {
        $this->addContent(
            $this->getLayout()->createBlock(
                $this->nameBuilder->buildClassName([
                    '\Ess\M2ePro\Block\Adminhtml\Wizard',
                    $this->getNick(),
                    'Installation',
                    $this->getCurrentStep()
                ])
            )->setData([
                'nick' => $this->getNick()
            ])
        );

        return $this->getResult();
    }

    //########################################
}
