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
  * [PHP][6] 5.2.4 or greater
  * [MySQL][7] 5.0 or greater
* [Apache HTTP Server][8]
* [cURL][9] must be installed
* [allow_url_fopen][10] must be enabled

## Recommended Browser

* [Firefox][11]
* [Chrome][12]

## Creator

**Stefan Hüsges**

:computer: [Homepage][1]

:octocat: [GitHub][2]

## Libraries

* [jQuery][13]
* [normalize.css][14]
* [Google Fonts][15]

## License

[MIT License](LICENSE)

[1]: http://www.mpcx.net
[2]: https://github.com/tronsha
[3]: https://github.com/tronsha/wp-installer/archive/master.zip
[4]: http://getcomposer.org
[5]: https://wordpress.org/about/requirements/
[6]: http://php.net/
[7]: http://www.mysql.com/
[8]: http://httpd.apache.org/
[9]: http://php.net/manual/en/book.curl.php
[10]: http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen
[11]: https://www.mozilla.org/en-US/firefox/developer/
[12]: https://www.google.com/chrome/
[13]: http://jquery.com/
[14]: http://necolas.github.io/normalize.css/
[15]: https://www.google.com/fonts
