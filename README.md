WordPress Installer :ant:
===================

## Install

### Use Composer

If you don't have Composer yet, download it following the instructions on
http://getcomposer.org/ or just run the following command:

    curl -s http://getcomposer.org/installer | php

Then, use the `create-project` command to generate a new project:

    php composer.phar create-project tronsha/wp-installer --stability=dev

## Requirements

* [WordPress Requirements][3]
  * PHP 5.2.4 or greater
  * MySQL 5.0 or greater
  * Apache HTTP Server
* [cURL][4] must be installed
* [allow_url_fopen][5] must be enabled

## Creator

**Stefan Hüsges**

* :octocat: [GitHub][1]
* :computer: [Homepage][2]

## License

[MIT License](LICENSE)

[1]: https://github.com/tronsha
[2]: http://www.mpcx.net
[3]: https://wordpress.org/about/requirements/
[4]: http://php.net/manual/en/book.curl.php
[5]: http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen
