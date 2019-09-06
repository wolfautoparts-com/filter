<?php
namespace Wolf\Filter\Controller\Index;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Wolf\Filter\Observer\Navigation as Ob;
// 2019-09-06
class Change extends Action {
	/**      
	 * 2019-09-06
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
		LoggerInterface $logger,
		CacheInterface $cache,
		StoreManagerInterface $storeManager,
		CategoryFactory $categoryFactory,
		CategoryRepositoryInterface $categoryRepository,
		JsonFactory $resultJsonFactory
	) {
    	$this->_logger = $logger;
	    $this->_cache = $cache;
		$this->_storeManager = $storeManager;
		$this->_categoryInstance = $categoryFactory->create();
		$this->_categoryRepository = $categoryRepository;
		$this->_resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);    
    }

    /**
	 * 2019-09-06 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * «Decrease TTFB (time to first byte) for uncached category pages to 5 seconds»:
	 * https://www.upwork.com/ab/f/contracts/22684975
	 */
	function execute() {
		$catId = (int)df_request('selectedValue'); /** @var int $catId «3613» */
		/** @var string $cacheId */
		if (false !== ($data = $this->_cache->load($cacheId = "category_filter_$catId"))) {
			$categoryArray = unserialize($data);
		} 
		else {
			$levels = (int)df_request('levels'); /** @var int $levels «5» */
			$dataId = (int)df_request('dataId');/** @var int $dataId «1» */
			$cacheTags = ['WOLF_CATEGORY_FILTER'];
			if (false !== ($data = $this->_cache->load(Ob::CACHE))) {
				$menuTree = unserialize($data);
			} 
			else {
				$menuTree = [];
			}
			$levelValues = [];
			$bTree = $menuTree;
			for ($i = 0; $i < $levels; $i++) {
				$levelValues[$i] = (int)$this->getRequest()->getParam('level_' . $i . '_value');
				if ($levelValues[$i] > 0) {
					if (isset($bTree[$levelValues[$i]])) {
						if (isset($bTree[$levelValues[$i]]['children'])) {
							$bTree = $bTree[$levelValues[$i]]['children'];
						}
					}
				}
			}
			$categoryArray = [];
			foreach ($bTree as $menuEntry) {
				$tEntry = array(
					'id'   => $menuEntry['id'],
					'name' => $menuEntry['name'],
					'url'  => $menuEntry['url'],
				);
				array_push($categoryArray, $tEntry);
			}
			if ($dataId == 1) {// For changing sort(desc) order of Year
				usort($categoryArray, function ($first, $second) {
				   return strtolower($first['name']) < strtolower($second['name']);
			    });
			} 
			else { // For rest dropdown
				usort($categoryArray, function ($first, $second) {
				   return strtolower($first['name']) > strtolower($second['name']);
			    });
			}
			$this->_cache->save(serialize($categoryArray), $cacheId, $cacheTags);
		}
		$result = $this->_resultJsonFactory->create();
		return $result->setData($categoryArray);
	}
}