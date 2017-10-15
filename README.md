avrs-php-api
============

A Sample PHP-Based API Consumer

author: Paul Hulett Paul.Hulett@cdk.com

# The any-OS, from-scratch guide:
- Install Virtual Box from https://www.virtualbox.org/wiki/Downloads
- Install Vagrant from https://www.vagrantup.com/downloads.html
- From this directory, run: vagrant up
- Once Vagrant is up and running, ssh into the vm by running: vagrant ssh
- install composer, use it to install the autoloader and any project dependencies
```bash
cd /var/www/public/
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```
- copy the setting.json example to the proper location, then edit it
```bash
cp examples/_settings.json examples/settings.json
vim examples/settings.json
# or use your editor of choice
```
- command-line execution example
> vagrant@avrsapi:/var/www/public$ php -a
>
> Interactive mode enabled
>
> php > require_once 'vendor/autoload.php';
>
> php > $example = new PaulJulio\AvrsApi\Examples\FeeCalculator();
>
> php > $example->run();

# To Do
- needs browser-based interaction added
- needs unit tests