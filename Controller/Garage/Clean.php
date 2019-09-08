<?php
namespace Wolf\Filter\Controller\Garage;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Wolf\Filter\Setup\InstallData as Schema;
class Clean extends Action {
	/**
	 * Change constructor.
	 * @param Context $context
	 * @param Registry $registry
	 * @param CustomerFactory $customerResourceFactory
	 * $param Customer $customerModel
	 * @param Session $customerSession
	 * @param LoggerInterface $logger
	 * @param CacheInterface $cache
	 * @param JsonFactory $resultJsonFactory
	 */
	function __construct(
		Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory,
		\Magento\Customer\Model\Customer $customerModel,
		\Magento\Customer\Model\Session $customerSession,
		LoggerInterface $logger,
		CacheInterface $cache,
		JsonFactory $resultJsonFactory
	) {
		$this->_registry = $registry;
		$this->_customerResourceFactory = $customerResourceFactory;
		$this->_customerModel = $customerModel;
		$this->_customerSession = $customerSession;
		$this->_logger = $logger;
		$this->_cache = $cache;
		$this->_resultJsonFactory = $resultJsonFactory;

		parent::__construct($context);
	}

	function execute() {
		$customer_id = $this->_customerSession->getCustomer()->getId();
		$customer_garage = array('cars' => []);
		$customer_garage_json = json_encode($customer_garage);
		wolf_sess_set($customer_garage_json);
		if ($customer_id) {
			$customer = $this->_customerModel->load($customer_id);
			$customerData = $customer->getDataModel();
			$customerData->setCustomAttribute(Schema::GARAGE, $customer_garage_json);
			$customer->updateData($customerData);
			$customerResource = $this->_customerResourceFactory->create();
			$customerResource->saveAttribute($customer, Schema::GARAGE);
		}
		$result = $this->_resultJsonFactory->create();

		return $result->setData(array(
			'success' => true
		));
	}
}