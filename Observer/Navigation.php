<?php
namespace Wolf\Filter\Observer;
use Magento\Framework\Data\Tree\Node as N;
use Magento\Framework\Data\Tree\Node\Collection as NC;
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
		return [$id, $this->r($id, $n, $this->node($n))];
	});}

	/**
	 * 2019-09-07
	 * @used-by node()
	 * @param int $id
	 * @param N $n
	 * @param N[] $children
	 * @return array(string => mixed)
	 */
	private function r($id, N $n, array $children) {return df_clean([
		'children' => $children, 'id' => $id, 'name' => $n->getName(), 'url' => $n->getUrl()
	]);}

	/**
	 * 2019-09-06
	 * @used-by execute()
	 * @used-by wolf_tree_load()
	 */
	const CACHE_KEY = 'category_filter_tree';
	/**
	 * 2019-09-07
	 * @used-by execute()
	 * @used-by \Wolf\Filter\Controller\Index\Change::execute()
	 * @used-by \Wolf\Filter\Model\Cache\Type::__construct()
	 */
	const CACHE_TAG = 'WOLF_CATEGORY_FILTER';
}