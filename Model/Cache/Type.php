<?php

namespace Wolf\Filter\Model\Cache;

use \Magento\Framework\Cache\Frontend\Decorator\TagScope;
use \Magento\Framework\App\Cache\Type\FrontendPool;


class Type extends TagScope {

	const TYPE_IDENTIFIER = 'category_filter_cache';
	const CACHE_TAG = 'WOLF_CATEGORY_FILTER';

	function __construct(  FrontendPool $cacheFrontendPool ) {
		parent::__construct( $cacheFrontendPool->get( self::TYPE_IDENTIFIER ), self::CACHE_TAG );
	}
}