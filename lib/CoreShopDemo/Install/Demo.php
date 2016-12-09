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

use CoreShop\Exception;
use CoreShop\Model\Carrier;
use CoreShop\Model\Carrier\ShippingRule;
use CoreShop\Model\Category;
use CoreShop\Model\Index;
use CoreShop\Model\Manufacturer;
use CoreShop\Model\Product;
use CoreShop\Model\Tax;
use CoreShop\Model\TaxRule;
use CoreShop\Model\TaxRuleGroup;
use Pimcore\File;
use Pimcore\Model\Object;
use Pimcore\Tool;

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
                $tax = $this->getTaxByName($values['name']['en']);

                if(!$tax instanceof Tax) {
                    $tax = new Tax();
                }

                $tax->setName($values['name']['de'], 'de');
                $tax->setName($values['name']['en'], 'en');
                $tax->setRate($values['rate']);
                $tax->setActive($values['active']);
                $tax->save();
            }
        }
    }

    /**
     * @param $name
     * @return bool|Tax
     */
    protected function getTaxByName($name) {
        $list = Tax::getList();

        $list->setLocale("en");
        $list->setCondition("name = ?", $name);
        $list = $list->load();

        if(count($list) > 0) {
            return $list[0];
        }

        return false;
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

            foreach ($config as $values) {
                $taxRuleGroup = $this->getTaxRuleGroupByName($values['name']);

                if(!$taxRuleGroup instanceof TaxRuleGroup) {
                    $taxRuleGroup = new TaxRuleGroup();
                }

                $taxRuleGroup->setName($values['name']);
                $taxRuleGroup->setActive($values['active']);
                $taxRuleGroup->setShopIds([1]);
                $taxRuleGroup->save();

                foreach($taxRuleGroup->getRules() as $rule) {
                    $rule->delete();
                }

                foreach($values['rules']['rule'] as $rule) {
                    $tax = $this->getTaxByName($rule['tax']);

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
     * @param $name
     * @return \CoreShop\Model\AbstractModel|null
     */
    protected function getTaxRuleGroupByName($name) {
        return TaxRuleGroup::getByField("name", $name);
    }

    /**
     * @param $json
     */
    public function installDemoManufacturers($json) {
        $file = PIMCORE_PLUGINS_PATH."/CoreShopDemo/data/demo/$json.json";

        if (file_exists($file)) {
            $config = \Zend_Json::decode(file_get_contents($file));

            foreach ($config as $values) {
                $manufacturer = $this->getManufacturerByName($values['name']);

                if(!$manufacturer instanceof Manufacturer) {
                    $manufacturer = Manufacturer::create();
                }

                $manufacturer->setName($values['name']);
                $manufacturer->setPublished(true);
                $manufacturer->setParent(Object\Service::createFolderByPath("/coreshop-demo/manufacturers"));
                $manufacturer->setKey(File::getValidFilename($values['name']));
                $manufacturer->save();
            }
        }
    }

    /**
     * @param $name
     * @return Manufacturer|bool
     */
    protected function getManufacturerByName($name) {
        $list = Manufacturer::getList();
        $list->setCondition("o_path LIKE '/coreshop-demo/%' AND name=?", $name);
        $list = $list->load();

        if(count($list) === 1) {
            return $list[0];
        }

        return false;
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

        if(array_key_exists("filter", $data)) {
            $category->setFilterDefinition(Product\Filter::getByField("name", $data['filter']));
        }

        $category->setName($data['name']['en'], "en");
        $category->setName($data['name']['de'], "de");
        $category->setShops([1]);
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
                $product->setManufacturer($this->getManufacturerByName($values['manufacturer']));
                $product->setShops([1]);
                $product->setKey($key);
                $product->setParent($parent);
                $product->setPublished(true);

                $product->save();
            }
        }
    }

    /**
     * @param $json
     *
     * @throws Exception
     */
    public function installDemoIndex($json) {
        $file = PIMCORE_PLUGINS_PATH."/CoreShopDemo/data/demo/$json.json";

        $prohibitedFieldNames = ["name", "id"];

        if (file_exists($file)) {
            $index = \Zend_Json::decode(file_get_contents($file));

            foreach($index as $values) {
                $index = Index::getByField("name", $values['name']);

                if(!$index instanceof Index) {
                    $index = new Index();
                }

                $index->setName($values['name']);
                $index->setType($values['type']);

                $configClass = '\\CoreShop\\Model\\Index\\Config\\' . ucfirst($values['type']);

                if (Tool::classExists($configClass)) {
                    $config = new $configClass();

                    if ($config instanceof \CoreShop\Model\Index\Config) {
                        $columns = array();

                        foreach ($values['fields'] as $col)
                        {
                            $objectType = ucfirst($col['objectType']);

                            if (!$col['key']) {
                                continue;
                            }

                            $class = null;

                            //Allow Column-Types to be declared in Template and/or Website
                            $columnNamespace = '\\CoreShop\\Model\\Index\\Config\\Column\\';
                            $columnClass = $columnNamespace . $values['type'] . '\\' . $objectType;

                            if (Tool::classExists($columnClass)) {
                                $class = $columnClass;
                            }

                            if (!$class) {
                                //Use fallback column
                                throw new Exception('No config implementation for column with type ' . $objectType . ' found');
                            }

                            $columnObject = new $class();

                            if ($columnObject instanceof \CoreShop\Model\Index\Config\Column\AbstractColumn) {
                                $columnObject->setValues($col);

                                if (in_array($columnObject->getName(), $prohibitedFieldNames)) {
                                    throw new Exception(sprintf('Field Name "%s" is prohibited for indexes', $columnObject->getName()));
                                }

                                $columnObject->validate();

                                $columns[] = $columnObject;
                            }
                        }

                        $config->setColumns($columns);
                        $index->setConfig($config);
                    } else {
                        throw new Exception('Config class for type ' . $values['type'] . ' not instanceof \CoreShop\Model\Index\Config');
                    }

                } else {
                    throw new Exception('Config class for type ' . $values['type'] . ' not found');
                }

                $index->save();

                \CoreShop\IndexService::getIndexService()->getWorker($index->getName())->createOrUpdateIndexStructures();
            }
        }
    }

    /**
     * @param $json
     */
    public function installDemoFilter($json) {
        $file = PIMCORE_PLUGINS_PATH."/CoreShopDemo/data/demo/$json.json";

        if (file_exists($file)) {
            $filters = \Zend_Json::decode(file_get_contents($file));

            foreach($filters as $values) {
                $filter = Product\Filter::getByField("name", $values['name']);

                if(!$filter instanceof Product\Filter) {
                    $filter = new Product\Filter();
                }

                $conditionNamespace = 'CoreShop\\Model\\Product\\Filter\\Condition\\';
                $similarityNamespace = 'CoreShop\\Model\\Product\\Filter\\Similarity\\';

                $filtersInstances = $filter->prepareConditions($values['conditions'], $conditionNamespace);
                $similaritiesInstances = $filter->prepareSimilarities($values['similarities'], $similarityNamespace);

                $filter->setValues($values);
                $filter->setIndex(Index::getByField("name", "demo")->getId());
                $filter->setPreConditions([]);
                $filter->setFilters($filtersInstances);
                $filter->setSimilarities($similaritiesInstances);
                $filter->save();
            }
        }
    }

    /**
     * @param $json
     */
    public function installDemoShippingRules($json) {
        $file = PIMCORE_PLUGINS_PATH."/CoreShopDemo/data/demo/$json.json";

        if (file_exists($file)) {
            $config = \Zend_Json::decode(file_get_contents($file));

            foreach($config as $values) {
                $shippingRule = ShippingRule::getByField("name", $values['name']);

                if(!$shippingRule instanceof ShippingRule) {
                    $shippingRule = new ShippingRule();
                }

                $conditions = $values['conditions'];
                $actions = $values['actions'];

                $actionNamespace = 'CoreShop\\Model\\Carrier\\ShippingRule\\Action\\';
                $conditionNamespace = 'CoreShop\\Model\\Carrier\\ShippingRule\\Condition\\';

                $actionInstances = $shippingRule->prepareActions($actions, $actionNamespace);
                $conditionInstances = $shippingRule->prepareConditions($conditions, $conditionNamespace);

                $shippingRule->setValues($values);
                $shippingRule->setActions($actionInstances);
                $shippingRule->setConditions($conditionInstances);
                $shippingRule->save();
            }
        }
    }

    /**
     * @param $json
     */
    public function installDemoCarrier($json) {
        $file = PIMCORE_PLUGINS_PATH."/CoreShopDemo/data/demo/$json.json";

        if (file_exists($file)) {
            $config = \Zend_Json::decode(file_get_contents($file));

            foreach($config as $values) {
                $carrier = Carrier::getByField("name", $values['name']);

                if(!$carrier instanceof Carrier) {
                    $carrier = new Carrier();
                }

                $carrier->setTaxRuleGroup(TaxRuleGroup::getByField("name", $values['taxRule']));
                $carrier->setValues($values);
                $carrier->save();

                $i = 1;

                foreach($values['shippingRules'] as $ruleName) {
                    $i += 100;

                    $rule = ShippingRule::getByField("name", $ruleName);

                    if($rule instanceof ShippingRule) {
                        $group = new Carrier\ShippingRuleGroup();
                        $group->setPriority($i);
                        $group->setCarrier($carrier);
                        $group->setShippingRule($rule);
                        $group->save();
                    }
                }
            }
        }
    }
}
