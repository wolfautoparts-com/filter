<?php
namespace Wolf\Filter\Observer;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer as Ob;
use Magento\Framework\Event\ObserverInterface;
// 2019-09-08
class ControllerActionPredispatch implements ObserverInterface {
	/**
	 * 2019-09-08
	 * @override
	 * @see ObserverInterface::execute()
	 * @used-by \Magento\Framework\Event\Invoker\InvokerDefault::_callObserverMethod()
	 * @param Ob $o
	 */
	function execute(Ob $o) {
		$sess = df_customer_session(); /** @var Session $sess */
		$uri = strtok($_SERVER['REQUEST_URI'], '?');
		$garageUri = '';
		$uri_tmp = ltrim($uri, '/');
		$config = [];
		$config['params'] = explode('/', $uri_tmp);
		$paramsString = '';
		$complete_car_entry = false;
		foreach ($config['params'] as $key => &$p) {
			$p = ['id' => null, 'name' => $this->sanitize($p), 'value' => df_trim_text_right($p, '.html')];
			$paramsString .= $p['name'] . ' ';
			if (5 > $key) {
				$garageUri .= '/' . $p['value'];
			}
		}
		$garageUri .= '.html';
		if ($config['params'][0]['value'] == 'audi' || $config['params'][0]['value']  == 'volkswagen' || $config['params'][0]['value']  == 'bmw') {
			if (dfa($_COOKIE, 'car_selected') && 5 <= count($config['params'])) {
				$complete_car_entry = true;
			}
		}
		$paramsString = rtrim($paramsString);
		$paramsHash = sha1($paramsString);
		$customer_id = $sess->getCustomer()->getId();
		$customer_garage = array('cars' => []);
		if ($customer_id) {
			$customer = df_customer($customer_id);
			$customerData = $customer->getDataModel();
			$customer_garage_json = $customerData->getCustomAttribute('customer_garage_json');
			if ($customer_garage_json) {
				$customer_garage_json = $customer_garage_json->getValue();
			}
			if (!(!$customer_garage_json || $customer_garage_json == '{}')) {
				$customer_garage = json_decode($customer_garage_json, true);
			}
		}
		// combine with elements existing in session
		$customer_garage_json_session = $sess->getCustomerGarageJson();
		$customer_garage_json_session_used = false;
		if (!$customer_garage_json_session || $customer_garage_json_session == '{}') {
			$customer_garage_session = array('cars' => []);
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
				df_customer_resource()->saveAttribute($customer, 'customer_garage_json');
			}
			$sess->setCustomerGarageJson($customer_garage_json);
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
		// 2019-09-08 «Remove a cookie»: https://stackoverflow.com/a/686166
		setcookie('car_selected', '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
	}

	/**
	 * 2019-09-08
	 * @used-by execute()
	 * @param string $s
	 * @return bool|null|string|string[]
	 */
	private function sanitize($s) {return ucwords(preg_replace('/\/|-/', ' ', df_trim_text_right($s, '.html')));}
}