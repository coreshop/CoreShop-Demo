<?php
/**
 * CoreShopDemo.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2016 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShopDemo\Install;

use CoreShop\Model\Category;
use CoreShop\Model\Product;
use CoreShop\Model\Tax;
use CoreShop\Model\TaxRule;
use CoreShop\Model\TaxRuleGroup;
use Pimcore\File;
use Pimcore\Model\Object;

/**
 * Class Demo
 * @package CoreShop\Plugin\Install
 */
class Demo
{
    /**
     * Installs Demo Tax Rules
     *
     * @param $json
     */
    public function installDemoDataTaxes($json) {
        $file = PIMCORE_PLUGINS_PATH."/CoreShopDemo/data/demo/$json.json";

        if (file_exists($file)) {
            $config = \Zend_Json::decode(file_get_contents($file));

            foreach ($config as $values) {
                $taxRuleGroup = new Tax();

                $taxRuleGroup->setName($values['name']['de'], 'de');
                $taxRuleGroup->setName($values['name']['en'], 'en');
                $taxRuleGroup->setRate($values['rate']);
                $taxRuleGroup->setActive($values['active']);
            }
        }
    }

    /**
     * Installs Demo Tax Rules
     *
     * @param $json
     */
    public function installDemoDataTaxRules($json) {
        $file = PIMCORE_PLUGINS_PATH."/CoreShopDemo/data/demo/$json.json";

        if (file_exists($file)) {
            $config = \Zend_Json::decode(file_get_contents($file));

            $taxes = Tax::getList();
            $taxes->load();
            $taxes = $taxes->getData();

            foreach ($config as $values) {
                $taxRuleGroup = new TaxRuleGroup();

                $taxRuleGroup->setName($values['name']);
                $taxRuleGroup->setActive($values['active']);
                $taxRuleGroup->setShopIds([1]);
                $taxRuleGroup->save();

                foreach($values['rules']['rule'] as $rule) {
                    $tax = null;

                    foreach($taxes as $taxesTax) {
                        if(strtolower($taxesTax->getName("en")) === strtolower($rule['tax'])) {
                            $tax = $taxesTax;
                            break;
                        }
                    }

                    $taxRule = new TaxRule();
                    $taxRule->setCountryId($rule['country']);
                    $taxRule->setStateId($rule['state']);
                    $taxRule->setTax($tax);
                    $taxRule->setBehavior($rule['behaviour']);
                    $taxRule->setTaxRuleGroup($taxRuleGroup);
                    $taxRule->save();
                }
            }
        }
    }

    /**
     * Installs Demo Tax Rules
     *
     * @param $json
     */
    public function installDemoDataCategories($json) {
        $file = PIMCORE_PLUGINS_PATH."/CoreShopDemo/data/demo/$json.json";

        if (file_exists($file)) {
            $config = \Zend_Json::decode(file_get_contents($file));

            foreach ($config as $values) {
                $this->installCategory($values, null);
            }
        }
    }

    /**
     * @param $data
     * @param null $parent
     */
    protected function installCategory($data, $parent = null) {
        if($parent instanceof Category) {
            $path = $parent->getFullPath();
            $name = File::getValidFilename($data['name']['en']);
            $fullPath = $path . "/" . $name;
        }
        else {
            $path = Object\Service::createFolderByPath("/coreshop-demo/categories");
            $name = File::getValidFilename($data['name']['en']);
            $fullPath = $path->getFullPath() . "/" . $name;
        }

        $category = Category::getByPath($fullPath);

        if (!$category instanceof Category) {
            $category = Category::create();
        }

        if($parent instanceof Category) {
            $category->setParentCategory($parent);
            $category->setParent($parent);
        }
        else {
            $category->setParent($path);
        }

        $category->setName($data['name']['en'], "en");
        $category->setName($data['name']['de'], "de");
        $category->setKey($name);
        $category->setPublished(true);
        $category->save();

        if (is_array($data['childs'])) {
            foreach ($data['childs'] as $child) {
                $this->installCategory($child, $category);
            }
        }
    }

    /**
     * Installs Demo Tax Rules
     *
     * @param $json
     */
    public function installDemoDataProducts($json) {
        $file = PIMCORE_PLUGINS_PATH."/CoreShopDemo/data/demo/$json.json";

        if (file_exists($file)) {
            $products = \Zend_Json::decode(file_get_contents($file));

            foreach ($products as $values) {
                $questionMarks = str_repeat("?,", count($values['categories'])-1) . "?";
                $listing = Category::getList();
                $listing->setCondition("name in ($questionMarks) AND o_path LIKE '/coreshop-demo/%'", $values['categories']);
                $listing->setLocale("en");
                $listing->load();

                $categories = $listing->getObjects();

                if(is_array($categories) && count($categories) > 0) {
                    $parent = Object\Service::createFolderByPath(str_replace("categories", "products", $categories[0]->getFullPath()));
                }
                else {
                    $parent = Object\Service::createFolderByPath("/coreshop-demo/products");
                }
                $key = File::getValidFilename($values['name']['en']);
                $fullPath = $parent->getFullPath() . "/" . $key;

                $product = Product::getByPath($fullPath);

                if(!$product instanceof Product) {
                    $product = Product::create();
                }

                $product->setName($values['name']['en'], "en");
                $product->setName($values['name']['de'], "de");
                $product->setType($values['type']);
                $product->setShortDescription($values['shortDescription']['en'], "en");
                $product->setShortDescription($values['shortDescription']['de'], "de");
                $product->setDescription($values['description']['en'], "en");
                $product->setDescription($values['description']['de'], "de");
                $product->setEan($values['ean']);
                $product->setArticleNumber($values['articleNumber']);
                $product->setEnabled($values['enabled']);
                $product->setAvailableForOrder($values['availableForOrder']);
                $product->setCategories($listing->getObjects());
                $product->setWholesalePrice($values['wholesalePrice']);
                $product->setRetailPrice($values['retailPrice']);
                $product->setTaxRule(TaxRuleGroup::getByField("name", $values['taxRule']));
                $product->setKey($key);
                $product->setParent($parent);
                $product->setPublished(true);

                $product->save();
            }
        }
    }
}
