
## Installation

You can install the package via composer:

```bash
composer require bellesoft/portico-iptv
```

The package will automatically register itself.


You can publish the config-file with:

```bash
php artisan vendor:publish --provider="Bellesoft\PorticoIptv\IPTVServiceProvider" --tag="iptv-config"
```