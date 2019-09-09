<?php
namespace Wolf\Filter;
use Magento\Framework\App\Cache\Type\FrontendPool as Pool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;
// 2019-09-09
class CacheType extends TagScope {
	/**
	 * 2019-09-09
	 * @override
	 * @see \Magento\Framework\Cache\Frontend\Decorator\TagScope::__construct()
	 * @param Pool $pool
	 */
	function __construct(Pool $pool) {parent::__construct($pool->get('category_filter_cache'), self::TAG);}

	/**
	 * 2019-09-07
	 * @used-by __construct()
	 * @used-by \Wolf\Filter\Block\Topmenu::getHtml()
	 * @used-by \Wolf\Filter\Controller\Index\Change::execute()
	 */
	const TAG = 'WOLF_CATEGORY_FILTER';
}