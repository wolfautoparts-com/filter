A custom product filtering module for [wolfautoparts.com](https://wolfautoparts.com) (Magento 2)  

## How to install
```
bin/magento maintenance:enable
rm -rf composer.lock
composer clear-cache
composer require wolfautoparts.com/filter:*
/usr/share/stratus/cli cache.all.clear
bin/magento setup:upgrade
rm -rf var/di var/generation generated/code && bin/magento setup:di:compile
bin/magento cache:clean
rm -rf pub/static/*
bin/magento setup:static-content:deploy \
	--area adminhtml \
	--theme Magento/backend \
	-f en_US
bin/magento setup:static-content:deploy \
	--area frontend \
	--theme one80solution/wolfautoparts \
	-f en_US
bin/magento maintenance:disable
bin/magento cache:enable
/usr/share/stratus/cli cache.all.clear 
/usr/share/stratus/cli autoscaling.reinit
```

## How to upgrade
```
bin/magento maintenance:enable
rm -rf composer.lock
composer remove wolfautoparts.com/filter
composer clear-cache
composer require wolfautoparts.com/filter:*
/usr/share/stratus/cli cache.all.clear 
bin/magento setup:upgrade
rm -rf var/di var/generation generated/code && bin/magento setup:di:compile
bin/magento cache:clean
rm -rf pub/static/*
bin/magento setup:static-content:deploy \
	--area adminhtml \
	--theme Magento/backend \
	-f en_US
bin/magento setup:static-content:deploy \
	--area frontend \
	--theme one80solution/wolfautoparts \
	-f en_US
bin/magento maintenance:disable
bin/magento cache:enable
/usr/share/stratus/cli cache.all.clear 
/usr/share/stratus/cli autoscaling.reinit
```

If you have problems with these commands, please check the [detailed instruction](https://mage2.pro/t/263).