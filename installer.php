<?php

$default = array(
    'title' => 'WordPress',
    'admin' => array(
        'name' => 'Admin',
        'password' => 'password',
        'email' => 'mail@example.org',
    ),
    'public' => '1',
    'user' => array(
        'name' => '',
        'password' => '',
        'email' => '',
        'role' => 'author',
    ),
    'db' => array(
        'name' => 'wordpress',
        'username' => 'root',
        'password' => '',
        'host' => 'localhost',
        'port' => '3306',
    ),
);

$plugins = array(
    array(
        'name' => 'WordPress SEO',
        'url' => 'https://downloads.wordpress.org/plugin/wordpress-seo.latest-stable.zip',
        'selected' => '1'
    ),
    array(
        'name' => 'Contact Form 7',
        'url' => 'https://downloads.wordpress.org/plugin/contact-form-7.latest-stable.zip',
        'selected' => '1'
    ),
);

$config = array(
    'src' => array(
        'en' => 'https://wordpress.org/latest.zip',
        'de' => 'https://de.wordpress.org/latest-de_DE.zip',
    ),
    'salt' => 'https://api.wordpress.org/secret-key/1.1/salt/',
    'table_prefix' => 'wp_',
    'php_version' => '5.2.4',
);

set_time_limit(300);
error_reporting(E_ALL ^ E_NOTICE);

if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

define('WP_CONFIG', './wordpress/wp-config.php');
define('WP_CONFIG_SAMPLE', './wordpress/wp-config-sample.php');

//$login = array(
//    'user' => 'Installer',
//    'password' => 'wordpress'
//);
//
//if (isset($_SERVER['REDIRECT_REMOTE_USER']) === false && isset($_SERVER['REMOTE_USER']) === false) {
//    if (empty($login['user']) === false && empty($login['password']) === false && $_SERVER['PHP_AUTH_USER'] != $login['user'] && $_SERVER['PHP_AUTH_PW'] != $login['password']) {
//        header('WWW-Authenticate: Basic realm="Login"');
//        header('HTTP/1.0 401 Unauthorized');
//        die ('Not authorized');
//    }
//}

class WordpressInstaller
{
    private $wpSrc;
    private $wpSalt;
    private $wpTablePrefix;
    private $wpPhpVersion;

    public function __construct($config)
    {
        $this->wpSrc = $config['src'];
        $this->wpSalt = $config['salt'];
        $this->wpTablePrefix = $config['table_prefix'];
        $this->wpPhpVersion = $config['php_version'];
    }

    public function checkSystem()
    {
        $error = null;

        if ($this->isApache() === false) {
            $error[] = 'Apache Server is required.';
        }
        if ($this->checkPhpVersion() === false) {
            $error[] = 'PHP ' . $this->wpPhpVersion . ' or higher is required.';
        }
        if ($this->hasCurl() === false) {
            $error[] = 'Curl extension is required and not loaded.';
        }
        if ($this->isWritable() === false) {
            $error[] = 'Directory write permissions are required...<br>Change the permissions and <a href="javascript:location.reload();">reload</a> this page.';
        }

        return $error === null ? null : implode('<br><br>', $error);
    }

    public function isApache()
    {
        if (strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === false) {
            return false;
        } else {
            return true;
        }
    }

    public function checkPhpVersion()
    {
        if (version_compare(PHP_VERSION, $this->wpPhpVersion, '>=')) {
            return true;
        } else {
            return false;
        }
    }

    public function hasCurl()
    {
        if (extension_loaded('curl')) {
            return true;
        } else {
            return false;
        }
    }

    public function isWritable()
    {
        return is_writable(__DIR__);
    }

//    public function hasRights($file = __DIR__, $right = 7)
//    {
//        $dir = substr(sprintf('%o', fileperms($file)), -4);
//        $meArray = posix_getpwuid(posix_geteuid());
//        $fileArray = posix_getpwuid(fileowner($file));
//        if ($meArray['name'] == $fileArray['name']) {
//            if ($dir[1] == $right) {
//                return true;
//            }
//        } else {
//            if ($dir[3] == $right) {
//                return true;
//            }
//        }
//        return false;
//    }

    public function chmod($path, $recursive = false)
    {
        chmod($path, is_file($path) ? 0666 : 0777);
        if ($recursive === true) {
            $dir = new DirectoryIterator($path);
            foreach ($dir as $item) {
                if ($item->isDir() && !$item->isDot()) {
                    chmod($item->getPathname(), 0777);
                    $this->chmod($item->getPathname(), true);
                } elseif ($item->isFile()) {
                    chmod($item->getPathname(), 0666);
                }
            }
        }
    }

    public function download($file, $zip = './tmp.zip')
    {
        file_put_contents($zip, file_get_contents($file));
    }

    public function downloadWordpress($lang = 'en')
    {
        if (isset($this->wpSrc[$lang]) === true) {
            $file = $this->wpSrc[$lang];
        } else {
            $file = $this->wpSrc['en'];
        }
        $this->download($file, './wp.zip');
    }

    /**
     * @see http://php.net/manual/en/class.ziparchive.php
     */
    public function unzip($file = './wp.zip', $path = './')
    {
        $zip = new ZipArchive;
        $res = $zip->open($file);
        if ($res === true) {
            $zip->extractTo($path);
            $zip->close();
        }
    }

    public function unlink($file = './wp.zip')
    {
        unlink($file);
    }

    public function createUploadDir()
    {
        mkdir('./wordpress/wp-content/uploads');
    }

    public function installWordpress($lang = 'en')
    {
        if (file_exists(WP_CONFIG_SAMPLE) === false) {
            $this->downloadWordpress($lang);
            $this->unzip('./wp.zip', './');
            sleep(5);
            $this->unlink('./wp.zip');
            $this->createUploadDir();
            $this->chmod('./wordpress', true);
        }
    }

    public function installTheme()
    {
        $this->unzip('./theme.zip', './wordpress/wp-content/themes/');
        sleep(5);
        $this->unlink('./theme.zip');
        $this->chmod('./wordpress/wp-content/themes/', true);
    }

    public function installPlugin($src)
    {
        $this->download($src, './plugin.zip');
        $this->unzip('./plugin.zip', './wordpress/wp-content/plugins/');
        sleep(5);
        $this->unlink('./plugin.zip');
        $this->chmod('./wordpress/wp-content/plugins/', true);
    }

    public function getRandomTablePrefix($prefix = null, $length = 3, $algorithm = 'sha256', $iteration = 0)
    {
        if (empty($prefix) === true) {
            $prefix = time();
        }
        if (empty($algorithm) === false) {
            $prefix = hash($algorithm, $prefix);
            $prefix = base_convert($prefix, 16, 36);
            $prefix = preg_replace('/[0-9]/', '', $prefix);
        }
        if ($length > 0) {
            $prefix = substr($prefix, 0, $length);
            if (strlen($prefix) != $length && $iteration < 100) {
                return $this->getRandomTablePrefix($prefix, $length, $algorithm, ++$iteration);
            }
        }
        return $prefix . '_';
    }

    public function createConfig(
        $databasename = 'wordpress',
        $username = 'root',
        $password = '',
        $host = 'localhost',
        $port = '3306'
    ) {
        if (file_exists(WP_CONFIG) === false && file_exists(WP_CONFIG_SAMPLE) === true) {
            $config = file_get_contents(WP_CONFIG_SAMPLE);
            $salt = file_get_contents($this->wpSalt);
            if (empty($salt) === false) {
                $config = preg_replace(
                    "/define\('AUTH_KEY',\s*'put your unique phrase here'\);\s*define\('SECURE_AUTH_KEY',\s*'put your unique phrase here'\);\s*define\('LOGGED_IN_KEY',\s*'put your unique phrase here'\);\s*define\('NONCE_KEY',\s*'put your unique phrase here'\);\s*define\('AUTH_SALT',\s*'put your unique phrase here'\);\s*define\('SECURE_AUTH_SALT',\s*'put your unique phrase here'\);\s*define\('LOGGED_IN_SALT',\s*'put your unique phrase here'\);\s*define\('NONCE_SALT',\s*'put your unique phrase here'\);/sm",
                    str_replace('$', '\\$', $salt),
                    $config
                );
            }
            $config = str_replace(
                'define(\'DB_NAME\', \'database_name_here\');',
                'define(\'DB_NAME\', \'' . $databasename . '\');',
                $config
            );
            $config = str_replace(
                'define(\'DB_USER\', \'username_here\');',
                'define(\'DB_USER\', \'' . $username . '\');',
                $config
            );
            $config = str_replace(
                'define(\'DB_PASSWORD\', \'password_here\');',
                'define(\'DB_PASSWORD\', \'' . $password . '\');',
                $config
            );
            $config = str_replace(
                'define(\'DB_HOST\', \'localhost\');',
                'define(\'DB_HOST\', \'' . $host . ($port != '3306' ? ':' . $port : '') . '\');',
                $config
            );
            if (empty($this->wpTablePrefix) === true) {
                $table_prefix = $this->getRandomTablePrefix();
            } else {
                $table_prefix = $this->wpTablePrefix;
            }
            $config = str_replace('table_prefix  = \'wp_\';', 'table_prefix  = \'' . $table_prefix . '\';', $config);
            file_put_contents(WP_CONFIG, $config);
            $this->chmod(WP_CONFIG);
        }
    }

    /**
     * @see http://httpd.apache.org/docs/current/howto/htaccess.html
     */
    public function rewriteSubdirectory()
    {
        if (file_exists('.htaccess') === false) {
            $htaccess = '<IfModule mod_rewrite.c>' . "\n";
            $htaccess .= 'RewriteEngine on' . "\n";
            $htaccess .= 'RewriteBase /' . "\n";
            $htaccess .= 'RewriteCond %{REQUEST_FILENAME} !-f' . "\n";
            $htaccess .= 'RewriteCond %{REQUEST_FILENAME} !-d [OR]' . "\n";
            $htaccess .= 'RewriteCond %{REQUEST_URI} ^' . $this->getUrlPath() . '/$' . "\n";
            $htaccess .= 'RewriteRule ^(.*)$ ' . $this->getUrlPath() . '/wordpress/$1 [L]' . "\n";
            $htaccess .= '</IfModule>' . "\n";
            file_put_contents('.htaccess', $htaccess);
            chmod('.htaccess', 0666);
        }
    }

    public function getUrlPath()
    {
        $path = dirname($_SERVER['PHP_SELF']);
        return ($path == '/' ? '' : $path);
    }

    /**
     * Create Admin User
     * @see http://php.net/manual/en/book.curl.php
     */
    public function setupWordpress(
        $weblog_title,
        $user_name,
        $admin_password,
        $admin_password2,
        $admin_email,
        $blog_public
    ) {

        $url = 'http://' . $_SERVER["HTTP_HOST"] . $this->getUrlPath() . '/wp-admin/install.php?step=2';
        $fields = array(
            'weblog_title' => urlencode($weblog_title),
            'user_name' => urlencode($user_name),
            'admin_password' => urlencode($admin_password),
            'admin_password2' => urlencode($admin_password2),
            'admin_email' => urlencode($admin_email),
            'blog_public' => urlencode($blog_public)
        );
        $fieldsString = '';
        foreach ($fields as $key => $value) {
            $fieldsString .= $key . '=' . $value . '&';
        }
        rtrim($fieldsString, '&');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldsString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    /** 
     * @see http://codex.wordpress.org/Function_Reference/update_option
     */
    public function setBlogDescription($description = '')
    {
        update_option('blogdescription', $description);
    }

    /**
     * Set Permalink to Postname
     * @see http://httpd.apache.org/docs/current/howto/htaccess.html
     */
    public function setPermalinkToPostname()
    {
        if (file_exists('./wordpress/.htaccess') === false) {
            $GLOBALS['wp_rewrite']->set_permalink_structure('/%postname%/');
            $htaccess = '' . "\n";
            $htaccess .= '# BEGIN WordPress' . "\n";
            $htaccess .= '<IfModule mod_rewrite.c>' . "\n";
            $htaccess .= 'RewriteEngine On' . "\n";
            $htaccess .= 'RewriteBase /' . "\n";
            $htaccess .= 'RewriteRule ^index\.php$ - [L]' . "\n";
            $htaccess .= 'RewriteCond %{REQUEST_FILENAME} !-f' . "\n";
            $htaccess .= 'RewriteCond %{REQUEST_FILENAME} !-d' . "\n";
            $htaccess .= 'RewriteRule . ' . $this->getUrlPath() . '/index.php [L]' . "\n";
            $htaccess .= '</IfModule>' . "\n";
            $htaccess .= '' . "\n";
            $htaccess .= '# END WordPress' . "\n";
            file_put_contents('./wordpress/.htaccess', $htaccess);
            chmod('./wordpress/.htaccess', 0666);
        }
    }

    /**
     * Create User
     * @see http://codex.wordpress.org/Function_Reference/username_exists
     * @see http://codex.wordpress.org/Function_Reference/wp_create_user
     * @see http://codex.wordpress.org/Class_Reference/WP_User
     */
    public function addUser($name, $password, $email, $role = 'subscriber')
    {
        if (username_exists($name) === null) {
            wp_create_user($name, $password, $email);
            $userId = username_exists($name);
            $user = new WP_User($userId);
            $user->set_role($role);
        }
    }

    /**
     * Switch Theme
     * @see http://codex.wordpress.org/Function_Reference/wp_get_theme
     * @see http://codex.wordpress.org/Function_Reference/switch_theme
     */
    public function switchTheme($stylesheet)
    {
        $theme = wp_get_theme($stylesheet);
        if ($theme->exists() && $theme->is_allowed()) {
            switch_theme($theme->get_stylesheet());
        }
    }
    
    /** 
     * Page or posts 
     * @see http://codex.wordpress.org/Function_Reference/update_option
     * @see http://codex.wordpress.org/Function_Reference/wp_update_post
     */
    public function setFrontPage($show = 'page', $content = '')
    {
        update_option('show_on_front', $show);
        update_option('page_on_front', $show == 'page' ? '2' : '0');
        wp_update_post(
            array(
                'ID' => 2,
                'post_content' => $content,
                'post_title' => 'Home',
                'post_name' => 'home'
            )
        );
    }

    /**
     * @see http://php.net/manual/en/ref.pdo-mysql.php
     */
    public function isInstalled()
    {
        $config = file_get_contents(WP_CONFIG);
        preg_match("/define\('DB_HOST', '([^']+)'\);/", $config, $matches);
        $config_db_host = $matches[1];
        preg_match("/define\('DB_NAME', '([^']+)'\);/", $config, $matches);
        $config_db_name = $matches[1];
        preg_match("/define\('DB_USER', '([^']+)'\);/", $config, $matches);
        $config_db_user = $matches[1];
        preg_match("/define\('DB_PASSWORD', '([^']+)'\);/", $config, $matches);
        $config_db_password = $matches[1];
        preg_match("/table_prefix\s*\=\s*'([^']+)';/", $config, $matches);
        $config_table_prefix = $matches[1];
        $db = new PDO(
            'mysql:host=' . $config_db_host . ';dbname=' . $config_db_name,
            $config_db_user,
            $config_db_password,
            array(PDO::ATTR_PERSISTENT => false)
        );
        $stmt = $db->prepare('SHOW TABLES LIKE "' . $config_table_prefix . 'options"');
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            return false;
        }
        return true;
    }
}

$installer = new WordpressInstaller($config);

if (isset($_POST['ready']) === true) {
    if (isset($_POST['delete']) === true) {
        unlink(__FILE__);
    }
    header('Location: ' . $installer->getUrlPath() . '/wp-login.php');
    die;
}

if (($errormessage = $installer->checkSystem()) !== null) {
    $step = 0;
} else {
    if (isset($_GET['step']) === true) {
        if ($_GET['step'] == 2) {
            if (!file_exists(WP_CONFIG_SAMPLE) && isset($_POST['lang']) === true) {
                $installer->installWordpress($_POST['lang']);
            }
            $step = 2;
        }
        if ($_GET['step'] == 3) {
            if (file_exists(WP_CONFIG_SAMPLE) && !file_exists(WP_CONFIG) && isset($_POST['db_name']) === true && isset($_POST['db_username']) === true && isset($_POST['db_password']) === true) {
                $installer->createConfig(
                    $_POST['db_name'],
                    $_POST['db_username'],
                    $_POST['db_password'],
                    $_POST['db_host'],
                    $_POST['db_port']
                );
            }
            if (!file_exists('./.htaccess')) {
                $installer->rewriteSubdirectory();
            }
            $step = 3;
        }
        if (isset($_GET['install']) === true && $_GET['install'] == 'wp') {
            $installer->setupWordpress(
                $_POST['weblog_title'],
                $_POST['user_name'],
                $_POST['admin_password'],
                $_POST['admin_password2'],
                $_POST['admin_email'],
                $_POST['blog_public']
            );
            require_once './wordpress/wp-load.php';
            $installer->setBlogDescription($_POST['weblog_description']);
        }
        if ($_GET['step'] == 4) {
            if (isset($_GET['theme']) === true && $_GET['theme'] == 'upload' && isset($_FILES['themezip']['tmp_name']) === true) {
                if (move_uploaded_file($_FILES['themezip']['tmp_name'], './theme.zip')) {
                    $installer->installTheme();
                }
            }
            $step = 4;
        }
        if ($_GET['step'] == 5) {
            if (isset($_GET['theme']) === true && $_GET['theme'] == 'activate' && isset($_POST['theme']) === true) {
                require_once './wordpress/wp-load.php';
                $installer->switchTheme($_POST['theme']);
            }
            $step = 5;
        }
        if ($_GET['step'] == 6) {
            if (isset($_GET['plugin']) === true && $_GET['plugin'] == 'install' && isset($_POST['plugins']) === true) {
                foreach ($_POST['plugins'] as $plugin) {
                    $installer->installPlugin($plugin);
                }
            }
            $step = 6;
        }
        if ($_GET['step'] == 7) {
            if (isset($_GET['user']) === true && $_GET['user'] == 'add' && isset($_POST['name']) === true && isset($_POST['password']) === true && isset($_POST['email']) === true && isset($_POST['role']) === true) {
                require_once './wordpress/wp-load.php';
                $installer->addUser($_POST['name'], $_POST['password'], $_POST['email'], $_POST['role']);
            }
            $step = 7;
        }
        if ($_GET['step'] == 8) {
            if (isset($_GET['permalink']) === true && $_GET['permalink'] == 'postname') {
                require_once './wordpress/wp-load.php';
                $installer->setPermalinkToPostname();
            }
            $step = 8;
        }
        if ($_GET['step'] == 9) {
            if (isset($_POST['frontpage'])) {
                require_once './wordpress/wp-load.php';
                $installer->setFrontPage($_POST['frontpage']);
            }
            $step = 9;
        }
    }
    if (!file_exists(WP_CONFIG_SAMPLE)) {
        $step = 1;
    }
    if (file_exists(WP_CONFIG_SAMPLE) && !file_exists(WP_CONFIG)) {
        $step = 2;
    }
    if (file_exists(WP_CONFIG) && $step < 3) {
        $step = 3;
    }
    if (file_exists(WP_CONFIG) && $step >= 3) {
        if ($installer->isInstalled() === false) {
            $step = 3;
        } elseif ($step == 3) {
            $step = 4;
        }
    }
}
?><!doctype html>
<html>
<head>
    <title>WordPress Installer</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script type="application/javascript" src="//code.jquery.com/jquery-1.11.1.min.js"></script>
    <link href='http://fonts.googleapis.com/css?family=Playball' rel='stylesheet' type='text/css'>
    <style>
        html {
            background-color: #000000;
            color: #ffffff;
            font-family: verdana, tahoma, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
        }

        h1 {
            font-family: 'Playball';
            font-size: 42px;
            font-weight: 100;
            text-shadow: 0 0 10px #FFFFFF, 0 0 20px #FFFFFF, 0 0 30px #FFFFFF, 0 0 40px #21759B, 0 0 70px #21759B, 0 0 80px #21759B, 0 0 100px #21759B;
            display: inline;
        }

        fieldset {
            background: linear-gradient(#000000, #21759B);
            border: none;
            border-radius: 10px;
            margin-left: auto;
            margin-right: auto;
            max-width: 100%;
            padding: 30px;
            width: 300px;
        }

        fieldset select,
        fieldset input[type="text"],
        fieldset input[type="email"],
        fieldset input[type="password"] {
            display: block;
            margin-bottom: 6px;
            width: 100%;
        }

        fieldset input[type="submit"] {
            margin-top: 20px;
        }

        #logo {
            height: 70px;
            position: relative;
            top: 15px;
            width: 70px;
        }
    </style>
</head>
<body>
<div style="text-align: center;">
    <h1>
        <img id="logo" alt="WordPress" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAG4AAABuCAMAAADxhdbJAAAC8VBMVEX///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////8xx/ZkAAAA+nRSTlMAAQIDBAUGBwgJCgsMDQ4PEBESExQVFhcYGRobHB0eHyAhIiMkJSYnKCkqKywtLi8wMTIzNDU2Nzg5Ojs8PT4/QEFCQ0RFRkdISktMTU5PUFFSU1RVVldYWVpbXF1e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">
        Installer
    </h1>
    <br><br><br>
    <?php if ($step == 0): ?>
        <?php echo $errormessage; ?>
    <?php elseif ($step == 1): ?>
        <form id="step1" action="./installer.php?step=2" method="post">
            <fieldset>
                <legend align="left">Language</legend>
                <select name="lang">
                    <option value="en">english</option>
                    <option value="de" selected>deutsch</option>
                </select>
                <input type="submit" name="next" value="Next">
            </fieldset>
        </form>
    <?php elseif ($step == 2): ?>
        <form id="step2" action="./installer.php?step=3" method="post">
            <fieldset>
                <legend align="left">MySQL Database</legend>
                <input type="text" required="required" placeholder="Database Name" name="db_name" value="<?= $default['db']['name'] ?>">
                <input type="text" required="required" placeholder="Database User" name="db_username" value="<?= $default['db']['username'] ?>">
                <input type="text" placeholder="Database Password" name="db_password" value="<?= $default['db']['password'] ?>">
                <input type="text" required="required" placeholder="Host" name="db_host" value="<?= $default['db']['host'] ?>">
                <input type="text" required="required" placeholder="Port" name="db_port" value="<?= $default['db']['port'] ?>">
                <input type="submit" name="next" value="Next">
            </fieldset>
        </form>
    <?php elseif ($step == 3): ?>
        <form id="step3" action="./installer.php?step=4&amp;install=wp" method="post">
            <fieldset>
                <legend align="left">Setup</legend>
                <input type="text" placeholder="Site Title" name="weblog_title" value="<?= $default['title'] ?>">
                <input type="text" placeholder="Site Description" name="weblog_description" value="">
                <input type="text" required="required" placeholder="Admin Name" name="user_name" value="<?= $default['admin']['name'] ?>">
                <input type="password" required="required" placeholder="Admin Password" name="admin_password" value="<?= $default['admin']['password'] ?>">
                <input type="password" required="required" placeholder="Admin Password" name="admin_password2" value="<?= $default['admin']['password'] ?>">
                <input type="email" required="required" placeholder="Admin E-Mail" name="admin_email" value="<?= $default['admin']['email'] ?>">
                <label for="blog_public">Blog Public</label>
                <input type="checkbox" name="blog_public" id="blog_public" value="1" <?= $default['public'] == 1 ? 'checked' : '' ?>>
                <br>
                <input type="submit" name="next" value="Next">
            </fieldset>
        </form>
    <?php elseif ($step == 4): ?>
        <fieldset>
            <legend align="left">Install Theme</legend>
            <?php
            require_once './wordpress/wp-load.php';
            require_once './wordpress/wp-admin/includes/admin.php';
            require_once './wordpress/wp-admin/includes/theme-install.php';
            install_themes_upload();
            ?>
            <script type="application/javascript">
                jQuery(document).ready(function () {
                    jQuery('.wp-upload-form').attr('action', './installer.php?step=4&theme=upload');
                });
            </script>
        </fieldset>
        <br>
        <form id="step4" action="./installer.php?step=5" method="post">
            <input type="submit" name="next" value="Next">
        </form>
    <?php elseif ($step == 5): ?>
        <?php
        require_once './wordpress/wp-load.php';
        require_once './wordpress/wp-admin/includes/admin.php';
        ?>
        <form id="step5theme" action="./installer.php?step=5&amp;theme=activate" method="post">
            <fieldset>
                <legend align="left">Activate Theme</legend>
                <select name="theme">
                    <?php
                    $themes = wp_prepare_themes_for_js();
                    foreach ($themes as $theme) {
                        echo '<option value="' . $theme['id'] . '"' . ($theme['active'] ? ' selected' : '') . '>' . $theme['name'] . '</option>';
                    }
                    ?>
                </select>
                <input type="submit" value="Activate">
            </fieldset>
        </form>
        <br>
        <form id="step5" action="./installer.php?step=6" method="post">
            <input type="submit" name="next" value="Next">
        </form>
    <?php elseif ($step == 6): ?>
        <form id="step6plugins" action="./installer.php?step=6&amp;plugin=install" method="post">
            <fieldset>
                <legend align="left">Install Plugins</legend>
                <select name="plugins[]" size="<?php echo count($plugins); ?>" multiple>
                    <?php
                    foreach ($plugins as $plugin) {
                        echo '<option value="' . $plugin['url'] . '"' . ($plugin['selected'] == '1' ? ' selected' : '') . '>' . $plugin['name'] . '</option>';
                    }
                    ?>
                </select>
                <input type="submit" value="Install">
            </fieldset>
        </form>
        <br>
        <form id="step6" action="./installer.php?step=7" method="post">
            <input type="submit" name="next" value="Next">
        </form>
    <?php elseif ($step == 7): ?>
        <?php
        require_once './wordpress/wp-load.php';
        require_once './wordpress/wp-admin/includes/admin.php';
        ?>
        <form id="step7user" action="./installer.php?step=7&amp;user=add" method="post">
            <fieldset>
                <legend align="left">New User</legend>
                <input type="text" placeholder="Name" name="name" value="<?= $default['user']['name'] ?>">
                <input type="password" placeholder="Password" name="password" value="<?= $default['user']['password'] ?>">
                <input type="text" placeholder="E-Mail" name="email" value="<?= $default['user']['email'] ?>">
                <select name="role">
                    <?php
                    /**
                     * @see http://codex.wordpress.org/Function_Reference/wp_dropdown_roles
                     */
                    wp_dropdown_roles($default['user']['role']);
                    ?>
                </select>
                <input type="submit" value="Add">
            </fieldset>
        </form>
        <br>
        <form id="step7" action="./installer.php?step=8" method="post">
            <input type="submit" name="next" value="Next">
        </form>
    <?php elseif ($step == 8): ?>
        <form id="step8permalink" action="./installer.php?step=8&amp;permalink=postname" method="post">
            <fieldset>
                <legend align="left">Permalink</legend>
                <input type="submit" value="Postname">
            </fieldset>
        </form>
        <br>
        <form id="step8" action="./installer.php?step=9" method="post">
            <input type="submit" name="next" value="Next">
        </form>
    <?php elseif ($step == 9): ?>
        <form id="step9frontpage" action="./installer.php?step=9" method="post">
            <fieldset>
                <legend align="left">Frontpage</legend>
                <?php
                require_once './wordpress/wp-load.php';
                $frontpage = get_option('show_on_front');
                ?>
                <select name="frontpage">
                    <option value="page"<?php echo $frontpage == 'page' ? ' selected' : ''; ?>>Page</option>
                    <option value="posts"<?php echo $frontpage == 'posts' ? ' selected' : ''; ?>>Posts</option>
                </select>
                <input type="submit" value="Save">
            </fieldset>
        </form>
        <br>
        <form id="step9" action="./installer.php?step=10" method="post">
            <input type="submit" name="next" value="Next">
        </form>
    <?php else: ?>
        <form action="./installer.php" method="post">
            <label for="delete">Remove Script</label> <input type="checkbox" name="delete" id="delete" value="1" checked>
            <input type="hidden" name="ready" value="1">
            <br><br>
            <input type="submit" name="next" value="Ready">
        </form>
    <?php endif; ?>
</div>
</body>
</html>
