<?php
namespace Wolf\Filter\Observer;
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
			df_cache_save(serialize($r = $this->tree($o['menu'])), self::CACHE_KEY, [self::CACHE_TAG]);
		}
		df_register('wolfCategoryMenuTree', $r);
	}

	/**
	 * 2019-09-07
	 * @used-by execute()
	 * @used-by tree()
	 * @param $menuTree
	 * @return array
	 */
	private function tree($menuTree) {
		$children = $menuTree->getChildren();
		$parentLevel = $menuTree->getLevel();
		$childLevel = $parentLevel === null ? 0 : $parentLevel + 1;
		$counter = 1;
		$itemPosition = 0;
		$childrenCount = $children->count();
		$result = [];
		foreach ($children as $child) {
			if ( $childLevel === 0 && $child->getData( 'is_parent_active' ) === false ) {
				continue;
			}
			$child->setLevel($childLevel);
			$child->setIsFirst($counter == 1);
			$child->setIsLast($counter == $childrenCount);

			$id = $child->getId();
			$id = str_replace('category-node-', '', $id);
			$id = (int) $id;
			if($childrenCount) {
				$subChildren = $this->tree($child);
				if($subChildren && !empty($subChildren)) {
					$result[$id] = [
						'id' => $id,
						'name' => $child->getName(),
						'url' => $child->getUrl(),
						'children' => $subChildren
					];
				} else {
					$result[$id] = [
						'id' => $id,
						'name' => $child->getName(),
						'url' => $child->getUrl()
					];
				}
			} else {
				$result[$id] = [
					'id' => $id,
					'name' => $child->getName(),
					'url' => $child->getUrl()
				];
			}
			$itemPosition++;
			$counter++;
		}
		return $result;
	}

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