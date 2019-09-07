<?php
namespace Wolf\Filter\Controller\Garage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Setup\Exception;
use Psr\Log\LoggerInterface;
use Wolf\Filter\Observer\Navigation as Ob;
class AllCars extends Action {
	private $_maxLevel = 5;
	protected  $_registry;
	protected  $_logger;
	protected  $_cache;
	protected  $_resultJsonFactory;
	/**
	 * Change constructor.
	 * @param Context $context
	 * @param LoggerInterface $logger
	 * @param CacheInterface $cache
	 * @param StoreManagerInterface $storeManager
	 * @param CategoryFactory $categoryFactory
	 * @param CategoryRepositoryInterface $categoryRepository
	 * @param JsonFactory $resultJsonFactory
	 */
	function __construct(
		Context $context,
		\Magento\Framework\Registry $registry,
		LoggerInterface $logger,
		CacheInterface $cache,
		JsonFactory $resultJsonFactory
	)
	{
		$this->_registry = $registry;
		$this->_logger = $logger;
		$this->_cache = $cache;
		$this->_resultJsonFactory = $resultJsonFactory;
		parent::__construct($context);
	}

	function execute() {
		$tResult = $this->_registry->registry('wolfCategoryCustomerGarage');
		$menuTree = null;
		$tResult['success'] = true;
		if (!($menuTree = wolf_tree_load())) {
			$tResult = [
				'error' => Ob::CACHE_KEY . ' cache object does not exist. Open a website page to generate it'
				,'success' => false
			];
		}
		else {
			try {
				$level = 1;
				$allCars = array();
				$this->parseMenuTree($menuTree, $level, $allCars);
				$tResult['allCars'] = $allCars;
			}
			catch(Exception $e) {
				$tResult['success'] = false;
				$tResult['error'] = $e->getMessage();
			}
		}
		$result = $this->_resultJsonFactory->create();
		return $result->setData($tResult);
	}

	function parseMenuTree($menuTree, &$level, &$allCars) {
		foreach ($menuTree as $item) {
			if($level == $this->_maxLevel) {
				array_push($allCars, $item['url']);
			}
			if(isset($item['children']) && is_array($item['children']) && !empty($item['children'])) {
				$level++;
				$this->parseMenuTree($item['children'], $level, $allCars);
				$level--;
			}
		}
	}
}