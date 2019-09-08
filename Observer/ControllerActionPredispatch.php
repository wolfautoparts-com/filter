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
		/**
		 * 2019-09-08
		 * 1) @uses \Magento\Framework\App\Request\Http::getOriginalPathInfo() removes the `?...` part.
		 * 2) ['audi', '2019', 'a4', 'quattro-sedan', '2-0l-l4-turbo.html']
		 * @var string[] $pathA
		 */
		$pathA = explode('/', ltrim(df_strip_ext(df_request_o()->getOriginalPathInfo()), '/'));
		$params = df_map($pathA, function($v) {return [
			'id' => null, 'name' => $this->sanitize($v), 'value' => $v
		];});
		$config = ['params' => $params];
		$categoryPath = '/' . df_cc_path(array_slice($pathA, 0, 5)) . '.html'; /** @var string $categoryPath */
		$isComplete = 
			dfa($_COOKIE, 'car_selected')
			&& 5 <= count($params)
			&& in_array($params[0]['value'], ['audi', 'bmw', 'volkswagen'])
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
		$customer_garage_session =
			!$customer_garage_json_session || $customer_garage_json_session == '{}'
			? ['cars' => []]
			: df_json_decode($customer_garage_json_session)
		;
		foreach ($customer_garage_session['cars'] as $car) {
			if (!in_array($car, $customer_garage['cars'])) {
				array_push($customer_garage['cars'], $car);
				$customer_garage_json_session_used = true;
			}
		}
		$complete_car_entry_added = false;
		if ($isComplete && !in_array($categoryPath, $customer_garage['cars'])) {
			array_push($customer_garage['cars'], $categoryPath);
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
		WCustomer::params($config['params']);
		WCustomer::categoryPath(!$isComplete ? null : $categoryPath);
		WCustomer::uriName(!$isComplete ? null : $this->sanitize($categoryPath));
		// 2019-09-08 «Remove a cookie»: https://stackoverflow.com/a/686166
		setcookie('car_selected', '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
	}

	/**
	 * 2019-09-08
	 * @used-by execute()
	 * @param string $s
	 * @return bool|null|string|string[]
	 */
	private function sanitize($s) {return ucwords(preg_replace('/\/|-/', ' ', df_strip_ext($s)));}
}