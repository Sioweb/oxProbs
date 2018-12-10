<?php

namespace Job963\Oxid\HealthCheck\Controller\Admin;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController as BaseAdminDetailsController;

class AdminDetailsController extends BaseAdminDetailsController
{
    /**
     *
     * @return array
     */
    protected function getConfigFiles($type)
    {
        $Files = [];
        if(is_dir(($Path = $this->jxGetModulePath() . '/' . $type . '/'))) {
            $Files = array_diff(scandir($Path), ['.', '..']);
            $Files = preg_filter('/^/', $Path, $Files);
        }


        return $Files;
    }

    /**
     *
     * @return string
     */
    protected function jxGetModulePath()
    {
        $sModuleId = $this->getEditObjectId();

        $this->_aViewData['oxid'] = $sModuleId;

        $oModule = oxNew(Module::class);
        $oModule->load($sModuleId);
        $sModuleId = $oModule->getId();

        $myConfig = Registry::get(Config::class);
        $sModulePath = $myConfig->getConfigParam("sShopDir") . 'Application/oxprobs';

        if (!is_dir($sModulePath)) {
            mkdir($sModulePath, 0755, true);
        }

        return $sModulePath;
    }
}
