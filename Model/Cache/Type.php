<?php
namespace Wolf\Filter\Model\Cache;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use Magento\Framework\App\Cache\Type\FrontendPool;
use Wolf\Filter\Observer\Navigation as Ob;
class Type extends TagScope {
	function __construct( FrontendPool $cacheFrontendPool) {
		parent::__construct($cacheFrontendPool->get('category_filter_cache'), Ob::CACHE_TAG);
	}
}