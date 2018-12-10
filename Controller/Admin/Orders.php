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
 * $Id: oxprobs_orders.php jobarthel@gmail.com $
 *
 */

namespace Job963\Oxid\HealthCheck\Controller\Admin;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Registry;

class Orders extends AdminDetailsController
{
    protected $_sThisTemplate = "oxprobs_orders.tpl";

    /**
     *
     * @return type
     */
    public function render()
    {
        parent::render();
        $myConfig = Registry::get(Config::class);

        $aIncReports = [];
        $aIncFiles = $this->getConfigFiles('orders');
        if (count($aIncFiles) > 0) {
            foreach ($aIncFiles as $sIncFile) {
                require $sIncFile;
            }
        }

        $cReportType = $this->getConfig()->getRequestParameter('oxprobs_reporttype');
        if (empty($cReportType)) {
            $cReportType = "readyorders";
        }

        $this->_aViewData["ReportType"] = $cReportType;

        $aOrders = $this->_retrieveData();

        $oModule = oxNew(Module::class);
        $oModule->load('oxprobs');
        $this->_aViewData["sModuleId"] = $oModule->getId();
        $this->_aViewData["sModuleVersion"] = $oModule->getInfo('version');
        $this->_aViewData["sIsoLang"] = Registry::getLang()->getLanguageAbbr($iLang);

        $this->_aViewData["editClassName"] = $cClass;
        $this->_aViewData["aOrders"] = $aOrders;
        $this->_aViewData["aIncReports"] = $aIncReports;

        return $this->_sThisTemplate;
    }

    /**
     *
     * @return type
     */
    public function downloadResult()
    {
        $aOrders = $this->_retrieveData();

        $aSelOxid = $this->getConfig()->getRequestParameter("oxprobs_oxid");

        $sContent = '';
        foreach ($aOrders as $aOrder) {
            if (in_array($aOrder['oxid'], $aSelOxid)) {
                $sContent .= '"' . implode('","', $aOrder) . '"' . chr(13);
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
     *
     * @return array
     */
    private function _retrieveData()
    {

        $cReportType = $this->getConfig()->getRequestParameter('oxprobs_reporttype');
        if (empty($cReportType)) {
            $cReportType = "readyorders";
        }

        $myConfig = Registry::get(Config::class);
        $this->ean = $myConfig->getConfigParam("sOxProbsEANField");
        $this->minDescLen = (int) $myConfig->getConfigParam("sOxProbsMinDescLen");
        $this->bpriceMin = (float) $myConfig->getConfigParam("sOxProbsBPriceMin");

        $whereShopId = "";
        if (is_string($this->_aViewData["oViewConf"]->getActiveShopId())) {
            // This is a CE or PE Shop
            $sShopId = $this->_aViewData["oViewConf"]->getActiveShopId();
            $whereShopId = " AND o.oxshopid = '$sShopId' ";
        } else {
            // This is a EE Shop
            $iShopId = $this->_aViewData["oViewConf"]->getActiveShopId();
            $whereShopId = " AND o.oxshopid = $iShopId ";

        }

        switch ($cReportType) {
            case 'readyorders':
            case 'opencia':
            case 'openinv':
                $txtIgnoreRemark = $myConfig->getConfigParam("sOxProbsOrderIgnoredRemark"); //"Hier k%nnen Sie uns noch etwas mitteilen.";
                if ($cReportType == 'readyorders') {
                    $payTypeList = "'" . implode("','", explode(',', $myConfig->getConfigParam("sOxProbsOrderPaidLater"))) . "'"; //"oxidinvoice,oxidcashondel";
                    $whereCondition = "AND ((o.oxpaid != '0000-00-00 00:00:00') OR (o.oxpaymenttype IN ({$payTypeList}))) "
                        . "AND o.oxsenddate = '0000-00-00 00:00:00' ";
                } elseif ($cReportType == 'opencia') {
                    $payTypeList = "'" . implode("','", explode(',', $myConfig->getConfigParam("sOxProbsOrderPaidbyCIA"))) . "'"; //"oxidpayadvance";
                    $whereCondition = "AND ((o.oxpaid != '0000-00-00 00:00:00') AND (o.oxpaymenttype IN ({$payTypeList}))) "
                        . "AND o.oxsenddate = '0000-00-00 00:00:00' ";
                } elseif ($cReportType == 'openinv') {
                    $payTypeList = "'" . implode("','", explode(',', $myConfig->getConfigParam("sOxProbsOrderPaidbyInvoice"))) . "'"; //"oxidinvoice";
                    $whereCondition = "AND ((o.oxpaid != '0000-00-00 00:00:00') AND (o.oxpaymenttype IN ({$payTypeList}))) "
                        . "AND o.oxsenddate != '0000-00-00 00:00:00' ";
                } else {
                    // nothing to do
                }

                $sSql1 = "SELECT o.oxid AS oxid, o.oxordernr AS orderno, o.oxtotalordersum AS ordersum, o.oxbillsal AS salutation, "
                    . "CONCAT('<nobr>', o.oxbillcompany, '</nobr>') AS company, "
                    . "CONCAT('<a href=\"mailto:', o.oxbillemail, '\" style=\"text-decoration:underline;\"><nobr>', o.oxbillfname, '&nbsp;', o.oxbilllname, '</nobr></a>') AS name, "
                    . "IF (o.oxdelcity = '', "
                    . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxbillstreet,'+',o.oxbillstreetnr,',+',o.oxbillzip,'+',o.oxbillcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxbillzip, '&nbsp;', o.oxbillcity, '</a>'), "
                    . "CONCAT('<a href=\"http://maps.google.com/maps?f=q&hl=de&geocode=&q=', o.oxdelstreet,'+',o.oxdelstreetnr,',+',o.oxdelzip,'+',o.oxdelcity,'&z=10\" style=\"text-decoration:underline;\" target=\"_blank\">', o.oxdelzip, '&nbsp;', o.oxdelcity, '</a>') "
                    . ") AS  custdeladdr, "
                    . "p.oxdesc AS paytype, "
                    . "GROUP_CONCAT(CONCAT('<nobr>', a.oxamount, ' x ', a.oxtitle, IF (a.oxselvariant != '', CONCAT(' &ndash; ', a.oxselvariant), ''), '</nobr>') SEPARATOR '<br>') AS orderlist, "
                    . "(TO_DAYS(NOW())-TO_DAYS(o.oxorderdate)) AS days, DATE(o.oxorderdate) AS orderdate , "
                    . "IF(o.oxremark!='', "
                    . "IF((SELECT o.oxremark LIKE '{$txtIgnoreRemark}') != 1,"
                    . "o.oxremark, "
                    . "''"
                    . "), "
                    . "''"
                    . ") AS remark "
                    . "FROM oxorder o, oxpayments p, oxorderarticles a "
                    . "WHERE o.oxpaymenttype = p.oxid "
                    . "AND o.oxid = a.oxorderid  "
                    . $whereCondition
                    . "AND o.oxstorno = 0 "
                    . $whereShopId
                    . "GROUP BY o.oxordernr "
                    . "ORDER BY o.oxordernr DESC ";

                $cClass = 'actions';
                break;

            default:
                $sSql1 = '';
                $sSql2 = '';
                if (count($myConfig->getConfigParam("aOxProbsOrdersIncludeFiles")) != 0) {
                    $aIncFiles = $myConfig->getConfigParam("aOxProbsOrdersIncludeFiles");
                    $sIncPath = $this->jxGetModulePath() . '/application/controllers/admin/';
                    foreach ($aIncFiles as $sIncFile) {
                        $sIncFile = $sIncPath . 'oxprobs_orders_' . $sIncFile . '.inc.php';
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

        $aOrders = [];

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
                    array_push($aOrders, $rs->getFields());
                    $rs->fetchRow();
                }
            }
        }

        return $aOrders;
    }
}
