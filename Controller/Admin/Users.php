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
 * $Id: oxprobs_users.php jobarthel@gmail.com $
 *
 */

namespace Job963\Oxid\HealthCheck\Controller\Admin;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Module\Module;
use OxidEsales\Eshop\Core\Registry;

class Users extends AdminDetailsController
{
    protected $_sThisTemplate = "oxprobs_users.tpl";

    /**
     *
     * @return type
     */
    public function render()
    {
        ini_set('display_errors', true);

        parent::render();
        $cClass = 'admin_user';
        $myConfig = Registry::get(Config::class);

        $aIncReports = [];
        $aIncFiles = $this->getConfigFiles('groups');
        if (count($aIncFiles) > 0) {
            foreach ($aIncFiles as $sIncFile) {
                require $sIncFile;
            }
        }

        $cReportType = $this->getConfig()->getRequestParameter('oxprobs_reporttype');
        if (empty($cReportType)) {
            $cReportType = "dblname";
        }
        $this->_aViewData["ReportType"] = $cReportType;

        $aUsers = [];
        $aUsers = $this->_retrieveData();

        $oModule = oxNew(Module::class);
        $oModule->load('oxprobs');
        $this->_aViewData["sModuleId"] = $oModule->getId();
        $this->_aViewData["sModuleVersion"] = $oModule->getInfo('version');
        $this->_aViewData["sIsoLang"] = Registry::getLang()->getLanguageAbbr($iLang);

        $this->_aViewData["editClassName"] = $cClass;
        $this->_aViewData["aUsers"] = $aUsers;
        $this->_aViewData["aIncReports"] = $aIncReports;

        return $this->_sThisTemplate;
    }

    /**
     *
     * @return type
     */
    public function downloadResult()
    {
        $aUsers = $this->_retrieveData();

        $aSelOxid = $this->getConfig()->getRequestParameter("oxprobs_oxid");

        $sContent = '';
        foreach ($aUsers as $aUser) {
            if (in_array($aUser['oxid'], $aSelOxid)) {
                $aLogins = $aUser['logins'];
                $sContent .= '"' . $aUser['name'] . '","' . $aUser['amount'] . '"';
                foreach ($aLogins as $aLogin) {
                    $sContent .= ',"' . $aLogin['oxusername'] . '"';
                }
                $sContent .= chr(13);
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
            $cReportType = "dblname";
        }

        $myConfig = Registry::get(Config::class);
        $this->ean = $myConfig->getConfigParam("sOxProbsEANField");
        $this->minDescLen = (int) $myConfig->getConfigParam("sOxProbsMinDescLen");
        $this->bpriceMin = (float) $myConfig->getConfigParam("sOxProbsBPriceMin");

        $sWhere = "";
        if (is_string($this->_aViewData["oViewConf"]->getActiveShopId())) {
            // This is a CE or PE Shop
            $sShopId = $this->_aViewData["oViewConf"]->getActiveShopId();
            $sWhere = $sWhere . " u.oxshopid = '$sShopId' ";
        } else {
            // This is a EE Shop
            $iShopId = $this->_aViewData["oViewConf"]->getActiveShopId();
            $sWhere = $sWhere . " u.oxshopid = $iShopId ";

        }

        switch ($cReportType) {
            case 'dblname':
                $sName = "CONCAT(TRIM(u.oxfname), ' ', TRIM(u.oxlname), ', ', TRIM(u.oxcity))";
                $sMatch = "CONCAT(TRIM(u.oxfname), ' ', TRIM(u.oxlname), ', ', TRIM(u.oxcity))";
                $sSql1 = "SELECT u.oxactive AS oxactive, $sName AS name, COUNT(*) AS amount, $sMatch AS matchstring "
                    . "FROM oxuser u "
                    . "WHERE $sWhere "
                    . "GROUP BY name "
                    . "HAVING COUNT(*) > 1 ";
                $sSql2 = "SELECT u.oxid, u.oxactive, u.oxusername, n.oxdboptin "
                    . "FROM oxuser u, oxnewssubscribed n "
                    . "WHERE $sMatch = '@MATCH@' "
                    . "AND u.oxid = n.oxuserid "
                    . "AND $sWhere ";
                break;

            case 'dbladdr':
                $sName = "CONCAT( REPLACE(REPLACE(REPLACE(u.oxstreet,'.',''),' ',''),'-','') , ', ', TRIM(u.oxcity))";
                $sMatch = "CONCAT( REPLACE(REPLACE(REPLACE(u.oxstreet,'.',''),' ',''),'-','') , ', ', TRIM(u.oxcity))";
                $sSql1 = "SELECT u.oxactive AS oxactive, $sName AS name, COUNT(*) AS amount, $sMatch AS matchstring "
                    . "FROM oxuser u "
                    . "WHERE $sWhere "
                    . "GROUP BY name "
                    . "HAVING COUNT(*) >  1";
                $sSql2 = "SELECT u.oxid, u.oxactive, u.oxusername, n.oxdboptin "
                    . "FROM oxuser u, oxnewssubscribed n "
                    . "WHERE $sMatch = '@MATCH@' "
                    . "AND u.oxid = n.oxuserid "
                    . "AND $sWhere ";
                break;

            case 'invcats':
                $sSql1 = 'SELECT c.oxactive AS oxactive, c.oxid AS oxid, c.oxtitle AS oxtitle, COUNT(*) AS count, '
                    . 'CONCAT_WS(\'|\', '
                    . 'IF(c.oxactive = 0, \'OXPROBS_DEACT_CATS\', \'\'), '
                    . 'IF((SELECT c1.oxactive FROM oxcategories c1 WHERE c1.oxid = c.oxparentid) = 0, \'OXPROBS_DEACT_PARENTCAT\', \'\'), '
                    . 'IF((SELECT c2.oxactive FROM oxcategories c2, oxcategories c1 WHERE c1.oxid = c.oxparentid AND c2.oxid = c1.oxparentid AND c2.oxactive = 0) = 0, \'OXPROBS_DEACT_GRANDCAT\', \'\') '
                    . ') AS status '
                    . 'FROM oxarticles a, oxobject2category o2a, oxcategories c '
                    . 'WHERE a.oxid = o2a.oxobjectid AND c.oxid = o2a.oxcatnid '
                    . 'AND ('
                    . 'c.oxactive = 0 '
                    . 'OR (SELECT c1.oxactive FROM oxcategories c1 WHERE c1.oxid = c.oxparentid AND c1.oxactive = 0) = 0 '
                    . 'OR (SELECT c2.oxactive FROM oxcategories c2, oxcategories c1 WHERE c1.oxid = c.oxparentid AND c2.oxid = c1.oxparentid AND c2.oxactive = 0) = 0 '
                    . ') '
                    . 'AND a.oxactive = 1 '
                    . 'GROUP BY c.oxtitle ';
                $sSql2 = '';
                $cClass = 'category';
                break;

            default:
                $sSql1 = '';
                $sSql2 = '';
                $aIncFiles = [];
                $aIncReports = [];
                if (count($myConfig->getConfigParam("aOxProbsUsersIncludeFiles")) != 0) {
                    $aIncFiles = $myConfig->getConfigParam("aOxProbsUsersIncludeFiles");
                    $sIncPath = $this->jxGetModulePath() . '/application/controllers/admin/';
                    foreach ($aIncFiles as $sIncFile) {
                        $sIncFile = $sIncPath . 'oxprobs_users_' . $sIncFile . '.inc.php';
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
        $aUsers = [];

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
                    array_push($aUsers, $rs->getFields());
                    $rs->fetchRow();
                }
            }
        }

        if (!empty($sSql2)) {
            $oDb = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
            foreach ($aUsers as $key => $row) {
                $aLogins = [];
                $sSql = str_replace('@MATCH@', $row['matchstring'], $sSql2);
                $rs = $oDb->select($sSql);
                if ($rs != false && $rs->count() > 0) {
                    while (!$rs->EOF) {
                        array_push($aLogins, $rs->getFields());
                        $rs->fetchRow();
                    }
                }
                $aUsers[$key]['logins'] = $aLogins;
            }
        }

        return $aUsers;
    }
}
