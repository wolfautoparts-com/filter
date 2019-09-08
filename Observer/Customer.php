<?php
namespace Wolf\Filter\Observer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
class Customer implements ObserverInterface {
	function __construct(
		CacheInterface $cache,
		Registry $registry,
		\Magento\Customer\Model\ResourceModel\CustomerFactory $customerResourceFactory,
		\Magento\Customer\Model\Customer $customerModel,
		\Magento\Customer\Model\Session $customerSession
	)
	{
		$this->_cache = $cache;
		$this->_registry = $registry;
		$this->_customerResourceFactory = $customerResourceFactory;
		$this->_customerModel = $customerModel;
		$this->_customerSession = $customerSession;

	}

	function execute(Observer $observer)
	{
		$isCarSelected = "not_selected";
		if (isset($_COOKIE['car_selected' ])) {
			$isCarSelected = $_COOKIE['car_selected' ];
		}
		$uri = strtok($_SERVER['REQUEST_URI'], '?');
		$garageUri = '';
		$uri_tmp = ltrim($uri, '/');
		$config = array();
		$config['params'] = explode('/', $uri_tmp);
		$paramsString = '';
		$complete_car_entry = false;
		foreach ($config['params'] as $key => &$param) {
			$param = array(
				'id' => null,
				'name' => $this->sanitize($param),
				'value' => df_trim_text_right($param, '.html')
			);
			$paramsString .= $param['name'] . ' ';
			if ($key < 5) {
				$garageUri .= '/' . $param['value'];
			}

			// @todo fix motor lt missing dot
		}

		$garageUri .= '.html';

		if ($config['params'][0]['value'] == 'audi' || $config['params'][0]['value']  == 'volkswagen' || $config['params'][0]['value']  == 'bmw') {
			if (count($config['params']) >= 5 && $isCarSelected == "selected") {
				$complete_car_entry = true;
			}
		}
		$paramsString = rtrim($paramsString);
		$paramsHash = sha1($paramsString);
//        $this->_customerSession->start();
		$customer_id = $this->_customerSession->getCustomer()->getId();
		$customer_garage = array('cars' => array());
		if ($customer_id) {
			$customer = $this->_customerModel->load($customer_id);
			$customerData = $customer->getDataModel();
//            $customer_garage_json = $customer->getCustomAttribute('customer_garage_json');
			$customer_garage_json = $customerData->getCustomAttribute('customer_garage_json');
			if ($customer_garage_json) {
				$customer_garage_json = $customer_garage_json->getValue();
			}
			if (!(!$customer_garage_json || $customer_garage_json == '{}')) {
				$customer_garage = json_decode($customer_garage_json, true);
			}
		}
		// combine with elements existing in session
		$customer_garage_json_session = $this->_customerSession->getCustomerGarageJson();
		$customer_garage_json_session_used = false;
		if (!$customer_garage_json_session || $customer_garage_json_session == '{}') {
			$customer_garage_session = array('cars' => array());
		} else {
			$customer_garage_session = json_decode($customer_garage_json_session, true);
		}
		foreach ($customer_garage_session['cars'] as $car) {
			if (!in_array($car, $customer_garage['cars'])) {
				array_push($customer_garage['cars'], $car);
				$customer_garage_json_session_used = true;
			}
		}
		$complete_car_entry_added = false;
		if ($complete_car_entry && !in_array($garageUri, $customer_garage['cars'])) {
			array_push($customer_garage['cars'], $garageUri);
			$complete_car_entry_added = true;
		}
		if ($customer_garage_json_session_used || $complete_car_entry_added) {
			$customer_garage_json = json_encode($customer_garage);
			if ($customer_id) {
				$customerData = $customer->getDataModel();
				$customerData->setCustomAttribute('customer_garage_json', $customer_garage_json);
				$customer->updateData($customerData);
				$customerResource = $this->_customerResourceFactory->create();
				$customerResource->saveAttribute($customer, 'customer_garage_json');
			}
			$this->_customerSession->setCustomerGarageJson($customer_garage_json);
		}
		// sort
		sort($customer_garage['cars']);
		// if there's elements on garage
		$customerGarageIsEmpty = true;
		if (!empty($customer_garage['cars'])) {
			$customerGarageIsEmpty = false;
		}
		// register results for further usage on controllers and views
		df_unregister('wolfCustomerGarageIsEmpty');
		df_register('wolfCustomerGarageIsEmpty', $customerGarageIsEmpty);
		if ($complete_car_entry) {
			df_unregister('wolfCustomerGarageUri');
			df_register('wolfCustomerGarageUri', $garageUri);
			df_unregister('wolfCustomerGarageUriName');
			df_register('wolfCustomerGarageUriName', $this->sanitize($garageUri));
		}
		else {
			df_unregister('wolfCustomerGarageUri');
			df_register('wolfCustomerGarageUri', null);
			df_unregister('wolfCustomerGarageUriName');
			df_register('wolfCustomerGarageUriName', null);
		}
		df_unregister('wolfCategoryCustomerGarage');
		df_register('wolfCategoryCustomerGarage', $customer_garage);
		df_unregister('wolfCategoryParams');
		df_register('wolfCategoryParams', $config['params']);
		df_unregister('wolfCategoryParamsString');
		df_register('wolfCategoryParamsString', $paramsString);
		df_unregister('wolfCategoryParamsHash');
		df_register('wolfCategoryParamsHash', $paramsHash);
		setcookie('car_selected', 'not_selected', time() +2592000, '/', $_SERVER['HTTP_HOST']);
	}

	/**
	 * 2019-09-08
	 * @used-by execute()
	 * @param string $s
	 * @return bool|null|string|string[]
	 */
	private function sanitize($s) {return ucwords(preg_replace('/\/|-/', ' ', df_trim_text_right($s, '.html')));}
}