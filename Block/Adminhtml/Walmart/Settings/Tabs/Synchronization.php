<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Settings\Tabs;

use Ess\M2ePro\Block\Adminhtml\Walmart\Settings\Tabs;
use Magento\Framework\Message\MessageInterface;

class Synchronization extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfiguration;

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration
     * @param \Ess\M2ePro\Model\Config\Manager $config
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->moduleConfiguration = $moduleConfiguration;
        $this->config = $config;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @var int
     */
    private $inspectorMode;

    protected function _prepareForm()
    {
        // ---------------------------------------
        $instructionsMode = $this->config->getGroupValue(
            '/cron/task/walmart/listing/product/process_instructions/',
            'mode'
        );

        // ---------------------------------------

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'enctype' => 'multipart/form-data',
                    'method' => 'post'
                ]
            ]
        );
        // ---------------------------------------

        $form->addField(
            'walmart_settings_synchronization_help',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
                    <p>In this section, you can enable M2E Pro Listing Synchronization to automatically
                    update your Walmart Listings based on Synchronization Rules.
                    Click <strong>Save</strong> after the changes are made.</p><br>
                    <p><strong>Note:</strong> If you disable M2E Pro Listing Synchronization,
                    you will be required to monitor the Product changes by yourself and timely update
                    the related information on the Channel.</p>
HTML
                )
            ]
        );

        $fieldset = $form->addFieldset(
            'walmart_synchronization_templates',
            [
                'legend' => $this->__('M2E Pro Listings Synchronization'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'instructions_mode',
            self::SELECT,
            [
                'name'        => 'instructions_mode',
                'label'       => $this->__('Enabled'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes')
                ],
                'value' => $instructionsMode,
                'tooltip' => $this->__(
                    '<p>This synchronization includes import of changes made on Walmart channel as well
                    as the ability to enable/disable the data synchronization managed by the
                    Synchronization Policy Rules.</p><br>
                    <p>However, it does not exclude the ability to manually manage Items in Listings using the
                    available List, Revise, Relist or Stop Action options.</p>'
                )
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        parent::_prepareForm();
    }

    protected function _beforeToHtml()
    {
        // ---------------------------------------
        $this->inspectorMode = $this->moduleConfiguration->isEnableListingProductInspectorMode();
        // ---------------------------------------

        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        $js = "require([
                'M2ePro/Synchronization'
            ], function() {

            SynchronizationObj = new Synchronization();";

        $js .= '})';

        $this->js->addOnReadyJs($js);

        $this->jsTranslator->addTranslations(
            [
                'Synchronization Settings have been saved.' => 'Synchronization Settings have been saved.',
            ]
        );

        $this->jsUrl->addUrls([
            Tabs::TAB_ID_SYNCHRONIZATION => $this->getUrl('*/walmart_synchronization/save'),
            'synch_formSubmit' => $this->getUrl('*/walmart_synchronization/save'),
            'logViewUrl' => $this->getUrl('*/walmart_synchronization_log/index', ['back'=>$this->dataHelper
                ->makeBackUrlParam('*/walmart_synchronization/index')]),
        ]);

        return parent::_toHtml();
    }

    //########################################

    protected function getGlobalNotice()
    {
        return '';
    }

    //########################################
}
