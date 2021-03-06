<?php
/**
 * CoreShop.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

use CoreShop\Plugin;
use CoreShop\Controller\Action\Admin;

/**
 * Class CoreShopDemo_Admin_InstallController
 */
class CoreShopDemo_Admin_InstallController extends Admin
{
    public function installDemoAction() {
        $force = $this->getParam("force", false);

        if(\CoreShop\Model\Configuration::get("CORESHOPDEMO.INSTALLED") && !$force) {
            $this->_helper->json(["success" => false, "message" => "Demo already installed"]);
        }

        $install = new \CoreShopDemo\Install\Demo();

        \Pimcore::getEventManager()->trigger('coreshop.install.demo.pre', null, array('installer' => $install));

        $install->installDemoDataTaxes('taxes');
        $install->installDemoDataTaxRules('taxRules');
        $install->installDemoManufacturers('manufacturers');

        if(\CoreShop\Version::getBuildNumber() < 123) {
            $install->installDemoIndex('indexes');
        }
        else {
            $install->installDemoIndex123('indexes-123');
        }
        $install->installDemoFilter('filters');
        $install->installDemoDataCategories('categories');
        $install->installDemoDataProducts('products');
        $install->installDemoShippingRules('shippingRules');
        $install->installDemoCarrier('carriers');

        \Pimcore::getEventManager()->trigger('coreshop.install.demo.post', null, array('installer' => $install));

        \CoreShop\Model\Configuration::set("CORESHOPDEMO.INSTALLED", true);

        $this->_helper->json(array('success' => true));
    }
}
