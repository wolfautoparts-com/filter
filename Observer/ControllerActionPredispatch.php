<?php
namespace Wolf\Filter\Observer;
use Magento\Customer\Model\Customer as C;
use Magento\Framework\Event\Observer as Ob;
use Magento\Framework\Event\ObserverInterface;
use Wolf\Filter\Customer as WCustomer;
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
		$uri = strtok($_SERVER['REQUEST_URI'], '?');
		$garageUri = '';
		$uri_tmp = ltrim($uri, '/');
		$config = ['params' => explode('/', $uri_tmp)];
		$paramsString = '';
		foreach ($config['params'] as $key => &$p) {
			$p = ['id' => null, 'name' => $this->sanitize($p), 'value' => df_trim_text_right($p, '.html')];
			$paramsString .= $p['name'] . ' ';
			if (5 > $key) {
				$garageUri .= '/' . $p['value'];
			}
		}
		WCustomer::hash(sha1(rtrim($paramsString)));
		$garageUri .= '.html';
		$isComplete = 
			dfa($_COOKIE, 'car_selected')
			&& 5 <= count($config['params'])
			&& in_array($config['params'][0]['value'], ['audi', 'bmw', 'volkswagen'])
		; /** @var bool $isComplete */
		$c = df_customer(); /** @var C|false $c */
		$customer_garage = array('cars' => []);
		if ($c) {
			$customerData = $c->getDataModel();
			$customer_garage_json = $customerData->getCustomAttribute('customer_garage_json');
			if ($customer_garage_json) {
				$customer_garage_json = $customer_garage_json->getValue();
			}
			if (!(!$customer_garage_json || $customer_garage_json == '{}')) {
				$customer_garage = json_decode($customer_garage_json, true);
			}
		}
		// combine with elements existing in session
		$customer_garage_json_session = wolf_sess_get();
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
		if ($isComplete && !in_array($garageUri, $customer_garage['cars'])) {
			array_push($customer_garage['cars'], $garageUri);
			$complete_car_entry_added = true;
		}
		if ($customer_garage_json_session_used || $complete_car_entry_added) {
			$customer_garage_json = json_encode($customer_garage);
			if ($c) {
				$customerData = $c->getDataModel();
				$customerData->setCustomAttribute('customer_garage_json', $customer_garage_json);
				$c->updateData($customerData);
				df_customer_resource()->saveAttribute($c, 'customer_garage_json');
			}
			wolf_sess_set($customer_garage_json);
		}
		sort($customer_garage['cars']);
		WCustomer::garage($customer_garage);
		df_register('wolfCategoryParams', $config['params']);
		WCustomer::uri(!$isComplete ? null : $garageUri);
		WCustomer::uriName(!$isComplete ? null : $this->sanitize($garageUri));
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