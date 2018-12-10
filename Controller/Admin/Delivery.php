<?php

/*
 *    This file is part of the module oxProbs for OXID eShop Community Edition.
 *
 *    The module oxProbs for OXID eShop Community Edition is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    The module oxProbs for OXID eShop Community Edition is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with OXID eShop Community Edition.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link    https://github.com/job963/oxProbs
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @copyright (C) Joachim Barthel 2012-2017
 *
 * $Id: oxprobs_delivery.php jobarthel@gmail.com $
 *
 */

namespace Job963\Oxid\HealthCheck\Controller\Admin;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Registry;

class Delivery extends AdminDetailsController
{
    protected $_sThisTemplate = "oxprobs_delivery.tpl";

    /**
     *
     * @return type
     */
    public function render()
    {
        parent::render();
        $myConfig = Registry::get(Config::class);

        $aIncReports = [];
        $aIncFiles = $this->getConfigFiles('delivery');
        if (count($aIncFiles) > 0) {
            foreach ($aIncFiles as $sIncFile) {
                require $sIncFile;
            }
        }

        $cReportType = $this->getConfig()->getRequestParameter('oxprobs_reporttype');
        if (empty($cReportType)) {
            $cReportType = "delsetcost";
        }

        $this->_aViewData["ReportType"] = $cReportType;

        $aList = $this->_retrieveData();

        $oModule = oxNew(Module::class);
        $oModule->load('oxprobs');
        $this->_aViewData["sModuleId"] = $oModule->getId();
        $this->_aViewData["sModuleVersion"] = $oModule->getInfo('version');
        $this->_aViewData["sIsoLang"] = Registry::getLang()->getLanguageAbbr($iLang);

        $this->_aViewData["editClassName"] = $cClass;
        $this->_aViewData["aList"] = $aList;
        $this->_aViewData["aIncReports"] = $aIncReports;

        return $this->_sThisTemplate;
    }

    /**
     * Downloads the selected data as CSV file
     *
     * @return null
     */
    public function downloadResult()
    {
        $aItems = $this->_retrieveData();

        $aSelOxid = $this->getConfig()->getRequestParameter("oxprobs_oxid");

        $sContent = '';
        foreach ($aItems as $aItem) {
            if (in_array($aItem['oxid'], $aSelOxid)) {
                $sContent .= '"' . implode('","', $aItem) . '"' . chr(13);
            }
        }

        header("Content-Type: text/plain");
        header("content-length: " . strlen($sContent));
        header("Content-Disposition: attachment; filename=\"problem-report.csv\"");
        echo $sContent;

        exit();

        return;
    }

    /**
     * Retrieves and analyzes shipping data
     *
     * @return array $aList     Shipping analysis data
     */
    private function _retrieveData()
    {

        $cReportType = $this->getConfig()->getRequestParameter('oxprobs_reporttype');
        if (empty($cReportType)) {
            $cReportType = "delsetcost";
        }

        $myConfig = Registry::get(Config::class);
        $this->ean = $myConfig->getConfigParam("sOxProbsEANField");
        $this->minDescLen = (int) $myConfig->getConfigParam("sOxProbsMinDescLen");
        $this->bpriceMin = (float) $myConfig->getConfigParam("sOxProbsBPriceMin");

        $sWhere = "";
        if (is_string($this->_aViewData["oViewConf"]->getActiveShopId())) {
            // This is a CE or PE Shop
            $sShopId = $this->_aViewData["oViewConf"]->getActiveShopId();
            $sWhere = $sWhere . " AND ds.oxshopid = '$sShopId' ";
        } else {
            // This is a EE Shop
            $iShopId = $this->_aViewData["oViewConf"]->getActiveShopId();
            $sWhere = $sWhere . " AND ds.oxshopid = $iShopId ";

        }

        switch ($cReportType) {
            case 'delsetcost':
                $sSql1 = 'SELECT c.oxtitle AS country, o2d.oxdeliveryid, o2d.oxobjectid, ds.oxtitle AS deliveryset, ds.oxpos, '
                    . 'd.oxtitle AS deliveryrule, d.oxaddsum AS addsum, d.oxaddsumtype AS addtype, d.oxparam As startval, d.oxparamend AS endval '
                    . 'FROM oxcountry c, oxobject2delivery o2d, oxdeliveryset ds, oxdel2delset d2d, oxdelivery d '
                    . 'WHERE o2d.oxtype=\'oxdelset\' '
                    . 'AND c.oxid=o2d.oxobjectid '
                    . 'AND o2d.oxdeliveryid=ds.oxid '
                    . 'AND d2d.OXDELID=d.oxid '
                    . 'AND d2d.OXDELSETID=ds.OXID '
                    . 'AND c.oxactive=1 AND d.oxactive=1 AND ds.oxactive=1 '
                    . $sWhere
                    . 'ORDER BY c.oxtitle, ds.oxtitle, d.oxtitle ';
                $sSql2 = '';
                $sSql3 = '';
                $cClass = '---';
                break;

            case 'delsetpay':
                $sSql1 = 'SELECT c.oxid AS countryid, c.oxtitle AS country, o2d.oxdeliveryid, o2d.oxobjectid, ds.oxid AS delsetid, '
                    . 'ds.oxtitle AS deliveryset, ds.oxpos, p.oxid AS paymentid, p.oxdesc AS payment, p.oxaddsum AS addsum, '
                    . 'p.oxfromamount AS startval, p.oxtoamount AS endval, p.oxaddsumtype AS addtype '
                    . 'FROM oxcountry c, oxobject2delivery o2d, oxdeliveryset ds, oxobject2payment o2p, oxpayments p '
                    . 'WHERE o2d.oxtype=\'oxdelset\' '
                    . 'AND c.oxid=o2d.oxobjectid '
                    . 'AND o2d.oxdeliveryid=ds.oxid '
                    . 'AND o2p.oxobjectid=ds.oxid '
                    . 'AND o2p.oxpaymentid=p.oxid '
                    . 'AND c.oxactive=1 AND ds.oxactive=1 AND p.oxactive=1 '
                    . $sWhere
                    . 'ORDER BY c.oxtitle, ds.oxtitle, p.oxdesc ';
                $sSql2 = '';
                $sSql3 = '';
                $cClass = '---';
                break;

            case 'custpay':
                $sSql1 = "SELECT c.oxtitle AS country, g.oxtitle AS deliveryset /*usergroup*/, p.oxdesc AS payment, "
                . "p.oxaddsum AS addsum, p.oxaddsumtype AS addtype, p.oxfromamount AS startval, p.oxtoamount AS endval "
                . "FROM oxcountry c, oxobject2group o2g, oxgroups g, oxpayments p, oxobject2payment o2p "
                . "WHERE c.oxid = o2p.oxobjectid "
                . "AND g.oxid = o2g.oxgroupsid "
                . "AND p.oxid = o2g.oxobjectid "
                . "AND p.oxid = o2p.oxpaymentid "
                . "AND c.oxactive = 1 "
                . "AND g.oxactive = 1 "
                . "AND p.oxactive = 1 "
                . "AND o2g.oxshopid = '" . $this->_aViewData["oViewConf"]->getActiveShopId() . "' "
                    . "ORDER BY c.oxtitle, g.oxtitle, p.oxdesc ";
                $sSql2 = '';
                $sSql3 = '';
                $cClass = '---';
                break;

            default:
                $sSql = '';
                $aIncReports = [];
                if (count($myConfig->getConfigParam("aOxProbsDeliveryIncludeFiles")) != 0) {
                    $aIncFiles = $myConfig->getConfigParam("sOxProbsDeliveryIncludeFiles");
                    $sIncPath = $this->jxGetModulePath() . '/application/controllers/admin/';
                    foreach ($aIncFiles as $sIncFile) {
                        $sIncFile = $sIncPath . 'oxprobs_delivery_' . $sIncFile . '.inc.php';
                        try {
                            require $sIncFile;
                        } catch (\Exception $e) {
                            echo $e->getMessage();
                            die();
                        }
                    }
                }
                break;

        }

        $i = 0;
        $aList = $aDelSets = $aDelCosts = [];

        if (!empty($sSql1)) {
            $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

            try {
                $rs = $oDb->select($sSql1);
            } catch (\Exception $e) {
                echo '<div style="border:2px solid #dd0000;margin:10px;padding:5px;background-color:#ffdddd;font-family:sans-serif;font-size:14px;">';
                echo '<b>SQL-Error ' . $e->getCode() . ' in SQL statement</b><br />' . $e->getMessage() . '';
                echo '</div>';
                return;
            }

            if ($rs != false && $rs->count() > 0) {
                while (!$rs->EOF) {
                    array_push($aList, $rs->getFields());
                    $rs->fetchRow();
                }
            }
        }

        if (!empty($sSql2)) {
            $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

            try {
                $rs = $oDb->select($sSql2);
            } catch (\Exception $e) {
                echo '<div style="border:2px solid #dd0000;margin:10px;padding:5px;background-color:#ffdddd;font-family:sans-serif;font-size:14px;">';
                echo '<b>SQL-Error ' . $e->getCode() . ' in SQL statement</b><br />' . $e->getMessage() . '';
                echo '</div>';
                return;
            }

            if ($rs != false && $rs->count() > 0) {
                while (!$rs->EOF) {
                    array_push($aDelSets, $rs->getFields());
                    $rs->fetchRow();
                }
            }
        }

        if (!empty($sSql3)) {
            $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);

            try {
                $rs = $oDb->select($sSql3);
            } catch (\Exception $e) {
                echo '<div style="border:2px solid #dd0000;margin:10px;padding:5px;background-color:#ffdddd;font-family:sans-serif;font-size:14px;">';
                echo '<b>SQL-Error ' . $e->getCode() . ' in SQL statement</b><br />' . $e->getMessage() . '';
                echo '</div>';
                return;
            }

            if ($rs != false && $rs->count() > 0) {
                while (!$rs->EOF) {
                    array_push($aDelCosts, $rs->getFields());
                    $rs->fetchRow();
                }
            }
        }

        return $aList;
    }
}
