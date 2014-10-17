<?php
header("Content-type: text/plain");

$db_name = 'wordpress';
$db_username = 'root';
$db_password = '';

$wp_weblog_title = 'WordPress';
$wp_user_name = 'Admin';
$wp_admin_password = 'password';
$wp_admin_password2 = 'password';
$wp_admin_email = 'mail@example.org';
$wp_blog_public = '1';

define('WP_ZIP_TMP', './wp.zip');
define('WP_ZIP_URL', 'https://de.wordpress.org/latest-de_DE.zip');
define('SALT_URL', 'https://api.wordpress.org/secret-key/1.1/salt/');
define('WP_CONFIG', './wordpress/wp-config.php');
define('WP_CONFIG_SAMPLE', './wordpress/wp-config-sample.php');
define('WP_UPLOADS', './wordpress/wp-content/uploads');

set_time_limit(300);

function rights($file = __DIR__, $right = 7)
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

if (rights() === false) {
    die('directory rights needed...');
}

function chmod_recursive($path)
{
    $dir = new DirectoryIterator($path);
    foreach ($dir as $item) {
        if ($item->isDir() && !$item->isDot()) {
            chmod($item->getPathname(), 0777);
            chmod_recursive($item->getPathname());
        } elseif ($item->isFile()) {
            chmod($item->getPathname(), 0666);
        }
    }
}

if (file_exists(WP_CONFIG_SAMPLE) === false) {
    file_put_contents(WP_ZIP_TMP, file_get_contents(WP_ZIP_URL));
    $zip = new ZipArchive;
    $res = $zip->open(WP_ZIP_TMP);
    if ($res === true) {
        $zip->extractTo('./');
        $zip->close();
    }
    sleep(5);
    unlink(WP_ZIP_TMP);
    mkdir(WP_UPLOADS);
}

if (file_exists(WP_CONFIG) === false && file_exists(WP_CONFIG_SAMPLE) === true) {
    $config = file_get_contents(WP_CONFIG_SAMPLE);
    $salt = file_get_contents(SALT_URL);
    if (empty($salt) === false) {
        $config = preg_replace("/define\('AUTH_KEY',\s*'put your unique phrase here'\);\s*define\('SECURE_AUTH_KEY',\s*'put your unique phrase here'\);\s*define\('LOGGED_IN_KEY',\s*'put your unique phrase here'\);\s*define\('NONCE_KEY',\s*'put your unique phrase here'\);\s*define\('AUTH_SALT',\s*'put your unique phrase here'\);\s*define\('SECURE_AUTH_SALT',\s*'put your unique phrase here'\);\s*define\('LOGGED_IN_SALT',\s*'put your unique phrase here'\);\s*define\('NONCE_SALT',\s*'put your unique phrase here'\);/sm", str_replace('$', '\\$', $salt), $config);
    }
    $config = str_replace('database_name_here', $db_name, $config);
    $config = str_replace('username_here', $db_username, $config);
    $config = str_replace('password_here', $db_password, $config);
    file_put_contents(WP_CONFIG, $config);
}

chmod('./wordpress', 0777);
chmod_recursive('./wordpress');

if (file_exists(WP_CONFIG) === false) {
    die('something is wrong...');
}

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
    chmod('.htaccess', 0777);
}

include_once 'https://raw.githubusercontent.com/tronsha/httpwebrequest/master/HttpWebRequest.php';

if (class_exists('HttpWebRequest') === true) {
    $install = new HttpWebRequest('http://' . $_SERVER["HTTP_HOST"] . '/wp-admin/install.php');
    $install->setMethod(HttpWebRequest::POST);
    $install->addGet('step', '2');
    $install->addPost('weblog_title', $wp_weblog_title);
    $install->addPost('user_name', $wp_user_name);
    $install->addPost('admin_password', $wp_admin_password);
    $install->addPost('admin_password2', $wp_admin_password2);
    $install->addPost('admin_email', $wp_admin_email);
    $install->addPost('blog_public', $wp_blog_public);
    $install->run();
}

if (file_exists('./wordpress/.htaccess') === false) {

    require_once('./wordpress/wp-load.php');
    $wpdb->update('wp_options',
        array('option_value' => '/%postname%/'),
        array('option_name' => 'permalink_structure'),
        array('%s'),
        array('%s')
    );

    $wphtaccess = '' . "\n";
    $wphtaccess .= '# BEGIN WordPress' . "\n";
    $wphtaccess .= '<IfModule mod_rewrite.c>' . "\n";
    $wphtaccess .= 'RewriteEngine On' . "\n";
    $wphtaccess .= 'RewriteBase /' . "\n";
    $wphtaccess .= 'RewriteRule ^index\.php$ - [L]' . "\n";
    $wphtaccess .= 'RewriteCond %{REQUEST_FILENAME} !-f' . "\n";
    $wphtaccess .= 'RewriteCond %{REQUEST_FILENAME} !-d' . "\n";
    $wphtaccess .= 'RewriteRule . /index.php [L]' . "\n";
    $wphtaccess .= '</IfModule>' . "\n";
    $wphtaccess .= '' . "\n";
    $wphtaccess .= '# END WordPress' . "\n";
    file_put_contents('./wordpress/.htaccess', $wphtaccess);
    chmod('./wordpress/.htaccess', 0777);
}

if (false) {
    unlink(__FILE__);
}

header("Location: /wp-login.php");
