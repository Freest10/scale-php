<?php

define("CURRENT_WORKING_DIR", str_replace("\\", "/", $dirname = dirname(__FILE__)));

include_once CURRENT_WORKING_DIR . '/libs/config.php';
require_once CURRENT_WORKING_DIR . '/libs/root-src/data-base.php';
require_once CURRENT_WORKING_DIR . '/libs/langs/index.php';
include_once CURRENT_WORKING_DIR . '/libs/root-src/exception/index.php';
include_once CURRENT_WORKING_DIR . '/libs/systems_classes/Response.php';
include_once CURRENT_WORKING_DIR . '/libs/systems_classes/JsonOperations.php';
include_once CURRENT_WORKING_DIR . '/controllers/Users.php';
include_once CURRENT_WORKING_DIR . '/libs/root-src/ClassesOperations.php';
include_once CURRENT_WORKING_DIR . '/libs/root-src/Requests.php';
include_once CURRENT_WORKING_DIR . '/libs/root-src/SitePaths.php';

include_once CURRENT_WORKING_DIR . '/libs/systems_classes/files.php';

$languages = [
    "ru" => "Русский",
    "en" => "English"
];

switch ($_GET["lang"]) {
    case "ru":
        $activeLang = "ru";
        break;
    case "en":
        $activeLang = "en";
        break;
    default:
        $activeLang = "ru";
        break;
}

$installCmsInstance = new InstallCms();
$installCmsInstance->setLang($activeLang);
$installCmsInstance->setLanguages($languages);
$installCmsInstance->setSitePaths(new SitePaths());

try {
    if ($_GET["do_install"] == 1) {
        $installCmsInstance->setConfig($config);
        $dataBase = new \DataBase;
        $installCmsInstance->setDatabase($dataBase);
        $installCmsInstance->doInstallCms($_GET);
    } else {
        $installCmsInstance->showInstallForm();
    }
} catch (\SystemException $e) {
    $e->showMessage();
}


class InstallCms
{

    private $languages;
    private $config;
    private $dataBase;
    private $tabels;
    private $langs;
    private $files;
    private $sitePaths;

    function __construct()
    {
        $this->langs = \Langs::getInstance();
        $this->tabels = [
            "users" => [
                "user_id int(11) PRIMARY KEY AUTO_INCREMENT",
                "login varchar(255)",
                "password text",
                "is_admin tinyint(1)",
                "new_event tinyint(1)",
                "date datetime",
                "restore_path text",
                "restore_date datetime",
                "UNIQUE (login)"
            ],
            "template_id_name" => [
                "id int(11) UNIQUE AUTO_INCREMENT",
                "name text"
            ],
            "page_id_name" => [
                "id INT(11) PRIMARY KEY AUTO_INCREMENT",
                "name text",
                "last_mod varchar(255)"
            ],
            "group_id_name" => [
                "id INT(11) PRIMARY KEY AUTO_INCREMENT",
                "name text",
                "text_id varchar(255)",
                "UNIQUE (text_id)"
            ],
            "field_id_name" => [
                "id INT(11) PRIMARY KEY AUTO_INCREMENT",
                "name text",
                "text_id VARCHAR(255)",
                "UNIQUE (text_id)"
            ],
            "type_fields" => [
                "id int(11) PRIMARY KEY AUTO_INCREMENT",
                "name text",
                "table_name varchar(255)",
                "filter tinyint(1)"
            ],
            "site_sub_domains" => [
                "id int(11) PRIMARY KEY AUTO_INCREMENT",
                "text_id varchar(255)",
                "text varchar(255)",
                "default_value tinyint(1)",
                "UNIQUE (text_id)"
            ],
            "plugins" => [
                "text_id varchar(255) PRIMARY KEY",
                "text text",
                "version varchar(50)"
            ],
            "sections" => [
                "id INT(11) PRIMARY KEY AUTO_INCREMENT",
                "name varchar(255)",
                "available tinyint(1)",
                "link varchar(255)",
                "class_ico varchar(255)"
            ],
            "basket" => [
                "user_id INT(11) NOT NULL",
                "products text",
                "FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE"
            ],
            "currency" => [
                "id INT(11) PRIMARY KEY AUTO_INCREMENT",
                "name VARCHAR(150)",
                "UNIQUE (id)"
            ],
            "dop_properties_fields" => [
                "id INT(11)",
                "hint text",
                "necessarily tinyint(1)",
                "indexed tinyint(1)",
                "filtered tinyint(1)",
                "PRIMARY KEY (id)",
                "FOREIGN KEY (id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "dop_properties_page" => [
                "id INT(11) AUTO_INCREMENT",
                "h1 text",
                "title text",
                "description text",
                "PRIMARY KEY (id)",
                "FOREIGN KEY (id) REFERENCES page_id_name(id) ON DELETE CASCADE"
            ],
            "field_id_field_type" => [
                "id INT(11) AUTO_INCREMENT",
                "type_id INT(11) NOT NULL",
                "UNIQUE (id)",
                "FOREIGN KEY (id) REFERENCES field_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (type_id) REFERENCES type_fields(id)"
            ],
            "field_id_reference_id" => [
                "field_id INT(11)",
                "reference_id INT(11)",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "field_values_type_date" => [
                "page_id INT(11)",
                "field_id INT(11)",
                "value date",
                "type INT(11)",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id)",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "field_values_type_double" => [
                "page_id INT(11)",
                "field_id INT(11)",
                "value double",
                "type INT(11)",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "field_values_type_file" => [
                "page_id INT(11)",
                "field_id INT(11)",
                "value text",
                "type INT(11)",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "field_values_type_html_text" => [
                "page_id INT(11)",
                "field_id INT(11)",
                "value text",
                "type INT(11)",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "field_values_type_link_page" => [
                "page_id INT(11)",
                "field_id INT(11)",
                "value 	int(11)",
                "type INT(11)",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "field_values_type_number" => [
                "page_id INT(11)",
                "field_id INT(11)",
                "value 	int(100)",
                "type INT(11)",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "field_values_type_password" => [
                "page_id INT(11)",
                "field_id INT(11)",
                "value 	text",
                "type INT(11)",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "field_values_type_price" => [
                "page_id INT(11)",
                "field_id INT(11)",
                "value 	double",
                "type INT(11)",
                "UNIQUE (page_id)",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "field_values_type_select" => [
                "page_id INT(11)",
                "field_id INT(11)",
                "value 	int(11)",
                "type INT(11)",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "field_values_type_sostav" => [
                "page_id INT(11)",
                "field_id INT(11)",
                "reference_data_id INT(11)",
                "value 	double",
                "type INT(11)",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "field_values_type_string" => [
                "page_id INT(11)",
                "field_id INT(11)",
                "value 	varchar(250)",
                "type INT(11)",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "field_values_type_text" => [
                "page_id INT(11)",
                "field_id INT(11)",
                "value 	text",
                "type INT(11)",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "field_values_type_time" => [
                "page_id INT(11)",
                "field_id INT(11)",
                "value 	time",
                "type INT(11)",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "field_value_multi_select" => [
                "page_id INT(11)",
                "field_id INT(11)",
                "value 	int(11)",
                "type INT(11)",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "filter_fields" => [
                "page_id INT(11)",
                "template_id INT(11)",
                "field_id INT(11)",
                "field_name varchar(255)",
                "field_value int(11)",
                "field_value_string varchar(255) DEFAULT NULL",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "group_id_field_id" => [
                "group_id INT(11)",
                "field_id INT(11)",
                "sort_num INT(11)",
                "FOREIGN KEY (group_id) REFERENCES group_id_name(id) ON DELETE CASCADE",
                "FOREIGN KEY (field_id) REFERENCES field_id_name(id) ON DELETE CASCADE"
            ],
            "langs" => [
                "id INT(11) AUTO_INCREMENT",
                "text_id varchar(255)",
                "active tinyint(1)",
                "name varchar(255)",
                "PRIMARY KEY (id)",
                "UNIQUE (text_id)"
            ],
            "main_page" => [
                "id INT(11) UNIQUE",
                "sub_domain INT(11)",
                "UNIQUE (sub_domain)",
                "FOREIGN KEY (id) REFERENCES page_id_name(id)",
            ],
            "main_rights" => [
                "user_id INT(11)",
                "section_text_id varchar(255)",
                "read_right tinyint(1)",
                "create_right tinyint(1)",
                "edit_right tinyint(1)",
                "delete_right tinyint(1)",
                "FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE"
            ],
            "messages" => [
                "id INT(11) AUTO_INCREMENT",
                "address_id INT(11)",
                "template_id INT(11)",
                "date date",
                "ip varchar(255)",
                "message text",
                "new_event tinyint(1)",
                "PRIMARY KEY (id)"
            ],
            "orders" => [
                "order_id INT(11) NOT NULL AUTO_INCREMENT",
                "user_id INT(11) NOT NULL",
                "date datetime",
                "new_event tinyint(1)",
                "PRIMARY KEY (order_id)",
                "FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE"
            ],
            "order_info" => [
                "id INT(11)",
                "product_id INT(11)",
                "price INT(11)",
                "amount INT(11)",
                "product_name text",
                "currency INT(11)",
                "FOREIGN KEY (product_id) REFERENCES page_id_name(id)",
                "FOREIGN KEY (currency) REFERENCES currency(id)"
            ],
            "page_id_active" => [
                "id INT(11)",
                "active tinyint(1)",
                "no_indexed tinyint(1)",
                "PRIMARY KEY (id)",
                "FOREIGN KEY (id) REFERENCES page_id_name(id)"
            ],
            "page_id_to_template_id" => [
                "page_id INT(11) AUTO_INCREMENT",
                "template_id INT(11)",
                "PRIMARY KEY (page_id)",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id)",
                "FOREIGN KEY (template_id) REFERENCES template_id_name(id)"
            ],
            "page_id_uri" => [
                "page_id INT(11) PRIMARY KEY AUTO_INCREMENT",
                "uri varchar(255)",
                "full_path text",
                "FOREIGN KEY (page_id) REFERENCES page_id_name(id)",
            ],
            "page_parent_id" => [
                "id INT(11) UNIQUE NOT NULL",
                "parent_id INT(11)",
                "sort INT(11)",
                "countSortNum 	bigint(20)",
                "sub_domain INT(11)",
                "FOREIGN KEY (id) REFERENCES page_id_name(id)",
            ],
            "plugins_rights" => [
                "user_id INT(11) NOT NULL",
                "text_id varchar(255)",
                "read_right tinyint(1)",
                "create_right tinyint(1)",
                "edit_right tinyint(1)",
                "delete_right tinyint(1)",
                "FOREIGN KEY (user_id) REFERENCES users(user_id)",
                "FOREIGN KEY (text_id) REFERENCES plugins(text_id)"
            ],
            "reference_data" => [
                "reference_id INT(11)",
                "reference_data_id INT(11) AUTO_INCREMENT",
                "name varchar(255)",
                "PRIMARY KEY (reference_data_id)"
            ],
            "sessions" => [
                "session_id varchar(255)",
                "user_id int(11) NOT NULL",
                "date_time_session_create datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
                "PRIMARY KEY (session_id)",
                "FOREIGN KEY (user_id) REFERENCES users(user_id)"
            ],
            "settings" => [
                "id int(11) UNIQUE AUTO_INCREMENT",
                "name varchar(255)",
                "value varchar(255)",
                "UNIQUE (name)",
                "FOREIGN KEY (id) REFERENCES users(user_id)"
            ],
            "site_url" => [
                "id int(11) AUTO_INCREMENT",
                "url text",
                "PRIMARY KEY (id)"
            ],
            "template_parent_id" => [
                "id int(11) UNIQUE AUTO_INCREMENT",
                "parent_id int(11)",
                "FOREIGN KEY (id) REFERENCES template_id_name(id)",
                "FOREIGN KEY (parent_id) REFERENCES template_id_name(id)"
            ],
            "template_id_group_id" => [
                "template_id INT(11)",
                "group_id INT(11)",
                "FOREIGN KEY (template_id) REFERENCES template_id_name(id)",
                "FOREIGN KEY (group_id) REFERENCES group_id_name(id)"
            ]
        ];

        $this->files = new FilesOperations();
    }

    public function setLang($lang)
    {
        $this->langs->setActiveLangToConfigByTextId($lang);
    }

    public function setSitePaths($sitePath)
    {
        $this->sitePaths = $sitePath;
    }

    public function showInstallForm()
    {

        header('Content-Type: text/html; charset=UTF-8');
        $this->renderLanguageOptions();

        echo '
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<canvas></canvas>
			<link href="/css/administration/auth.css" rel="stylesheet">
			<link href="/css/administration/bootstrap.css" rel="stylesheet">
			<link href="/css/administration/style.css" rel="stylesheet">
			<script src="/js/administration/canvas_animation/zepto.min.js"></script>
			<script src="/js/administration/canvas_animation/index.js"></script>
			<script src="/js/administration/install.js"></script>

			<div id="wrap_loader_block">
			    <div id="loader_block_flex">
                    <div id="loader_block">
                        <div class="cssload-squares">
                            <span></span><span></span><span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
            </div>

			<div id="auth_wrap">
			    <div id="auth_wrap_flex">
                    <div id="install">
                        <div class="head"><h2>' . $this->langs->getMessage("backend.install.install") . '</h2></div>
                            <div class="cont">
                                <form method="post">
                                    <input type="hidden" name="from_page" value="/admin/events/last/">
                                    <div class="flex_block install_block">
                                        <h4>' . $this->langs->getMessage("backend.install.bd_props") . '</h4>
                                        <label><h5>' . $this->langs->getMessage("backend.install.db_host") . '</h5><input type="text" class="form-control" name="db_host" value="localhost"></label>
                                        <label><h5>' . $this->langs->getMessage("backend.install.db_login") . '</h5><input type="text" class="form-control" name="db_login"></label>
                                        <label><h5>' . $this->langs->getMessage("backend.install.db_password") . '</h5><input type="text" class="form-control" name="db_password"></label>
                                        <label><h5>' . $this->langs->getMessage("backend.install.db_name") . '</h5><input type="text" class="form-control" name="db_name"></label>
                                    </div>
                                    <div class="flex_block install_block">
                                        <h4>' . $this->langs->getMessage("backend.install.user") . '</h4>
                                        <label><h5>' . $this->langs->getMessage("backend.auth.login") . '</h5><input type="text" class="form-control" id="login_field" name="login"></label>
                                        <label><h5>' . $this->langs->getMessage("backend.install.email") . '</h5><input type="text" class="form-control" id="email_field" name="email"></label>
                                        <label><h5>' . $this->langs->getMessage("backend.auth.password") . '</h5><input type="password" class="form-control" id="password_field" name="password"></label>
                                        <label><h5>' . $this->langs->getMessage("backend.users.confirm_password") . '</h5><input type="password" class="form-control" id="confirm_password_field" name="confirm_password"></label>
                                    </div>
                                    <div class="flex_block install_block">
                                        <h4>' . $this->langs->getMessage("backend.install.system_version") . '</h4>
                                        <input type="radio" id="company_site"
                                             name="type_cms" value="company" checked>
                                        <label for="company_site"  class="rightLabelMargin">' . $this->langs->getMessage("backend.install.company_site") . '</label>
                                        <input type="radio" id="emarket_site" name="type_cms" value="emarket">
                                        <label for="emarket_site" class="rightLabelMargin">' . $this->langs->getMessage("backend.install.emarket_site") . '</label>
                                    </div>
                                    <div class="flex_block install_block">
                                        <h4>' . $this->langs->getMessage("backend.install.languages") . '</h4>
                                        <label><h5>' . $this->langs->getMessage("backend.install.select_language") . '</h5>
                                            <select  class="form-control" name="language">' . $this->renderLanguageOptions() . '</select>
                                        </label>
                                    </div>
                                    <div>
                                        <div>
                                            <input type="button" id="do_install" class="btn btn-primary btn-block m-t" value="' . $this->langs->getMessage("backend.install.do_install") . '">
                                        </div>
                                    </div>
                                    <div class="errorMessageAuth">
                                        <div class="passwordError">' . $this->langs->getMessage("backend.install.password_error") . '</div>
                                        <div class="emptyError">' . $this->langs->getMessage("backend.install.empty_error") . '</div>
                                    </div>
                                </form>
                            </div>
                    </div>
                </div>
			</div>
			';
    }

    private function renderLanguageOptions()
    {
        $resultOptions = "";
        $optionNum = 0;
        foreach ($this->languages as $key => $value) {
            $resultOptions .= "<option value='$key' ";
            if ($optionNum == 0) {
                $resultOptions .= "selected";
            }
            $resultOptions .= ">$value</option>";
            $optionNum++;
        }
        return $resultOptions;
    }

    public function setLanguages($languages)
    {
        $this->languages = $languages;
    }

    public function doInstallCms($params)
    {
        $this->setConfigData($params);
        if ($this->doInstallDbValues($params)) {
            $this->setCmsHtaccess();
            $this->updateDateLastInstall();
        }
    }

    private function updateDateLastInstall()
    {
        $aboutProgramm = [];
        $aboutProgramm["program_name"] = "ScaleCMS";
        $aboutProgramm["version"] = "1.0";
        $aboutProgramm["date_create"] = date("d.m.Y");
        $aboutProgramm["date_update"] = date("d.m.Y");
        $newAboutJsonFile = \JsonOperations::getJsonFromPhp($aboutProgramm, JSON_UNESCAPED_UNICODE);
        $this->files->writeToFile("libs/root-src/about_program.json", $newAboutJsonFile);
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    private function setConfigData($params)
    {
        $this->config->set("connections", "core.host", $params["db_host"]);
        $this->config->set("connections", "core.login", $params["db_login"]);
        $this->config->set("connections", "core.password", $params["db_password"]);
        $this->config->set("connections", "core.dbname", $params["db_name"]);
    }

    public function setDatabase($dataBase)
    {
        $this->dataBase = $dataBase;
    }

    private function doInstallDbValues($params)
    {
        $resultConnectDb = $this->dataBase->connectToDataBase(true);

        if ($resultConnectDb == "db_connect_error") {
            $responceText = $this->langs->getMessage("backend.errors.db_connect_error");
            \Response::errorResponse($responceText, 503);
            return false;
        } else if ($resultConnectDb == "db_select_error") {
            $responceText = $this->langs->getMessage("backend.errors.db_select_error");
            \Response::errorResponse($responceText, 501);
            return false;
        } else {
            $this->langs->setActiveLangByTextId($params["language"]);
            $this->setDbValues($params);
            $this->setDomainByActiveLang($params["language"]);
            $printData = [];
            $printData["status"] = "installed";
            $printData["messageTitle"] = $this->langs->getMessage("backend.install.good_installed");
            $printData["message"] = $this->langs->getMessage("backend.install.go_to_admin");
            $this->dataBase->closeConnectToDataBase();
            sleep(2);
            \JsonOperations::printJsonFromPhp($printData);
            return true;
        }
    }

    private function setDomainByActiveLang($lang)
    {
        $this->sitePaths->setDefaultSubDomainByTextId($lang);
    }

    private function setDbValues($params)
    {
        $this->createDbTabels();
        $this->setLangValues($params);
        $this->setConfigLangValues($params);
        $this->setAdminValues($params);
        $this->setTableValues($this->getTableValues($params));
        $this->setCmsDataByVersion($params);
    }

    private function setConfigLangValues($params)
    {
        $this->config->set("system", "lang", $params["language"]);
    }

    private function setCmsDataByVersion($params)
    {
        if ($params["type_cms"] == "company") {
            $this->setCompanyValues($params);
        } else if ($params["type_cms"] == "emarket") {
            $this->setEmarketValues($params);
        }
    }

    private function setCompanyValues($params)
    {
        $this->setTableValues($this->getCompanyTableValues($params));
    }

    private function setEmarketValues($params)
    {
        $this->setTableValues($this->getEmarketTabelValues($params));
    }

    private function setAdminValues($params)
    {
        $userInstance = new \Controller\Users();
        $userInstance->createUserWithParams($params["login"], $params["password"], $params["confirm_password"], $params["email"], 1);
    }

    private function setTableValues($values)
    {
        foreach ($values as $key => $value) {
            $this->setValuesOfTable($key, $value);
        }
    }

    private function setValuesOfTable($tableName, $values)
    {
        foreach ($values as $key => $value) {
            $sqlString = "INSERT ";
            $sqlString .= $tableName;
            $sqlString .= " SET ";
            $sqlString .= $this->getDbSetStringFromValues($value);
            $this->dataBase->justQueryToDataBase($sqlString);
        }
    }

    private function getDbSetStringFromValues($values)
    {
        $elemNum = 0;
        $setString = "";
        foreach ($values as $key => $value) {
            if ($elemNum != 0) {
                $setString .= ",";
            }
            $setString .= $key;
            if (gettype($value) == "string") {
                $setString .= "='$value'";
            } else if ($value === NULL) {
                $setString .= "=NULL";
            } else {
                $setString .= "=$value";
            }
            $elemNum++;
        }
        return $setString;
    }

    private function createDbTabels()
    {
        foreach ($this->tabels as $key => $value) {
            $sqlString = "CREATE TABLE ";
            $sqlString .= "$key";
            $sqlString .= "(";
            $sqlString .= join(',', $value);
            $sqlString .= ");";
            $this->dataBase->justQueryToDataBase($sqlString);
        }
    }

    private function setCmsHtaccess()
    {
        $data = "
            RewriteRule ^config\.ini$ / [L,R]
            RewriteRule ^install\.php$ / [L,R]
            RewriteRule ^pug\.php$ / [L,R]
            RewriteRule ^composer\.json$ / [L,R]
            RewriteRule ^composer\.lock$ / [L,R]
            RewriteRule ^backups / [L,R]
            <IfModule mod_rewrite.c>
                RewriteEngine On
                RewriteBase /
                RewriteCond %{REQUEST_FILENAME} -f
                RewriteRule ^ - [L]
                RewriteRule . /index.php [L]
            </IfModule>
            RewriteCond %{REQUEST_URI} !\.json
            RewriteCond %{REQUEST_URI} !^images
            RewriteCond %{REQUEST_URI} !^files
            RewriteCond %{REQUEST_URI} !\.png
            RewriteCond %{REQUEST_URI} !\.jpg
            RewriteCond %{REQUEST_URI} !^styles
            RewriteCond %{REQUEST_URI} !^css
            RewriteCond %{REQUEST_URI} !\.css
            RewriteCond %{REQUEST_URI} !^js
            RewriteCond %{REQUEST_URI} !\.js
            RewriteCond %{REQUEST_URI} !^ico
            RewriteCond %{REQUEST_URI} !\.ico
            php_flag display_errors on
            php_flag display_startup_errors on";
        $this->files->writeToFile(".htaccess", $data);
    }

    private function setLangValues($params)
    {
        foreach ($this->languages as $key => $value) {
            $sqlString = "INSERT ";
            $sqlString .= "langs ";
            $sqlString .= "SET text_id='$key', name='$value', ";
            if ($key == $params["language"]) {
                $sqlString .= "active=1";
            } else {
                $sqlString .= "active=0";
            }
            $this->dataBase->justQueryToDataBase($sqlString);
        }
    }

    private function getTableValues($params)
    {
        $tableValues = [
            "dop_properties_page" => [
                0 => [
                    "id" => 1,
                    "h1" => "",
                    "title" => "",
                    "description" => ""
                ]
            ],
            "currency" => [
                0 => [
                    "id" => 1,
                    "name" => "rub"
                ],
                1 => [
                    "id" => 2,
                    "name" => "euro"
                ],
                2 => [
                    "id" => 3,
                    "name" => "usd"
                ]
            ],
            "type_fields" => [
                0 => [
                    "id" => 1,
                    "name" => $this->langs->getMessage("backend.field_types.string"),
                    "table_name" => "field_values_type_string",
                    "filter" => 1
                ],
                1 => [
                    "id" => 2,
                    "name" => $this->langs->getMessage("backend.field_types.number"),
                    "table_name" => "field_values_type_number",
                    "filter" => 1
                ],
                2 => [
                    "id" => 3,
                    "name" => $this->langs->getMessage("backend.field_types.check_box"),
                    "table_name" => "field_values_type_number",
                    "filter" => 1
                ],
                3 => [
                    "id" => 4,
                    "name" => $this->langs->getMessage("backend.field_types.select"),
                    "table_name" => "field_values_type_select",
                    "filter" => 1
                ],
                4 => [
                    "id" => 5,
                    "name" => $this->langs->getMessage("backend.field_types.multi_select"),
                    "table_name" => "field_value_multi_select",
                    "filter" => 1
                ],
                5 => [
                    "id" => 6,
                    "name" => $this->langs->getMessage("backend.field_types.simple_text"),
                    "table_name" => "field_values_type_text",
                    "filter" => 0
                ],
                6 => [
                    "id" => 7,
                    "name" => $this->langs->getMessage("backend.field_types.html_text"),
                    "table_name" => "field_values_type_html_text",
                    "filter" => 0
                ],
                7 => [
                    "id" => 8,
                    "name" => $this->langs->getMessage("backend.field_types.image_file"),
                    "table_name" => "field_values_type_file",
                    "filter" => 0
                ],
                8 => [
                    "id" => 9,
                    "name" => $this->langs->getMessage("backend.field_types.date"),
                    "table_name" => "field_values_type_date",
                    "filter" => 1
                ],
                9 => [
                    "id" => 10,
                    "name" => $this->langs->getMessage("backend.field_types.time"),
                    "table_name" => "field_values_type_time",
                    "filter" => 1
                ],
                10 => [
                    "id" => 11,
                    "name" => $this->langs->getMessage("backend.field_types.double"),
                    "table_name" => "field_values_type_double",
                    "filter" => 1
                ],
                11 => [
                    "id" => 12,
                    "name" => $this->langs->getMessage("backend.field_types.compound"),
                    "table_name" => "field_values_type_sostav",
                    "filter" => 0
                ],
                12 => [
                    "id" => 13,
                    "name" => $this->langs->getMessage("backend.field_types.price"),
                    "table_name" => "field_values_type_price",
                    "filter" => 1
                ],
                13 => [
                    "id" => 14,
                    "name" => $this->langs->getMessage("backend.field_types.link_page"),
                    "table_name" => "field_values_type_link_page",
                    "filter" => 0
                ],
                14 => [
                    "id" => 15,
                    "name" => $this->langs->getMessage("backend.field_types.password"),
                    "table_name" => "field_values_type_password",
                    "filter" => 0
                ]
            ],
            "page_id_name" => [
                0 => [
                    "id" => 1,
                    "name" => $this->langs->getMessage("backend.install.main_page"),
                    "last_mod" => date("c")
                ]
            ],
            "site_sub_domains" => [
                0 => [
                    "id" => 1,
                    "text_id" => "en",
                    "text" => $this->langs->getMessage("frontend.settings.domains.english"),
                    "default_value" => 0
                ],
                1 => [
                    "id" => 2,
                    "text_id" => "ru",
                    "text" => $this->langs->getMessage("frontend.settings.domains.russian"),
                    "default_value" => 0
                ]
            ],
            "template_id_name" => [
                0 => [
                    "id" => 1,
                    "name" => $this->langs->getMessage("backend.sections.section")
                ],
                1 => [
                    "id" => 2,
                    "name" => $this->langs->getMessage("backend.sections.directories")
                ],
                2 => [
                    "id" => 3,
                    "name" => $this->langs->getMessage("backend.sections.users")
                ],
                3 => [
                    "id" => 6,
                    "name" => $this->langs->getMessage("backend.sections.forms")
                ],
                4 => [
                    "id" => 7,
                    "name" => $this->langs->getMessage("backend.sections.messsage_template")
                ],
                5 => [
                    "id" => 54,
                    "name" => $this->langs->getMessage("backend.filed_names.addresses_mail_template")
                ]
            ],
            "template_parent_id" => [
                0 => [
                    "id" => 1,
                    "parent_id" => NULL
                ],
                1 => [
                    "id" => 2,
                    "parent_id" => NULL
                ],
                2 => [
                    "id" => 54,
                    "parent_id" => 2
                ],
                3 => [
                    "id" => 3,
                    "parent_id" => NULL
                ],
                5 => [
                    "id" => 6,
                    "parent_id" => NULL
                ],
                6 => [
                    "id" => 7,
                    "parent_id" => NULL
                ]
            ],
            "page_id_to_template_id" => [
                0 => [
                    "page_id" => 1,
                    "template_id" => 1
                ]
            ],
            "group_id_name" => [
                0 => [
                    "id" => 1,
                    "name" => $this->langs->getMessage("backend.filed_names.fields_of_user"),
                    "text_id" => "field_of_user"
                ],
                1 => [
                    "id" => 85,
                    "name" => $this->langs->getMessage("backend.filed_names.addresses_mail_template_group"),
                    "text_id" => "addresses_mail_template_group"
                ],
                2 => [
                    "id" => 84,
                    "name" => $this->langs->getMessage("backend.filed_names.mail_template"),
                    "text_id" => "mail_template"
                ],
                3 => [
                    "id" => 78,
                    "name" => $this->langs->getMessage("backend.filed_names.fields_of_user"),
                    "text_id" => "fields_of_user"
                ],
                4 => [
                    "id" => 80,
                    "name" => $this->langs->getMessage("backend.filed_names.address"),
                    "text_id" => "address"
                ]
            ],
            "template_id_group_id" => [
                0 => [
                    "template_id" => 3,
                    "group_id" => 1
                ],
                1 => [
                    "template_id" => 7,
                    "group_id" => 84
                ],
                2 => [
                    "template_id" => 54,
                    "group_id" => 85
                ]
            ],
            "field_id_name" => [
                0 => [
                    "id" => 1,
                    "name" => "Email",
                    "text_id" => "email_user"
                ],
                1 => [
                    "id" => 76,
                    "name" => $this->langs->getMessage("backend.filed_names.addresses_mail_template"),
                    "text_id" => "addresses_mail_template"
                ],
                2 => [
                    "id" => 78,
                    "name" => $this->langs->getMessage("backend.filed_names.mail_template_address"),
                    "text_id" => "mail_template_address"
                ],
                3 => [
                    "id" => 79,
                    "name" => $this->langs->getMessage("backend.filed_names.mail_template_name_from"),
                    "text_id" => "mail_template_name_from"
                ],
                4 => [
                    "id" => 80,
                    "name" => $this->langs->getMessage("backend.filed_names.mail_template_address_from"),
                    "text_id" => "mail_template_address_from"
                ],
                5 => [
                    "id" => 81,
                    "name" => $this->langs->getMessage("backend.filed_names.mail_template_mail_templ"),
                    "text_id" => "mail_template_mail_templ"
                ]
            ],
            "field_id_field_type" => [
                0 => [
                    "id" => 1,
                    "type_id" => 1
                ],
                1 => [
                    "id" => 76,
                    "type_id" => 1
                ],
                2 => [
                    "id" => 78,
                    "type_id" => 4
                ],
                3 => [
                    "id" => 79,
                    "type_id" => 1
                ],
                4 => [
                    "id" => 80,
                    "type_id" => 1
                ],
                5 => [
                    "id" => 81,
                    "type_id" => 7
                ]
            ],
            "dop_properties_fields" => [
                0 => [
                    "id" => 1,
                    "hint" => "",
                    "necessarily" => 0,
                    "indexed" => 0,
                    "filtered" => 0
                ],
                1 => [
                    "id" => 76,
                    "hint" => "",
                    "necessarily" => 0,
                    "indexed" => 0,
                    "filtered" => 0
                ],
                2 => [
                    "id" => 78,
                    "hint" => "",
                    "necessarily" => 0,
                    "indexed" => 0,
                    "filtered" => 0
                ],
                3 => [
                    "id" => 79,
                    "hint" => "",
                    "necessarily" => 0,
                    "indexed" => 0,
                    "filtered" => 0
                ],
                4 => [
                    "id" => 80,
                    "hint" => "",
                    "necessarily" => 0,
                    "indexed" => 0,
                    "filtered" => 0
                ],
                5 => [
                    "id" => 81,
                    "hint" => "",
                    "necessarily" => 0,
                    "indexed" => 0,
                    "filtered" => 0
                ]
            ],
            "field_id_reference_id" => [
                0 => [
                    "field_id" => 78,
                    "reference_id" => 54
                ]
            ],
            "group_id_field_id" => [
                0 => [
                    "group_id" => 1,
                    "field_id" => 1,
                    "sort_num" => 0
                ],
                1 => [
                    "group_id" => 84,
                    "field_id" => 78,
                    "sort_num" => 0
                ],
                2 => [
                    "group_id" => 84,
                    "field_id" => 79,
                    "sort_num" => 0
                ],
                3 => [
                    "group_id" => 84,
                    "field_id" => 80,
                    "sort_num" => 0
                ],
                4 => [
                    "group_id" => 84,
                    "field_id" => 81,
                    "sort_num" => 0
                ],
                5 => [
                    "group_id" => 85,
                    "field_id" => 76,
                    "sort_num" => 0
                ]
            ],
            "main_page" => [
                0 => [
                    "id" => 1,
                    "sub_domain" => 1
                ]
            ],
            "main_rights" => [
                0 => [
                    "user_id" => 1,
                    "section_text_id" => "events",
                    "read_right" => 1,
                    "create_right" => 1,
                    "edit_right" => 1,
                    "delete_right" => 1
                ],
                1 => [
                    "user_id" => 1,
                    "section_text_id" => "structure",
                    "read_right" => 1,
                    "create_right" => 1,
                    "edit_right" => 1,
                    "delete_right" => 1
                ],
                2 => [
                    "user_id" => 1,
                    "section_text_id" => "templates",
                    "read_right" => 1,
                    "create_right" => 1,
                    "edit_right" => 1,
                    "delete_right" => 1
                ],
                3 => [
                    "user_id" => 1,
                    "section_text_id" => "references",
                    "read_right" => 1,
                    "create_right" => 1,
                    "edit_right" => 1,
                    "delete_right" => 1
                ],
                4 => [
                    "user_id" => 1,
                    "section_text_id" => "orders",
                    "read_right" => 1,
                    "create_right" => 1,
                    "edit_right" => 1,
                    "delete_right" => 1
                ],
                5 => [
                    "user_id" => 1,
                    "section_text_id" => "feedback",
                    "read_right" => 1,
                    "create_right" => 1,
                    "edit_right" => 1,
                    "delete_right" => 1
                ],
                6 => [
                    "user_id" => 1,
                    "section_text_id" => "users",
                    "read_right" => 1,
                    "create_right" => 1,
                    "edit_right" => 1,
                    "delete_right" => 1
                ],
                7 => [
                    "user_id" => 1,
                    "section_text_id" => "plugins",
                    "read_right" => 1,
                    "create_right" => 1,
                    "edit_right" => 1,
                    "delete_right" => 1
                ],
                8 => [
                    "user_id" => 1,
                    "section_text_id" => "backups",
                    "read_right" => 1,
                    "create_right" => 1,
                    "edit_right" => 1,
                    "delete_right" => 1
                ],
                9 => [
                    "user_id" => 1,
                    "section_text_id" => "settings",
                    "read_right" => 1,
                    "create_right" => 1,
                    "edit_right" => 1,
                    "delete_right" => 1
                ]
            ],
            "page_id_active" => [
                0 => [
                    "id" => 1,
                    "active" => 1,
                    "no_indexed" => 0
                ]
            ],
            "page_id_uri" => [
                0 => [
                    "page_id" => 1,
                    "uri" => "main",
                    "full_path" => "/"
                ]
            ],
            "page_parent_id" => [
                0 => [
                    "id" => 1,
                    "parent_id" => 0,
                    "sort" => 1,
                    "countSortNum" => 1,
                    "sub_domain" => 1
                ]
            ],
            "settings" => [
                0 => [
                    "id" => 1,
                    "name" => "admin_email",
                    "value" => $params["email"]
                ]
            ],
            "site_url" => [
                0 => [
                    "id" => 1,
                    "url" => \Requests::getFullUrl()
                ]
            ]
        ];
        return $tableValues;
    }

    private function getCompanyTableValues($params)
    {
        $tableValues = [
            "sections" => [
                0 => [
                    "id" => 1,
                    "name" => "events",
                    "available" => 1,
                    "link" => "/events",
                    "class_ico" => "fa-bell-o"
                ],
                1 => [
                    "id" => 2,
                    "name" => "structure",
                    "available" => 1,
                    "link" => "/structure",
                    "class_ico" => "fa-file-o"
                ],
                2 => [
                    "id" => 3,
                    "name" => "templates",
                    "available" => 1,
                    "link" => "/type_template_data",
                    "class_ico" => "fa-archive"
                ],
                3 => [
                    "id" => 4,
                    "name" => "references",
                    "available" => 1,
                    "link" => "/references",
                    "class_ico" => "fa-book"
                ],
                4 => [
                    "id" => 5,
                    "name" => "orders",
                    "available" => 0,
                    "link" => "/emarket",
                    "class_ico" => "fa-shopping-cart"
                ],
                5 => [
                    "id" => 6,
                    "name" => "feedback",
                    "available" => 1,
                    "link" => "/webforms",
                    "class_ico" => "fa-envelope-o"
                ],
                6 => [
                    "id" => 7,
                    "name" => "users",
                    "available" => 1,
                    "link" => "/users",
                    "class_ico" => "fa-user"
                ],
                7 => [
                    "id" => 8,
                    "name" => "plugins",
                    "available" => 1,
                    "link" => "/plugins",
                    "class_ico" => "fa-plug"
                ],
                8 => [
                    "id" => 9,
                    "name" => "backups",
                    "available" => 1,
                    "link" => "/backups",
                    "class_ico" => "fa-download"
                ],
                9 => [
                    "id" => 10,
                    "name" => "settings",
                    "available" => 1,
                    "link" => "/settings",
                    "class_ico" => "fa-wrench"
                ]
            ]
        ];
        return $tableValues;
    }

    private function getEmarketTabelValues($params)
    {
        $tableValues = [
            "template_id_name" => [
                0 => [
                    "id" => 4,
                    "name" => $this->langs->getMessage("backend.sections.orders")
                ]
            ],
            "template_parent_id" => [
                0 => [
                    "id" => 4,
                    "parent_id" => NULL
                ]
            ],
            "sections" => [
                0 => [
                    "id" => 1,
                    "name" => "events",
                    "available" => 1,
                    "link" => "/events",
                    "class_ico" => "fa-bell-o"
                ],
                1 => [
                    "id" => 2,
                    "name" => "structure",
                    "available" => 1,
                    "link" => "/structure",
                    "class_ico" => "fa-file-o"
                ],
                2 => [
                    "id" => 3,
                    "name" => "templates",
                    "available" => 1,
                    "link" => "/type_template_data",
                    "class_ico" => "fa-archive"
                ],
                3 => [
                    "id" => 4,
                    "name" => "references",
                    "available" => 1,
                    "link" => "/references",
                    "class_ico" => "fa-book"
                ],
                4 => [
                    "id" => 5,
                    "name" => "orders",
                    "available" => 1,
                    "link" => "/emarket",
                    "class_ico" => "fa-shopping-cart"
                ],
                5 => [
                    "id" => 6,
                    "name" => "feedback",
                    "available" => 1,
                    "link" => "/webforms",
                    "class_ico" => "fa-envelope-o"
                ],
                6 => [
                    "id" => 7,
                    "name" => "users",
                    "available" => 1,
                    "link" => "/users",
                    "class_ico" => "fa-user"
                ],
                7 => [
                    "id" => 8,
                    "name" => "plugins",
                    "available" => 1,
                    "link" => "/plugins",
                    "class_ico" => "fa-plug"
                ],
                8 => [
                    "id" => 9,
                    "name" => "backups",
                    "available" => 1,
                    "link" => "/backups",
                    "class_ico" => "fa-download"
                ],
                9 => [
                    "id" => 10,
                    "name" => "settings",
                    "available" => 1,
                    "link" => "/settings",
                    "class_ico" => "fa-wrench"
                ]
            ]
        ];
        return $tableValues;
    }
}