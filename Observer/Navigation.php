<?php
namespace Wolf\Filter\Observer;
use Magento\Framework\Data\Tree\Node as N;
use Magento\Framework\Event\Observer as Ob;
use Magento\Framework\Event\ObserverInterface;
// 2019-09-07
class Navigation implements ObserverInterface {
	/**
	 * 2019-09-07
	 * @override
	 * @see ObserverInterface::execute()
	 * @used-by \Magento\Framework\Event\Invoker\InvokerDefault::_callObserverMethod()
	 * @param Ob $o
	 */
	function execute(Ob $o) {
		if (!($r = wolf_tree_load())) {
			df_cache_save(serialize($r = $this->node($o['menu'])), self::CACHE_KEY, [self::CACHE_TAG]);
		}
	}

	/**
	 * 2019-09-07
	 * @used-by execute()
	 * @used-by node()
	 * @param N $node
	 * @return array(int => array(string => mixed))
	 */
	private function node(N $node) {return df_map_r($node->getChildren(), function(N $n) {
		$id = (int)df_trim_text_left($n->getId(), 'category-node-');
		return [$id, df_clean([
			'children' => $this->node($n), 'id' => $id, 'name' => $n->getName(), 'url' => $n->getUrl()
		])];
	});}

	/**
	 * 2019-09-06
	 * @used-by execute()
	 * @used-by wolf_tree_load()
	 */
	const CACHE_KEY = 'category_filter_tree';
	/**
	 * 2019-09-07
	 * @used-by execute()
	 * @used-by \Wolf\Filter\Block\Topmenu::getHtml()
	 * @used-by \Wolf\Filter\CacheType::__construct()
	 * @used-by \Wolf\Filter\Controller\Index\Change::execute()
	 */
	const CACHE_TAG = 'WOLF_CATEGORY_FILTER';
}