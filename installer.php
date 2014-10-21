<?php
$db_name = 'wordpress';
$db_username = 'root';
$db_password = '';

define('WP_CONFIG', './wordpress/wp-config.php');
define('WP_CONFIG_SAMPLE', './wordpress/wp-config-sample.php');

set_time_limit(300);

$config = array(
    'src' => array(
        'en' => 'https://wordpress.org/latest.zip',
        'de' => 'https://de.wordpress.org/latest-de_DE.zip'
    ),
    'salt' => 'https://api.wordpress.org/secret-key/1.1/salt/'
);

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
    )
);

$plugins = array(
    array('name' => 'wpSEO', 'url' => 'http://cdn.wpseo.de/plugin/v3/zip/latest/wpseo.zip'),
    array('name' => 'Contact Form 7', 'url' => 'https://downloads.wordpress.org/plugin/contact-form-7.4.0.1.zip'),
    array('name' => 'InfiniteWP', 'url' => 'https://downloads.wordpress.org/plugin/iwp-client.zip'),
    array('name' => 'Members', 'url' => 'https://downloads.wordpress.org/plugin/members.0.2.4.zip'),
);

if (isset($_POST['ready']) === true) {
    if (isset($_POST['delete']) === true) {
        unlink(__FILE__);
    }
    header("Location: /wp-login.php");
    die;
}

class WordpressInstaller
{
    private $wpSrc;
    private $wpSalt;

    public function __construct($config)
    {
        $this->wpSrc = $config['src'];
        $this->wpSalt = $config['salt'];
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

    public function download($file, $zip = './tmp.zip'){
        file_put_contents($zip, file_get_contents($file));
    }

    public function downloadWpZip($lang = 'en')
    {
        if (isset($this->wpSrc[$lang]) === true) {
            $file = $this->wpSrc[$lang];
        } else {
            $file = $this->wpSrc['en'];
        }
        $this->download($file, './wp.zip');
    }

    public function unzip($file = './wp.zip', $path = './')
    {
        $zip = new ZipArchive;
        $res = $zip->open($file);
        if ($res === true) {
            $zip->extractTo($path);
            $zip->close();
        }
    }

    public function removeZip($file = './wp.zip')
    {
        unlink($file);
    }

    public function createUploadDir()
    {
        mkdir('./wordpress/wp-content/uploads');
    }

    public function installZip($file = './wp.zip', $path = './')
    {
        $this->unzip($file, $path);
        sleep(5);
        $this->removeZip($file);
    }

    public function installWordpress($lang = 'en')
    {
        if (file_exists(WP_CONFIG_SAMPLE) === false) {
            $this->downloadWpZip($lang);
            $this->installZip('./wp.zip', './');
            $this->createUploadDir();
            $this->chmod('./wordpress', true);
        }
    }

    public function installTheme()
    {
        $this->installZip('./theme.zip', './wordpress/wp-content/themes/');
        $this->chmod('./wordpress/wp-content/themes/', true);
    }

    public function installPlugin($src)
    {
        $this->download($src, './plugin.zip');
        $this->installZip('./plugin.zip', './wordpress/wp-content/plugins/');
        $this->chmod('./wordpress/wp-content/plugins/', true);
    }

    public function createConfig($database_name = 'wordpress', $username = 'root', $password = '')
    {
        if (file_exists(WP_CONFIG) === false && file_exists(WP_CONFIG_SAMPLE) === true) {
            $config = file_get_contents(WP_CONFIG_SAMPLE);
            $salt = file_get_contents($this->wpSalt);
            if (empty($salt) === false) {
                $config = preg_replace("/define\('AUTH_KEY',\s*'put your unique phrase here'\);\s*define\('SECURE_AUTH_KEY',\s*'put your unique phrase here'\);\s*define\('LOGGED_IN_KEY',\s*'put your unique phrase here'\);\s*define\('NONCE_KEY',\s*'put your unique phrase here'\);\s*define\('AUTH_SALT',\s*'put your unique phrase here'\);\s*define\('SECURE_AUTH_SALT',\s*'put your unique phrase here'\);\s*define\('LOGGED_IN_SALT',\s*'put your unique phrase here'\);\s*define\('NONCE_SALT',\s*'put your unique phrase here'\);/sm", str_replace('$', '\\$', $salt), $config);
            }
            $config = str_replace('database_name_here', $database_name, $config);
            $config = str_replace('username_here', $username, $config);
            $config = str_replace('password_here', $password, $config);
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
            $htaccess .= 'RewriteCond %{REQUEST_URI} ^/$' . "\n";
            $htaccess .= 'RewriteRule ^(.*) /wordpress/$1 [L]' . "\n";
            $htaccess .= '</IfModule>' . "\n";
            file_put_contents('.htaccess', $htaccess);
            chmod('.htaccess', 0666);
        }
    }

    /**
     * Create Admin User
     * @see http://php.net/manual/en/book.curl.php
     */
    public function setupWordpress($weblog_title, $user_name, $admin_password, $admin_password2, $admin_email, $blog_public)
    {
        $url = 'http://' . $_SERVER["HTTP_HOST"] . '/wp-admin/install.php?step=2';
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
     * Set Permalink to Postname
     * @see http://httpd.apache.org/docs/current/howto/htaccess.html
     */
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
}

$installer = new WordpressInstaller($config);

if ($installer->hasRights() === false) {
    $step = 0;
} else {
    $step = 1;
}

if (isset($_GET['step']) === true) {

    if ($_GET['step'] == 2) {
        if (!file_exists(WP_CONFIG_SAMPLE)) {
            $installer->installWordpress($_POST['lang']);
        }
        $step = 2;
    }

    if ($_GET['step'] == 3) {
        if (file_exists(WP_CONFIG_SAMPLE) && !file_exists(WP_CONFIG)) {
            $installer->createConfig($_POST['db_name'], $_POST['db_username'], $_POST['db_password']);
        }
        if (!file_exists('./.htaccess')) {
            $installer->rewriteSubdirectory();
        }
        $step = 3;
    }

    if ($_GET['install'] == 'wp') {
        $installer->setupWordpress($_POST['weblog_title'], $_POST['user_name'], $_POST['admin_password'], $_POST['admin_password2'], $_POST['admin_email'], $_POST['blog_public']);
    }

    if ($_GET['step'] == 4) {
        if ($_GET['theme'] == 'upload' && isset($_FILES['themezip']['tmp_name']) === true) {
            if (move_uploaded_file($_FILES['themezip']['tmp_name'], './theme.zip')) {
                $installer->installTheme();
            }
        }
        $step = 4;
    }

    if ($_GET['step'] == 5) {
        if ($_GET['theme'] == 'activate' && isset($_POST['theme']) === true) {
            require_once('./wordpress/wp-load.php');
            $installer->switchTheme($_POST['theme']);
        }
        $step = 5;
    }

    if ($_GET['step'] == 6) {
        if ($_GET['plugin'] == 'install' && isset($_POST['plugins']) === true) {
            foreach ($_POST['plugins'] as $plugin) {
                $installer->installPlugin($plugin);
            }
        }
        $step = 6;
    }

    if ($_GET['step'] == 7) {
        if ($_GET['user'] == 'add' && isset($_POST['name']) === true && isset($_POST['password']) === true && isset($_POST['email']) === true && isset($_POST['role']) === true) {
            require_once('./wordpress/wp-load.php');
            $installer->addUser($_POST['name'], $_POST['password'], $_POST['email'], $_POST['role']);
        }
        if ($_GET['permalink'] == 'postname') {
            require_once('./wordpress/wp-load.php');
            $installer->setPermalinkToPostname();
        }
        $step = 7;
    }

    if ($_GET['step'] == 8) {
        $step = 8;
    }

} else {

    if (file_exists(WP_CONFIG_SAMPLE)) {
        $step = 2;
    }

    if (file_exists(WP_CONFIG)) {
        $step = 3;
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
    <style>
    </style>
</head>
<body>
<div style="text-align: center;">
    <h1>WordPress Installer</h1>
    <?php if ($step == 0): ?>
        Directory rights needed...<br>
        Change the rights and <a href="javascript:location.reload();">reload</a> this page.
    <?php elseif ($step == 1): ?>
        <h2>Language</h2>
        <form id="step1" action="./installer.php?step=2" method="post">
            <select name="lang">
                <option value="en">english</option>
                <option value="de" selected>deutsch</option>
            </select>
            <input type="submit" name="next" value="Next">
        </form>
    <?php elseif ($step == 2): ?>
        <h2>Database</h2>
        <form id="step2" action="./installer.php?step=3" method="post">
            <input type="text" placeholder="Database Name" name="db_name" value="<?= $db_name ?>">
            <input type="text" placeholder="Database User" name="db_username" value="<?= $db_username ?>">
            <input type="text" placeholder="Database Password" name="db_password" value="<?= $db_password ?>">
            <input type="submit" name="next" value="Next">
        </form>
    <?php elseif ($step == 3): ?>
        <h2>Setup</h2>
        <form id="step3" action="./installer.php?step=4&amp;install=wp" method="post">
            <input type="text" placeholder="Website Title" name="weblog_title" value="<?= $default['title'] ?>">
            <input type="text" placeholder="Admin Name" name="user_name" value="<?= $default['admin']['name'] ?>">
            <input type="password" placeholder="Admin Password" name="admin_password" value="<?= $default['admin']['password'] ?>">
            <input type="password" placeholder="Admin Password" name="admin_password2" value="<?= $default['admin']['password'] ?>">
            <input type="text" placeholder="Admin E-Mail" name="admin_email" value="<?= $default['admin']['email'] ?>">
            <input type="checkbox" name="blog_public" value="1" <?= $default['public'] == 1 ? 'checked' : '' ?>>
            <input type="submit" name="next" value="Next">
        </form>
    <?php elseif ($step == 4): ?>
        <h2>Install Theme</h2>
        <?php
        require_once('./wordpress/wp-load.php');
        require_once('./wordpress/wp-admin/includes/admin.php');
        require_once('./wordpress/wp-admin/includes/theme-install.php');
        install_themes_upload();
        ?>
        <br><br>
        <form id="step4" action="./installer.php?step=5" method="post">
            <input type="submit" name="next" value="Next">
        </form>
        <script type="application/javascript">
            jQuery(document).ready(function() {
                jQuery('.wp-upload-form').attr('action', './installer.php?step=4&theme=upload');
            });
        </script>
    <?php elseif ($step == 5): ?>
        <h2>Activate Theme</h2>
        <?php
        require_once('./wordpress/wp-load.php');
        require_once('./wordpress/wp-admin/includes/admin.php');
        ?>
        <form id="step5theme" action="./installer.php?step=5&amp;theme=activate" method="post">
            <select name="theme">
                <?php
                $themes = wp_prepare_themes_for_js();
                foreach ($themes as $theme) {
                    echo '<option value="' . $theme['id'] . '"' . ($theme['active'] ? ' selected' : '') . '>' . $theme['name'] . '</option>';
                }
                ?>
            </select>
            <input type="submit" value="Activate">
        </form>
        <br><br>
        <form id="step5" action="./installer.php?step=6" method="post">
            <input type="submit" name="next" value="Next">
        </form>
    <?php elseif ($step == 6): ?>
        <h2>Install Plugins</h2>
        <form id="step6plugins" action="./installer.php?step=6&amp;plugin=install" method="post">
            <select name="plugins[]" size="<?php echo count($plugins); ?>" multiple>
                <?php
                foreach ($plugins as $plugin) {
                    echo '<option value="' . $plugin['url'] . '" selected>' . $plugin['name'] . '</option>';
                }
                ?>
            </select>
            <br>
            <input type="submit" value="Install">
        </form>
        <br><br>
        <form id="step6" action="./installer.php?step=7" method="post">
            <input type="submit" name="next" value="Next">
        </form>
    <?php elseif ($step == 7): ?>
        <h2>Options</h2>
        <?php
        require_once('./wordpress/wp-load.php');
        require_once('./wordpress/wp-admin/includes/admin.php');
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
        <br><br>
        <form id="step7permalink" action="./installer.php?step=7&amp;permalink=postname" method="post">
            <fieldset>
                <legend align="left">Permalink</legend>
                <input type="submit" value="Postname">
            </fieldset>
        </form>
        <br><br>
        <form id="step7" action="./installer.php?step=8" method="post">
            <input type="submit" name="next" value="Next">
        </form>
    <?php else: ?>
        <form action="./installer.php" method="post">
            <input type="checkbox" name="delete" value="1" checked>
            <input type="hidden" name="ready" value="1">
            <input type="submit" name="next" value="Ready">
        </form>
    <?php endif; ?>
</div>
</body>
</html>
