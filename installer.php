<?php
$db_name = 'wordpress';
$db_username = 'root';
$db_password = '';

$wp_weblog_title = 'WordPress';
$wp_user_name = 'Admin';
$wp_admin_password = 'password';
$wp_admin_password2 = 'password';
$wp_admin_email = 'mail@example.org';
$wp_blog_public = '1';

$newUserName = 'Member';
$newUserPassword = 'password';
$newUserMail = 'member@example.org';

define('SALT_URL', 'https://api.wordpress.org/secret-key/1.1/salt/');
define('WP_CONFIG', './wordpress/wp-config.php');
define('WP_CONFIG_SAMPLE', './wordpress/wp-config-sample.php');
define('WP_UPLOADS', './wordpress/wp-content/uploads');

function __autoload($class)
{
    if ($class == 'HttpWebRequest') {
        require_once 'https://raw.githubusercontent.com/tronsha/httpwebrequest/master/HttpWebRequest.php';
    }
}

set_time_limit(300);

class WordpressInstaller
{
    private $wordpressSource = array();

    public function __construct()
    {
        $this->wordpressSource['en'] = 'https://wordpress.org/latest.zip';
        $this->wordpressSource['de'] = 'https://de.wordpress.org/latest-de_DE.zip';
    }

    public function hasRights($file = __DIR__, $right = 7)
    {
        $dir = substr(sprintf('%o', fileperms($file)), -4);
        $meArray = posix_getpwuid(posix_geteuid());
        $fileArray = posix_getpwuid(fileowner($file));
        if ($meArray['name'] == $fileArray['name']) {
            if ($dir[1] == $right) {
                return true;
            }
        } else {
            if ($dir[3] == $right) {
                return true;
            }
        }
        return false;
    }

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

    public function downloadWordpress()
    {
        file_put_contents('./wp.zip', file_get_contents($this->wordpressSource['de']));
    }

    public function unzipWordpress()
    {
        $zip = new ZipArchive;
        $res = $zip->open('./wp.zip');
        if ($res === true) {
            $zip->extractTo('./');
            $zip->close();
        }
    }

    public function installWordpressFiles()
    {
        if (file_exists(WP_CONFIG_SAMPLE) === false) {
            $this->downloadWordpress();
            $this->unzipWordpress();
            sleep(5);
            unlink('./wp.zip');
            mkdir(WP_UPLOADS);
        }
    }

    public function createConfig($database_name = 'wordpress', $username = 'root', $password = '')
    {
        if (file_exists(WP_CONFIG) === false && file_exists(WP_CONFIG_SAMPLE) === true) {
            $config = file_get_contents(WP_CONFIG_SAMPLE);
            $salt = file_get_contents(SALT_URL);
            if (empty($salt) === false) {
                $config = preg_replace("/define\('AUTH_KEY',\s*'put your unique phrase here'\);\s*define\('SECURE_AUTH_KEY',\s*'put your unique phrase here'\);\s*define\('LOGGED_IN_KEY',\s*'put your unique phrase here'\);\s*define\('NONCE_KEY',\s*'put your unique phrase here'\);\s*define\('AUTH_SALT',\s*'put your unique phrase here'\);\s*define\('SECURE_AUTH_SALT',\s*'put your unique phrase here'\);\s*define\('LOGGED_IN_SALT',\s*'put your unique phrase here'\);\s*define\('NONCE_SALT',\s*'put your unique phrase here'\);/sm",
                    str_replace('$', '\\$', $salt), $config);
            }
            $config = str_replace('database_name_here', $database_name, $config);
            $config = str_replace('username_here', $username, $config);
            $config = str_replace('password_here', $password, $config);
            file_put_contents(WP_CONFIG, $config);
            $this->chmod('./wordpress', true);
        }
    }

    public function rewriteSubdirectory()
    {
        if (file_exists('.htaccess') === false) {
            $htaccess = '<IfModule mod_rewrite.c>' . "\n";
            $htaccess .= 'RewriteEngine on' . "\n";
            $htaccess .= 'RewriteBase /' . "\n";
            $htaccess .= 'RewriteCond %{REQUEST_FILENAME} !-f' . "\n";
            $htaccess .= 'RewriteCond %{REQUEST_FILENAME} !-d [OR]' . "\n";
            $htaccess .= 'RewriteCond %{REQUEST_URI} ^/$' . "\n";
            $htaccess .= 'RewriteRule ^(.*) /wordpress/$1 [L]' . "\n";
            $htaccess .= '</IfModule>' . "\n";
            file_put_contents('.htaccess', $htaccess);
            chmod('.htaccess', 0666);
        }
    }

    public function installWordpress($weblog_title, $user_name, $admin_password, $admin_password2, $admin_email, $blog_public)
    {
        if (class_exists('HttpWebRequest') === true) {
            $install = new HttpWebRequest('http://' . $_SERVER["HTTP_HOST"] . '/wp-admin/install.php');
            $install->setMethod(HttpWebRequest::POST);
            $install->addGet('step', '2');
            $install->addPost('weblog_title', $weblog_title);
            $install->addPost('user_name', $user_name);
            $install->addPost('admin_password', $admin_password);
            $install->addPost('admin_password2', $admin_password2);
            $install->addPost('admin_email', $admin_email);
            $install->addPost('blog_public', $blog_public);
            $install->run();
        }
    }

    public function setPermalinkToPostname()
    {
        if (file_exists('./wordpress/.htaccess') === false) {
            $GLOBALS['wpdb']->update('wp_options',
                array('option_value' => '/%postname%/'),
                array('option_name' => 'permalink_structure'),
                array('%s'),
                array('%s')
            );
            $htaccess = '' . "\n";
            $htaccess .= '# BEGIN WordPress' . "\n";
            $htaccess .= '<IfModule mod_rewrite.c>' . "\n";
            $htaccess .= 'RewriteEngine On' . "\n";
            $htaccess .= 'RewriteBase /' . "\n";
            $htaccess .= 'RewriteRule ^index\.php$ - [L]' . "\n";
            $htaccess .= 'RewriteCond %{REQUEST_FILENAME} !-f' . "\n";
            $htaccess .= 'RewriteCond %{REQUEST_FILENAME} !-d' . "\n";
            $htaccess .= 'RewriteRule . /index.php [L]' . "\n";
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
    public function newUser($name, $password, $mail, $role = 'subscriber')
    {
        if (username_exists($name) === null) {
            wp_create_user($name, $password, $mail);
            $userId = username_exists($name);
            $user = new WP_User($userId);
            $user->set_role($role);
        }
    }
}

if (isset($_POST['install'])) {
    $installer = new WordpressInstaller;
    if ($installer->hasRights() === false) {
        die('directory rights needed...');
    }
    $installer->installWordpressFiles();
    $installer->createConfig($db_name, $db_username, $db_password);
    if (file_exists(WP_CONFIG) === false) {
        die('something is wrong...');
    }
    $installer->rewriteSubdirectory();
    $installer->installWordpress($wp_weblog_title, $wp_user_name, $wp_admin_password, $wp_admin_password2, $wp_admin_email, $wp_blog_public);
    if (file_exists('./wordpress/wp-load.php') === false) {
        die('wp-load.php not found');
    }
    require_once('./wordpress/wp-load.php');
    $installer->setPermalinkToPostname();
    $installer->newUser($newUserName, $newUserPassword, $newUserMail);
    if (false) {
        unlink(__FILE__);
    }
    header("Location: /wp-login.php");
    die;
}
?><!doctype html>
<html>
<head>
    <title>WordPress Installer</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
    </style>
</head>
<body>
<div style="text-align: center;">
    <form action="./installer.php" method="post">
        <?php if(true): ?>
            <input type="submit" name="install" value="Install">
        <?php endif; ?>
    </form>
</div>
<h1>WordPress Installer</h1>
</body>
</html>
