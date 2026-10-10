<?php

class Configuration
{
    private static $values = [];

    public static function reset()
    {
        self::$values = [
            'PS_OS_CANCELED' => 6,
            'PS_OS_ERROR' => 8,
            'PS_LANG_DEFAULT' => 1,
            'PS_HOME_CATEGORY' => 2,
            'PS_SHOP_NAME' => 'Test Shop',
            'PS_ATTRIBUTE_ANCHOR_SEPARATOR' => ' - ',
        ];
    }

    public static function get($key)
    {
        return isset(self::$values[$key]) ? self::$values[$key] : null;
    }

    public static function updateValue($key, $value)
    {
        self::$values[$key] = $value;

        return true;
    }

    public static function deleteByName($key)
    {
        unset(self::$values[$key]);

        return true;
    }
}

Configuration::reset();

class Shop
{
    private static $featureActive = false;

    public static function reset()
    {
        self::$featureActive = false;
    }

    public static function isFeatureActive()
    {
        return self::$featureActive;
    }

    public static function setFeatureActive($active)
    {
        self::$featureActive = (bool) $active;
    }

    public static function setContext($ctx)
    {
    }

    public static function addSqlRestrictionOnLang($alias)
    {
        return '';
    }

    public static function addSqlAssociation($table, $alias)
    {
        return '';
    }
}

class Tools
{
    public static function safeOutput($string)
    {
        return htmlspecialchars((string) $string, ENT_QUOTES, 'UTF-8');
    }

    public static function getValue($key, $default = false)
    {
        return ToolsState::getValue($key, $default);
    }

    public static function isSubmit($key)
    {
        return ToolsState::isSubmit($key);
    }

    public static function getAdminTokenLite($controller)
    {
        return 'token';
    }
}

class ToolsState
{
    private static $values = [];
    private static $submit = [];

    public static function reset()
    {
        self::$values = [];
        self::$submit = [];
    }

    public static function setValue($key, $value)
    {
        self::$values[$key] = $value;
    }

    public static function getValue($key, $default = false)
    {
        return array_key_exists($key, self::$values) ? self::$values[$key] : $default;
    }

    public static function setSubmit($key, $on = true)
    {
        self::$submit[$key] = $on;
    }

    public static function isSubmit($key)
    {
        return !empty(self::$submit[$key]);
    }
}

class Validate
{
    public static function isLoadedObject($obj)
    {
        return is_object($obj) && !empty($obj->id);
    }
}

class RequestGlobals
{
    public static function reset()
    {
        $_GET = [];
        $_POST = [];
        $requestTimeFloat = isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(true);
        $requestTime = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : 'phpunit';
        $_SERVER = [
            'HTTP_HOST' => 'shop.test',
            'REQUEST_URI' => '/',
            'REQUEST_TIME_FLOAT' => $requestTimeFloat,
            'REQUEST_TIME' => $requestTime,
            'SCRIPT_NAME' => $scriptName,
        ];
    }
}

RequestGlobals::reset();

class LanguageRegistry
{
    private static $ids = [1, 2];

    public static function reset()
    {
        self::$ids = [1, 2];
    }

    public static function getIDs($active)
    {
        return self::$ids;
    }
}

class Language
{
    public static function getIDs($active)
    {
        return LanguageRegistry::getIDs($active);
    }
}

class TabRegistry
{
    private static $tabs = [];
    private static $nextId = 1;

    public static function reset()
    {
        self::$tabs = [];
        self::$nextId = 1;
    }

    public static function recordAdd(Tab $tab)
    {
        $tab->id = self::$nextId++;
        self::$tabs[$tab->class_name] = $tab;

        return true;
    }

    public static function getByClass($class)
    {
        return isset(self::$tabs[$class]) ? self::$tabs[$class] : null;
    }

    public static function deleteTab(Tab $tab)
    {
        if (isset(self::$tabs[$tab->class_name])) {
            unset(self::$tabs[$tab->class_name]);
        }

        return true;
    }
}

class Tab
{
    public $id;
    public $class_name;
    public $module;
    public $active;
    public $id_parent;
    public $name = [];

    public function __construct($id = null)
    {
        if ($id) {
            $existing = TabRegistry::getByClass('AdminGanalyticsAjax');
            if ($existing && (int) $existing->id === (int) $id) {
                $this->id = $existing->id;
                $this->class_name = $existing->class_name;
            }
        }
    }

    public static function getIdFromClassName($class)
    {
        $tab = TabRegistry::getByClass($class);

        return $tab ? (int) $tab->id : 0;
    }

    public function add()
    {
        return TabRegistry::recordAdd($this);
    }

    public function delete()
    {
        return TabRegistry::deleteTab($this);
    }
}

class ModuleRegistry
{
    private static $hooks = [];
    private static $installed = [];
    private static $instances = [];

    public static function reset()
    {
        self::$hooks = [];
        self::$installed = [];
        self::$instances = [];
    }

    public static function registerHook($moduleName, $hook)
    {
        if (!isset(self::$hooks[$moduleName])) {
            self::$hooks[$moduleName] = [];
        }
        self::$hooks[$moduleName][$hook] = true;

        return true;
    }

    public static function unregisterHook($moduleName, $hook)
    {
        if (isset(self::$hooks[$moduleName][$hook])) {
            unset(self::$hooks[$moduleName][$hook]);
        }

        return true;
    }

    public static function getHooks($moduleName)
    {
        return isset(self::$hooks[$moduleName]) ? array_keys(self::$hooks[$moduleName]) : [];
    }

    public static function setInstalled($name, $active = true)
    {
        self::$installed[$name] = $active;
    }

    public static function isInstalled($name)
    {
        return isset(self::$installed[$name]);
    }

    public static function setInstance($name, Module $module)
    {
        self::$instances[$name] = $module;
    }

    public static function getInstance($name)
    {
        return isset(self::$instances[$name]) ? self::$instances[$name] : false;
    }
}

class Module
{
    public $name;
    public $active = true;

    public function __construct()
    {
    }
    public $displayName = 'Google Analytics';
    public $tab = 'analytics_stats';

    public function registerHook($hook)
    {
        return ModuleRegistry::registerHook($this->name, $hook);
    }

    public function unregisterHook($hook)
    {
        return ModuleRegistry::unregisterHook($this->name, $hook);
    }

    public function isRegisteredInHook($hook)
    {
        $hooks = ModuleRegistry::getHooks($this->name);

        return in_array($hook, $hooks, true);
    }

    public static function isInstalled($name)
    {
        return ModuleRegistry::isInstalled($name);
    }

    public static function getInstanceByName($name)
    {
        return ModuleRegistry::getInstance($name);
    }

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    public function display($file, $template)
    {
        SmartyStub::$lastDisplayTemplate = $template;

        return 'DISPLAY:' . $template;
    }

    public function getLocalPath()
    {
        return MODULE_ROOT . '/';
    }

    public function getPathUri()
    {
        return '/modules/ps_googleanalytics/';
    }

    public function getTranslator()
    {
        return new TranslatorStub();
    }

    public function displayConfirmation($msg)
    {
        return $msg;
    }

    public function trans($id, $params, $domain)
    {
        return $id;
    }
}

class TranslatorStub
{
    public function trans($id, $params, $domain)
    {
        return $id;
    }
}

class SmartyStub
{
    public static $vars = [];
    public static $lastDisplayTemplate = '';

    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                self::$vars[$k] = $v;
            }
        } else {
            self::$vars[$key] = $value;
        }
    }

    public function getTemplateVars($name = null)
    {
        if ($name === null) {
            return self::$vars;
        }

        return isset(self::$vars[$name]) ? self::$vars[$name] : null;
    }

    public static function reset()
    {
        self::$vars = [];
        self::$lastDisplayTemplate = '';
    }
}

class CookieStub
{
    public $ga_admin_order;
    public $ga_admin_refund;
    public $ga_last_listing;

    public function write()
    {
    }
}

class LinkStub
{
    public function getModuleLink($module, $controller, $params = [], $ssl = true)
    {
        return 'https://shop.test/module/' . $module . '/' . $controller;
    }

    public function getAdminLink($controller, $ssl = true, $params = [], $extra = [])
    {
        return 'https://shop.test/admin/' . $controller;
    }
}

class ControllerStub
{
    public $php_self = 'index';
    public $controller_name = 'index';
    public $addCssCalls = [];

    public function addCSS($path)
    {
        $this->addCssCalls[] = $path;
    }
}

class Context
{
    /** @var Context */
    private static $instance;

    public $shop;
    public $language;
    public $currency;
    public $cart;
    public $customer;
    public $cookie;
    public $smarty;
    public $link;
    public $controller;

    public function __construct()
    {
        $this->shop = new \stdClass();
        $this->shop->id = 1;
        $this->shop->name = 'Multishop Name';
        $this->language = new \stdClass();
        $this->language->id = 1;
        $this->currency = new CurrencyStub();
        $this->cart = new CartStub();
        $this->customer = new CustomerStub();
        $this->cookie = new CookieStub();
        $this->smarty = new SmartyStub();
        $this->link = new LinkStub();
        $this->controller = new ControllerStub();
    }

    public static function getContext()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function reset()
    {
        self::$instance = null;
        SmartyStub::reset();
    }
}

class ContextHolder
{
    public static function reset()
    {
        Context::reset();
    }
}

class CurrencyStub
{
    public $iso_code = 'EUR';

    public function __construct($id = null)
    {
        if ((int) $id === 4) {
            $this->iso_code = 'EUR';
        } elseif ($id !== null) {
            $this->iso_code = 'XXX';
        }
    }
}

class Currency extends CurrencyStub
{
}

class CartStub
{
    public $id = 42;
    public $id_carrier = 3;
    private static $productsByCartId = [];

    public static function reset()
    {
        self::$productsByCartId = [];
    }

    public static function setProductsForCart($cartId, array $products)
    {
        self::$productsByCartId[(int) $cartId] = $products;
    }

    public function getSummaryDetails()
    {
        return ['total_price' => 15.5];
    }

    public function getProducts()
    {
        return isset(self::$productsByCartId[(int) $this->id])
            ? self::$productsByCartId[(int) $this->id]
            : [];
    }
}

class Cart extends CartStub
{
    public function __construct($id = null)
    {
        if ($id) {
            $this->id = (int) $id;
        }
    }
}

class CustomerStub
{
    public $id = 0;
    public $is_guest = 0;

    public function isLogged()
    {
        return $this->id > 0;
    }

    public function mylogout()
    {
        $this->id = 0;
    }
}

class Customer extends CustomerStub
{
}

class ProductProperties
{
    private static $map = [];

    public static function reset()
    {
        self::$map = [];
    }

    public static function set($id, array $props)
    {
        self::$map[(int) $id] = $props;
    }

    public static function resolve($id)
    {
        $id = (int) $id;

        return isset(self::$map[$id]) ? self::$map[$id] : [];
    }
}

class Product
{
    public $id;
    public $name;

    public function __construct($id = null)
    {
        $this->id = (int) $id;
        $this->name = 'Product ' . $id;
    }

    public static function getProductProperties($idLang, $product)
    {
        $id = isset($product['id_product']) ? (int) $product['id_product'] : (int) $product['id'];
        $extra = ProductProperties::resolve($id);
        if ($extra !== []) {
            $merged = array_merge($product, $extra);
        } else {
            $merged = $product;
        }
        if (!array_key_exists('price', $merged) && array_key_exists('price_amount', $merged)) {
            $merged['price'] = $merged['price_amount'];
        }

        return $merged;
    }
}

class ManufacturerRegistry
{
    private static $names = [];

    public static function reset()
    {
        self::$names = [];
    }

    public static function set($id, $name)
    {
        self::$names[(int) $id] = $name;
    }
}

class Manufacturer
{
    public static function getNameById($id)
    {
        return isset(ManufacturerRegistry::$names[(int) $id]) ? ManufacturerRegistry::$names[(int) $id] : '';
    }
}

class OrderStub
{
    public $id;
    public $id_shop = 1;
    public $id_cart = 1;
    public $id_currency = 4;
    public $id_customer = 5;
    public $total_paid = '120.00';
    public $total_shipping = '8.50';
    public $total_paid_tax_incl = '120.00';
    public $total_paid_tax_excl = '100.00';
    public $payment = 'Bank wire';
    private $state = 2;

    public function getCurrentState()
    {
        return $this->state;
    }

    public function setCurrentState($state)
    {
        $this->state = (int) $state;
    }
}

class Order extends OrderStub
{
    public function __construct($id = null)
    {
        if ($id) {
            $loaded = OrderFixtures::get((int) $id);
            if (!$loaded) {
                $this->id = 0;

                return;
            }
            $this->id = (int) $id;
            foreach ($loaded as $k => $v) {
                $this->$k = $v;
            }
        }
    }
}

class OrderFixtures
{
    private static $orders = [];

    public static function reset()
    {
        self::$orders = [];
    }

    public static function set($id, array $data)
    {
        self::$orders[(int) $id] = $data;
    }

    public static function get($id)
    {
        return isset(self::$orders[(int) $id]) ? self::$orders[(int) $id] : null;
    }
}

class OrderDetail extends OrderStub
{
    public $product_id;
    public $product_attribute_id;
    public $product_name;

    public function __construct($id = null)
    {
        if ($id) {
            $this->id = (int) $id;
            $fix = OrderDetailFixtures::get($this->id);
            if ($fix) {
                foreach ($fix as $k => $v) {
                    $this->$k = $v;
                }
            }
        }
    }
}

class OrderDetailFixtures
{
    private static $details = [];

    public static function set($id, array $data)
    {
        self::$details[(int) $id] = $data;
    }

    public static function get($id)
    {
        return isset(self::$details[(int) $id]) ? self::$details[(int) $id] : null;
    }

    public static function reset()
    {
        self::$details = [];
    }
}

class HelperForm
{
    public $fields_value = [];
    public $module;
    public $recordedFormInputNames = [];

    public function generateForm($fields)
    {
        if (isset($fields[0]['form']['input'])) {
            foreach ($fields[0]['form']['input'] as $input) {
                $this->recordedFormInputNames[] = $input['name'];
            }
        }

        return 'FORM_HTML';
    }
}

class ModuleAdminController
{
    public $context;
    public $ssl = true;
    public $ajaxRendered = '';

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    public function initContent()
    {
    }

    public function ajaxRender($content)
    {
        $this->ajaxRendered = $content;
        throw new AjaxExitException();
    }
}

class ModuleFrontController extends ModuleAdminController
{
}

class AjaxExitException extends \Exception
{
}

class OrderState
{
    public static function getOrderStates($idLang)
    {
        return [
            ['id_order_state' => 6, 'name' => 'Canceled'],
        ];
    }
}