<?php
namespace Wolf\Filter\Controller\Garage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Wolf\Filter\Customer as WCustomer;
class Index extends Action {
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
	) {
		$this->_registry = $registry;
		$this->_logger = $logger;
		$this->_cache = $cache;
		$this->_resultJsonFactory = $resultJsonFactory;
		parent::__construct($context);
	}

	function execute() {
		$result = $this->_resultJsonFactory->create();
		return $result->setData(WCustomer::garage());
	}
}