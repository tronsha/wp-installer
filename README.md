WordPress Installer :ant:
===================

## Install

### Use Composer

If you don't have Composer yet, download it following the instructions on [getcomposer.org][4]
or just run the following command:

    curl -s http://getcomposer.org/installer | php

Then, use the `create-project` command to generate a new project:

    php composer.phar create-project tronsha/wp-installer --stability=dev
    
### Manually

[Download ZIP][3]

## Requirements

* [WordPress Requirements][5]
  * PHP 5.2.4 or greater
  * MySQL 5.0 or greater
  * Apache HTTP Server
* [cURL][6] must be installed
* [allow_url_fopen][7] must be enabled

## Creator

**Stefan H&uuml;sges**

:computer: [Homepage][1]

:octocat: [GitHub][2]

## License

[MIT License](LICENSE)

[1]: http://www.mpcx.net
[2]: https://github.com/tronsha
[3]: https://github.com/tronsha/wp-installer/archive/master.zip
[4]: http://getcomposer.org
[5]: https://wordpress.org/about/requirements/
[6]: http://php.net/manual/en/book.curl.php
[7]: http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen

