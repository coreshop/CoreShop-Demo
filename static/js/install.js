/**
 * CoreShopDemo
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

pimcore.registerNS('pimcore.plugin.coreshopdemo.install');
pimcore.plugin.coreshopdemo.install = Class.create({

    initialize: function () {
        Ext.MessageBox.confirm(t('coreshopdemo_install'), t('coreshopdemo_install_demo'), function (buttonValue) {
            if (buttonValue == 'yes')
            {
                pimcore.helpers.loadingShow();

                Ext.Ajax.request({
                    url: '/plugin/CoreShopDemo/admin_install/install-demo',
                    method: 'post',
                    success: function (response) {
                        var data = Ext.decode(response.responseText);

                        pimcore.helpers.loadingHide();

                        if (data.success) {
                            Ext.MessageBox.alert(t('info'), t('coreshopdemo_installed_successfully'));
                        } else {
                            Ext.MessageBox.alert(t('alert'), data.message);
                        }
                    }
                });
            }
        }.bind(this));
    }
});
