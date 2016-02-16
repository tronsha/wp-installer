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
    * [cURL][10] must be installed
    * [allow_url_fopen][11] must be enabled
  * [MySQL][7] 5.0 or greater
* [Apache HTTP Server][8]
  * [mod_rewrite][9] is required

## Recommended Browser

* [Firefox][12]
* [Chrome][13]

## Libraries

* [jQuery][14]
* [normalize.css][15]

## Fonts

* Playball _Copyright (c) 2011 [TypeSETit, LLC][17]_
* Orbitron _Copyright (c) 2009, [Matt McInerney][18]_
* [Ubuntu Mono][19]

## Creator

**Stefan Hüsges**

:computer: [Homepage][1]

:octocat: [GitHub][2]

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
[9]: http://httpd.apache.org/docs/2.2/mod/mod_rewrite.html
[10]: http://php.net/manual/en/book.curl.php
[11]: http://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen
[12]: https://www.mozilla.org/en-US/firefox/developer/
[13]: https://www.google.com/chrome/
[14]: http://jquery.com/
[15]: http://necolas.github.io/normalize.css/
[16]: https://www.google.com/fonts
[17]: mailto:typesetit@att.net
[18]: mailto:matt@pixelspread.com
[19]: http://font.ubuntu.com/
