<?php
namespace Wolf\Filter\Observer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
class Navigation implements ObserverInterface {
	function execute(Observer $observer) {
		if (!($menuTree = wolf_tree_load())) {
			$menuTree = $this->_getMenuTree($observer->getData('menu'));
			df_cache_save(serialize($menuTree), self::CACHE_KEY, [self::CACHE_TAG]);
		}
		df_register('wolfCategoryMenuTree', $menuTree);
	}

	// @todo code compress difference this
	protected function _getMenuTree ($menuTree) {
		$children = $menuTree->getChildren();
		$parentLevel = $menuTree->getLevel();
		$childLevel = $parentLevel === null ? 0 : $parentLevel + 1;
		$counter = 1;
		$itemPosition = 0;
		$childrenCount = $children->count();
		$result = array();
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
				$subChildren = $this->_getMenuTree($child);
				if($subChildren && !empty($subChildren)) {
					$result[$id] = array(
						'id' => $id,
						'name' => $child->getName(),
						'url' => $child->getUrl(),
						'children' => $subChildren
					);
				} else {
					$result[$id] = array(
						'id' => $id,
						'name' => $child->getName(),
						'url' => $child->getUrl()
					);
				}
			} else {
				$result[$id] = array(
					'id' => $id,
					'name' => $child->getName(),
					'url' => $child->getUrl()
				);
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
	 * @used-by \Wolf\Filter\Block\Navigation::getConfigJson()
	 * @used-by \Wolf\Filter\Controller\Index\Change::execute()
	 * @used-by \Wolf\Filter\Model\Cache\Type::__construct()
	 */
	const CACHE_TAG = 'WOLF_CATEGORY_FILTER';
}