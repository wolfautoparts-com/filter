<?php
namespace Wolf\Filter\Controller\Garage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Wolf\Filter\Customer as WCustomer;
class Remove extends Action {
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
	)
	{
		$this->_registry = $registry;
		$this->_customerResourceFactory = $customerResourceFactory;
		$this->_customerModel = $customerModel;
		$this->_customerSession = $customerSession;
		$this->_logger = $logger;
		$this->_cache = $cache;
		$this->_resultJsonFactory = $resultJsonFactory;
		parent::__construct($context);
	}

	/**
	 *
	 */
	function execute() {

		$params = $this->getRequest()->getParams();
		$errors = [];
		$customer_garage = array('cars' => []);

		if (isset($params['uri']) && !empty($params['uri'])) {

			// @todo cleanup uri what happens to in_array if it comes with unknown or huge data?

		} else {
			array_push($errors, 'uri must be defined');
		}

		if (empty($errors)) {

			$customer_garage = WCustomer::garage();

			if (in_array($params['uri'], $customer_garage['cars'])) {

				// remove the entry from $customer_garage and save
				$customer_garage['cars'] = array_diff($customer_garage['cars'], [$params['uri']]);
				$customer_garage_json = json_encode($customer_garage);
				$customer_id = $this->_customerSession->getCustomer()->getId();
				if ($customer_id) {
					$customer = $this->_customerModel->load($customer_id);
					$customerData = $customer->getDataModel();
					$customerData->setCustomAttribute('customer_garage_json', $customer_garage_json);
					$customer->updateData($customerData);
					$customerResource = $this->_customerResourceFactory->create();
					$customerResource->saveAttribute($customer, 'customer_garage_json');
				}
				wolf_sess_set($customer_garage_json);
				WCustomer::garage($customer_garage);
			}
			else {
				array_push($errors, 'uri not in garage');
			}
		}
		$data = array('success' => true, 'params' => $params, 'customer_garage' => $customer_garage);
		if (!empty($errors)) {
			$data['errors'] = $errors;
			$data['success'] = false;
		}

		$result = $this->_resultJsonFactory->create();

		return $result->setData($data);
	}
}