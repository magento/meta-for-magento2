# Facebook & Instagram for Magento

[**The official extension from Meta.**](https://commercemarketplace.adobe.com/meta-meta-for-magento2.html) Seamlessly
manage
your
Facebook & Instagram presence from one place.

## Extension Overview

Meta builds technologies that help people connect, find communities and grow businesses.

The Facebook & Instagram Extension enables users to set up Conversions API, Pixel, Catalog, and enables Adobe Commerce (
on-prem) and Magento Open Source users to create and manage a checkout-enabled Shop on Facebook & Instagram. This
extension will help you:

* **Grow your business** on Facebook & Instagram while managing orders, returns, and more from one place.
* **Reach new customers** and offer a seamless in-app shopping experience, from discovery to checkout.
* **Optimize your ads** using AI and easily promote your products and optimize your ads using powerful sales and
  marketing tools.
* **Automatically sync** eligible product offerings, inventory, promotions, and orders between your Magento system and
  Meta.
* **Gain better data insights** and understand meaningful business outcomes.

## Installation Assets

### (a) Meta Business Extension

Meta Business Extension is a popup-based solution that allows users to easily set up the Conversions API, Meta Pixel,
and Catalog, and allows Adobe Commerce (on-prem) and Magento Open Source users to create and manage a checkout-enabled
Shop on Facebook & Instagram.

### (b) Conversions API

Conversions API is designed to create a direct connection between your marketing data and the systems that help optimize
ad targeting, decrease cost per result and measure outcomes across Meta technologies.

### (c) Meta Pixel

Meta Pixel is a piece of code that you put on your website that allows you to measure the effectiveness of your
advertising by understanding the actions people take on your website.

### (d) Catalog

Catalog is a container that holds information about the items you want to advertise or sell across Facebook and
Instagram. You can create catalogs for different types of items, such as products, hotels, flights, destinations, home
listings or vehicles.

### (e) Checkout

Checkout allows customers to buy products directly from a shop on Facebook or Instagram and enables your business to run
Shops Ads.

## Usage Instructions

Complete usage guide [HERE](https://www.facebook.com/business/help/532749253576163).

### Installation

#### Composer Installation

* Get the Facebook & Instagram for Magento extension
  from [Adobe Commerce Marketplace](https://commercemarketplace.adobe.com/meta-meta-for-magento2.html). Make sure to
  choose the correct edition and store version.
* Go to your Magento root path.
* Execute command `cd /var/www/Magento` or `cd /var/www/html/Magento` based on your server Centos or Ubuntu.
* Install the Meta Business SDK for PHP with `composer require facebook/php-business-sdk`.
* Run composer command: `composer require meta/meta-for-magento2`.
* To set up the extension, run:
    * `php bin/magento setup:upgrade`
    * `php bin/magento setup:static-content:deploy`
    * `php bin/magento setup:di:compile`
    * `php bin/magento cache:clean`

#### Manual Installation

* Extract files from an archive.
* Execute command `cd /var/www/Magento/app/code` or `cd /var/www/html/Magento/app/code` based on your server Centos or
  Ubuntu.
* Move all files from the `app/code/*` directory in your extracted archive to the `app/code` directory in your Magento
  project. Your Magento directory should now have a subdirectory named `app/code/Meta/BusinessExtension`.

### Enable Extension

#### Enable Via Script

* Copy the `install-facebook-business-extension.sh` script to your Magento root folder.
* Give it execute permission with `chmod +x install-facebook-business-extension.sh`.
* Switch to Magento files owner user and run: `./install-facebook-business-extension.sh`.
* You should read `Installation finished` when the script is done.

#### Enable Manually

* Install the Facebook Business SDK for PHP (if you have not already) with `composer require facebook/php-business-sdk`.
* Run `php bin/magento module:enable Meta_BusinessExtension Meta_Catalog Meta_Conversion Meta_Promotions Meta_Sales`.
* Execute `php bin/magento setup:static-content:deploy`.
* Execute `php bin/magento setup:upgrade`.
* Execute `php bin/magento setup:di:compile`.
* Execute `php bin/magento cache:clean`.

### Verify Installation

- Upon successful installation, login to your Magento Admin panel.
- You should see a "Facebook and Instagram" Icon on the left hand side.
- Click "Overview" to link a Meta Account.

### Update Installation

#### Composer Update

* Go to your Magento root path.
* Execute command `cd /var/www/Magento` or
  `cd /var/www/html/Magento` based on your server Centos or Ubuntu.
* Run composer command: `composer update meta/meta-for-magento2`.

#### Manual Update

* Extract files from the latest archive.
* Execute command `cd /var/www/Magento/app/code` or
  `cd /var/www/html/Magento/app/code` based on your server Centos or Ubuntu.
* Replace all files in the `app/code` directory of your Magento
  project with the files from the `app/code/*` directory of your extracted archive.

## Support

Visit Facebook's [Advertiser Help Center](https://www.facebook.com/business/help/532749253576163).

## Requirements

Facebook & Instagram for Magento requires:

* Magento version `^2.4.2`.
* PHP 7.4 or greater.
* Memory limit of 1 GB or greater (2 GB or higher is preferred).

## Contributing

See the `CONTRIBUTING.md` file for how to help out.

## License

Facebook & Instagram for Magento is platform licensed.
