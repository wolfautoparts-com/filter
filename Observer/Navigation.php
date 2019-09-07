<?php

namespace Wolf\Filter\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Registry;
use Magento\Customer\Setup\CustomerSetupFactory;
use \Psr\Log\LoggerInterface;


class Navigation implements ObserverInterface
{

	private $_logger;
	private $_cache;
    private $_registry;


	function __construct(
		LoggerInterface $logger,
		CacheInterface $cache,
        Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
	)
	{
		$this->_logger = $logger;
		$this->_cache = $cache;
		$this->_registry = $registry;
        $this->_customerSession = $customerSession;
        $this->_customerRepository = $customerRepository;

	}

	function execute(Observer $observer) {
		$cacheTags = [self::CACHE_TAG];
		if (false !== ($data = $this->_cache->load(self::CACHE_KEY))) {
			$menuTree = unserialize($data);
		}
		else {
			$menu = $observer->getData('menu');
			$this->_logger->debug('$menu');
			$this->_logger->debug(json_encode($menu));
			$menuTree = $this->_getMenuTree($menu);
			$this->_cache->save(serialize($menuTree), self::CACHE_KEY, $cacheTags);
		}
		// Store the menuTree on registry for later use on Block Navigation
        $this->_registry->register('wolfCategoryMenuTree', $menuTree);
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