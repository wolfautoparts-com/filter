<?php
namespace Wolf\Filter;
use Magento\Framework\App\Cache\Type\FrontendPool as Pool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use Wolf\Filter\Observer\TopMenuGetHTMLBefore as Ob;
// 2019-09-09
class CacheType extends TagScope {
	/**
	 * 2019-09-09
	 * @override
	 * @see \Magento\Framework\Cache\Frontend\Decorator\TagScope::__construct()
	 * @param Pool $pool
	 */
	function __construct(Pool $pool) {parent::__construct($pool->get('category_filter_cache'), Ob::CACHE_TAG);}
}