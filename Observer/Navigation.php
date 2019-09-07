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
	 * @used-by tree()
	 * @param N $node
	 * @return array
	 */
	private function node(N $node) {
		$children = $node->getChildren(); /** @var NC $children */
		$parentLevel = $node->getLevel(); /** @var int|null $parentLevel */
		$childLevel = $parentLevel === null ? 0 : $parentLevel + 1; /** @var int $childLevel */
		$counter = 1;
		$itemPosition = 0;
		$childrenCount = $children->count();
		$r = [];
		foreach ($children as $child) {
			if ($childLevel === 0 && $child->getData('is_parent_active') === false) {
				continue;
			}
			$child->setLevel($childLevel);
			$child->setIsFirst($counter == 1);
			$child->setIsLast($counter == $childrenCount);
			$id = (int)str_replace('category-node-', '', $child->getId());
			$r[$id] = $this->r($id, $child, !$childrenCount ? null : $this->node($child));
			$itemPosition++;
			$counter++;
		}
		return $r;
	}

	/**
	 * 2019-09-07
	 * @used-by node()
	 * @param int $id
	 * @param N $node
	 * @param mixed|null $children [optional]
	 * @return array(string => mixed)
	 */
	private function r($id, N $node, $children = null) {return df_clean([
		'children' => $children, 'id' => $id, 'name' => $node->getName(), 'url' => $node->getUrl()
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