<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\Menu\Magento\Backend\Model\Menu;

use Ess\M2ePro\Helper\Module;
use Ess\M2ePro\Helper\View\Amazon;
use Ess\M2ePro\Helper\View\Ebay;
use Ess\M2ePro\Helper\View\Walmart;
use Ess\M2ePro\Helper\Module\Maintenance as Maintenance;

/**
 * Class \Ess\M2ePro\Plugin\Menu\Magento\Backend\Model\Menu\Config
 */
class Config extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    const MENU_STATE_REGISTRY_KEY = '/menu/state/';
    const MAINTENANCE_MENU_STATE_CACHE_KEY = 'maintenance_menu_state';

    protected $activeRecordFactory;
    protected $pageConfig;
    protected $itemFactory;

    protected $isProcessed = false;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\View\Page\Config $pageConfig,
        \Magento\Backend\Model\Menu\Item\Factory $itemFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->pageConfig = $pageConfig;
        $this->itemFactory = $itemFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    protected function canExecute()
    {
        return $this->helperFactory->getObject('Module')->areImportantTablesExist();
    }

    //########################################

    public function aroundGetMenu(\Magento\Backend\Model\Menu\Config $interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('getMenu', $interceptor, $callback, $arguments);
    }

    // ---------------------------------------

    protected function processGetMenu(
        \Magento\Backend\Model\Menu\Config $interceptor,
        \Closure $callback,
        array $arguments
    ) {
        /** @var \Magento\Backend\Model\Menu $menuModel */
        $menuModel = $callback(...$arguments);

        if ($this->isProcessed) {
            return $menuModel;
        }

        $this->isProcessed = true;

        // ---------------------------------------

        $maintenanceMenuState = $this->helperFactory->getObject('Data_Cache_Permanent')->getValue(
            self::MAINTENANCE_MENU_STATE_CACHE_KEY
        );

        if ($this->helperFactory->getObject('Module\Maintenance')->isEnabled()) {
            if ($maintenanceMenuState === null) {
                $this->helperFactory->getObject('Data_Cache_Permanent')->setValue(
                    self::MAINTENANCE_MENU_STATE_CACHE_KEY,
                    true
                );
                $this->helperFactory->getObject('Magento')->clearMenuCache();
            }
            $this->processMaintenance($menuModel);
            return $menuModel;
        } elseif ($maintenanceMenuState !== null) {
            $this->helperFactory->getObject('Data_Cache_Permanent')->removeValue(
                self::MAINTENANCE_MENU_STATE_CACHE_KEY
            );
            $this->helperFactory->getObject('Magento')->clearMenuCache();
        }

        // ---------------------------------------

        $currentMenuState = $this->buildMenuStateData();
        $previousMenuState = $this->getHelper('Module')->getRegistry()
            ->getValueFromJson(self::MENU_STATE_REGISTRY_KEY);

        if ($previousMenuState != $currentMenuState) {
            $this->getHelper('Module')->getRegistry()->setValue(self::MENU_STATE_REGISTRY_KEY, $currentMenuState);
            $this->helperFactory->getObject('Magento')->clearMenuCache();
        }

        // ---------------------------------------

        if ($this->helperFactory->getObject('Module')->isDisabled()) {
            $this->processModuleDisable($menuModel);
            return $menuModel;
        }

        if ($this->helperFactory->getObject('Component\Ebay')->isEnabled()) {
            $this->processWizard($menuModel->get(Ebay::MENU_ROOT_NODE_NICK), Ebay::NICK);
        } else {
            $menuModel->remove(Ebay::MENU_ROOT_NODE_NICK);
        }

        if ($this->helperFactory->getObject('Component\Amazon')->isEnabled()) {
            $this->processWizard($menuModel->get(Amazon::MENU_ROOT_NODE_NICK), Amazon::NICK);
        } else {
            $menuModel->remove(Amazon::MENU_ROOT_NODE_NICK);
        }

        if ($this->helperFactory->getObject('Component\Walmart')->isEnabled()) {
            $this->processWizard($menuModel->get(Walmart::MENU_ROOT_NODE_NICK), Walmart::NICK);
        } else {
            $menuModel->remove(Walmart::MENU_ROOT_NODE_NICK);
        }

        return $menuModel;
    }

    //########################################

    private function processMaintenance(\Magento\Backend\Model\Menu $menuModel)
    {
        $ebayMenuModel = $menuModel->get(Ebay::MENU_ROOT_NODE_NICK);
        $amazonMenuModel = $menuModel->get(Amazon::MENU_ROOT_NODE_NICK);

        if ($ebayMenuModel !== null && $ebayMenuModel->isAllowed()) {
            $maintenanceMenuItemResource = Ebay::MENU_ROOT_NODE_NICK;
        } elseif ($amazonMenuModel !== null && $amazonMenuModel->isAllowed()) {
            $maintenanceMenuItemResource = Amazon::MENU_ROOT_NODE_NICK;
        } else {
            $maintenanceMenuItemResource = Walmart::MENU_ROOT_NODE_NICK;
        }

        foreach ($menuModel as $menuIndex => $menuItem) {
            if ($menuItem->getId() == $maintenanceMenuItemResource) {
                $maintenanceMenuItem = $this->itemFactory->create([
                    'id'       => Maintenance::MENU_ROOT_NODE_NICK,
                    'module'   => Module::IDENTIFIER,
                    'title'    => 'M2E Pro',
                    'resource' => $maintenanceMenuItemResource,
                    'action'   => 'm2epro/maintenance',
                ]);

                $menuModel->remove($maintenanceMenuItemResource);
                $menuModel->add($maintenanceMenuItem, null, $menuIndex);
                break;
            }
        }

        $this->processModuleDisable($menuModel);
    }

    private function processModuleDisable(\Magento\Backend\Model\Menu $menuModel)
    {
        $menuModel->remove(Ebay::MENU_ROOT_NODE_NICK);
        $menuModel->remove(Amazon::MENU_ROOT_NODE_NICK);
        $menuModel->remove(Walmart::MENU_ROOT_NODE_NICK);
    }

    /**
     * @param \Magento\Backend\Model\Menu\Item|null $menu
     * @param string $viewNick
     *
     * @return void
     */
    private function processWizard(?\Magento\Backend\Model\Menu\Item $menu, string $viewNick): void
    {
        if ($menu === null) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Wizard $activeBlocker */
        $activeBlocker = $this->helperFactory->getObject('Module\Wizard')->getActiveBlockerWizard($viewNick);

        if ($activeBlocker === null) {
            return;
        }

        $menu->getChildren()->exchangeArray([]);

        $actionUrl = 'm2epro/wizard_' . $activeBlocker->getNick();

        if ($activeBlocker instanceof \Ess\M2ePro\Model\Wizard\MigrationFromMagento1 ||
            $activeBlocker instanceof \Ess\M2ePro\Model\Wizard\MigrationToInnodb) {
            $actionUrl .= '/index/referrer/' . $viewNick;
        }

        $menu->setAction($actionUrl);
    }

    private function buildMenuStateData()
    {
        return [
            Module::IDENTIFIER => [
                $this->helperFactory->getObject('Module')->isDisabled()
            ],
            Ebay::MENU_ROOT_NODE_NICK => [
                $this->helperFactory->getObject('Component\Ebay')->isEnabled(),
                $this->helperFactory->getObject('Module\Wizard')->getActiveBlockerWizard(Ebay::NICK) === null
            ],
            Amazon::MENU_ROOT_NODE_NICK => [
                $this->helperFactory->getObject('Component\Amazon')->isEnabled(),
                $this->helperFactory->getObject('Module\Wizard')->getActiveBlockerWizard(Amazon::NICK) === null
            ],
            Walmart::MENU_ROOT_NODE_NICK => [
                $this->helperFactory->getObject('Component\Walmart')->isEnabled(),
                $this->helperFactory->getObject('Module\Wizard')->getActiveBlockerWizard(Walmart::NICK) === null
            ]
        ];
    }

    //########################################
}
