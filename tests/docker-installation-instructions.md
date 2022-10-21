# Testing extension in your machine

## Installation instructions

These instructions will start a set of Docker containers, containing a Magento 2 store with the Facebook Business Extension, in your local machine, so you can try its capabilities before deploying to your production environment.

1. Change your etc/hosts file. It's necessary to access the store.

    1. Use this [tutorial](https://support.rackspace.com/how-to/modify-your-hosts-file/) to locate and edit it.

    2. Add this line: 127.0.0.1 local.magento

2. Open a terminal and browse to this folder.

3. Start containers: `docker-compose up -d`

4. Copy magento extension to tests_web_1 container `docker cp <directory_containing_the_repo>/. tests_web_1:/var/www/html/app/code`

5. Enter to tests_web_1 container: `docker exec -it tests_web_1 /bin/bash`

6. Install Magento inside Web Container: `/usr/local/bin/install-magento`

7. Install extension: `/usr/local/bin/install-facebook-business-extension`

## Verify installation

1. Open your browser and go to http://local.magento/admin

2. Log in using:

    * username: admin

    * password: magentorocks1

3. Go to Stores -> Facebook -> Setup and configure the connection to Facebook.

4. Go to Magento frontend: http://local.magento/

5. Open the Developer tools in your browser and select the Network tab.

6. Navigate through the store. You should see pixel event(`www.facebook.com/tr`) requests.
