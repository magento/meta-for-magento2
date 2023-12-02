
# Meta Business Extension For Magento2

## Facebook Connects Businesses with People

Marketing on Facebook helps your business build lasting relationships with people, find new customers and increase sales for your online store. With this Facebook ad extension, we make it easy to reach the people who matter to your business and track the results of your advertising across devices. This extension will help you:

### Reach the right people
Set up the Meta pixel to find new customers, optimize your ads for people likely to buy and reach people with relevant ads on Facebook after they've visited your website.

### Show them the right products
Connect your product catalog to Facebook to use dynamic ads. Reach shoppers when they're on Facebook with ads for the products they viewed on your website.

### Measure the results of your ads
When you have the Meta pixel set up, you can use Facebook ads reporting to understand the sales and revenue that resulted from your ads.
Many online retailers have found success using the Meta pixel to track the performance of their ads and run dynamic ads:

“The ability to measure sales was the first sign that our business would be a success. Our first day of breaking 100-plus sales always sticks out. Point blank, our marketing plan is Facebook, Facebook, and more Facebook... Facebook is 100% the backbone of our customer acquisition efforts and it's been made even better with the improved Facebook pixel” — Ali Najafian, co-founder, Trendy Butler

“I'm thrilled with the results we've seen since launching dynamic ads. We saw a rise in conversions almost immediately after launch and have been able to scale the program at an impressive pace over the past 6 months. These ads have proven to be a key component of our marketing efforts” — Megan Lang, Digital Marketing Manager, Food52

“With dynamic ads, Target has been able to easily engage consumers with highly relevant creative. The early results have exceeded expectations. Performance has been especially strong on mobile devices — an important and fast-growing area for Target — where we're seeing two times the conversion rate” — Kristi Argyilan, Senior Vice President, Media and Guest Engagement at Target

## What's included?

### (a) Pixel installer
Installing the Meta pixel allows you to access the features below:

Conversion tracking: See how successful your ad is by seeing what happened as a direct result of your ad (including conversions and sales)

Optimization: Show your ads to people most likely to take a specific action after clicking on them, like adding an item to their cart or making a purchase

Remarketing: When people visit your website, reach them again and remind them of your business with a Facebook ad

### (b) Product catalog integration
Importing your product catalog to Facebook allows you to use dynamic ads. Dynamic ads look identical to other link ads or carousel-format ads that are available on Facebook. However, instead of individually creating an ad for each of your products, Facebook creates the ads for you and personalizes them for each of your customers.

Scale: Use dynamic ads to promote all your products without needing to create individual ads for each item

Highly relevant: Show people ads for products they're interested in to increase the likelihood of a purchase

Always-on: Set up your campaigns once and continually reach people with the right product at the right time

Cross-device: Reach people with ads on any device they use, regardless of where they first see your products


## Usage Instructions

Meta Business Extension - Installation steps

INSTALL META BUSINESS EXTENSION FROM ZIP FILE ON YOUR DEV INSTANCE. TEST THAT THE EXTENSION
WAS INSTALLED CORRECTLY BEFORE SHIPPING THE CODE TO PRODUCTION

Before installing, verify your Magento cron job is up and running, read more about it on [this](https://devdocs.magento.com/guides/v2.3/config-guide/cli/config-cli-subcommands-cron.html) page.

Login to your server instance.

### INSTALLATION

#### Magento Marketplace Installation
You can download and install our extension in [Magento marketplace](https://marketplace.magento.com/facebook-facebook-for-magento2.html) if you have a marketplace account.
#### Composer Installation
* Go to your magento root path
* Execute command `cd /var/www/Magento` or
 `cd /var/www/html/Magento` based on your server Centos or Ubuntu.
* run composer command: `composer require meta/meta-for-magento2 -W`
- To enable modules execute `php bin/magento module:enable Meta_BusinessExtension Meta_Catalog Meta_Conversion Meta_Promotions Meta_Sales`
- Execute `php bin/magento setup:upgrade`
- Optional `php bin/magento setup:static-content:deploy`
- Execute `php bin/magento setup:di:compile`
- Execute `php bin/magento cache:clean`

#### Manual Installation
* extract files from an archive.
* Execute command `cd /var/www/Magento/app/code` or
 `cd /var/www/html/Magento/app/code` based on your server Centos or Ubuntu.
* Move all files from the app/code/* directory in your extracted archive to the app/code directory in your Magento project. Your Magento directory should now have a subdirectory named app/code/Meta/BusinessExtension.


##### ENABLE EXTENSION
* Make sure you have correct read/write permissions on your Magento root directory.
    Read about them [here](https://magento.stackexchange.com/questions/91870/magento-2-folder-file-permissions).
* Move to magento root folder by executing command `cd ../../`

######  Enable Extension By Running Script
You can install the extension with a bash script.
- Copy the install-facebook-business-extension.sh script to your Magento root folder.
- Give it execute permission with `chmod +x install-facebook-business-extension.sh` (you may have to log in as root user to do it).
- Switch to Magento files owner user and run: `./install-facebook-business-extension.sh`.
- You should read `Installation finished` when the script is done.
######  Enable Extension By Running Commands Manually
Execute the following commands to manually install Meta Business Extension.
- Install the Facebook Business SDK for PHP: `composer require facebook/php-business-sdk`. This dependency is used by the extension.
- You will see a message similar to: `Installing facebook/php-business-sdk (8.0.0): Downloading (100%)`
- Execute `php bin/magento module:status`
- You should see Meta_BusinessExtension in the list of disabled modules.
- To enable modules execute `php bin/magento module:enable Meta_BusinessExtension Meta_Catalog Meta_Conversion Meta_Promotions Meta_Sales`
- Execute `php bin/magento setup:upgrade`
- Optional `php bin/magento setup:static-content:deploy`
- Execute `php bin/magento setup:di:compile`
- Execute `php bin/magento cache:clean`
### Verify Installation
- Upon successful installation, login to your Magento Admin panel.
- You should see a "Facebook & Instagram" Icon on the left hand side.
- Click "Overview" to link a Meta Account.

## Need help?

Visit Facebook's [Advertiser Help Center](https://www.facebook.com/business/help/532749253576163).

## Requirements

Meta Business Extension For Magento 2 requires
* Magento version 2.4.2-2.4.6
* PHP 7.4 or greater
* Memory limit of 1 GB or greater (2 GB or higher is preferred)

## Contributing

See the CONTRIBUTING file for how to help out.

## License

Meta Business Extension For Magento2 is Platform-licensed.
