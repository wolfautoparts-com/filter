<?php
namespace Wolf\Filter\Observer;
use Magento\Customer\Model\Customer as C;
use Magento\Framework\Event\Observer as Ob;
use Magento\Framework\Event\ObserverInterface;
use Wolf\Filter\Customer as WCustomer;
use Wolf\Filter\Setup\InstallData as Schema;
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
		/**
		 * 2019-09-08
		 *	[
		 *		{"id": null, "name": "Audi", "value": "audi"},
		 *		{"id": null, "name": "2019", "value": "2019"},
		 *		{"id": null, "name": "A4", "value": "a4"},
		 *		{"id": null, "name": "Quattro Sedan", "value": "quattro-sedan"},
		 *		{"id": null, "name": "2 0l L4 Turbo", "value": "2-0l-l4-turbo"}
		 *	]
		 * @var array(array(string => string)) $params
		 */
		$params = df_map($pathA, function($v) {return ['id' => null, 'name' => $this->name($v), 'value' => $v];});
		WCustomer::params($params);
		$categoryPath = '/' . df_cc_path(array_slice($pathA, 0, 5)) . '.html'; /** @var string $categoryPath */
		$garage = ['cars' => []];
		/** @var C|false $c */
		if ($c = df_customer()) {
			$customerData = $c->getDataModel();
			$garageJ = $customerData->getCustomAttribute(Schema::GARAGE);
			if ($garageJ) {
				$garageJ = $garageJ->getValue();
			}
			if (!(!$garageJ || $garageJ == '{}')) {
				$garage = json_decode($garageJ, true);
			}
		}
		// combine with elements existing in session
		$garageJ_session = wolf_sess_get();
		$garageJ_session_used = false;
		$garage_session =
			!$garageJ_session || '{}' === $garageJ_session
			? ['cars' => []]
			: df_json_decode($garageJ_session)
		;
		foreach ($garage_session['cars'] as $car) {
			if (!in_array($car, $garage['cars'])) {
				array_push($garage['cars'], $car);
				$garageJ_session_used = true;
			}
		}
		$complete_car_entry_added = false;
		$isComplete =
			dfa($_COOKIE, 'car_selected')
			&& 5 <= count($params)
			&& in_array($params[0]['value'], ['audi', 'bmw', 'volkswagen'])
		; /** @var bool $isComplete */
		if ($isComplete && !in_array($categoryPath, $garage['cars'])) {
			array_push($garage['cars'], $categoryPath);
			$complete_car_entry_added = true;
		}
		if ($garageJ_session_used || $complete_car_entry_added) {
			$garageJ = df_json_encode($garage);
			if ($c) {
				$customerData = $c->getDataModel();
				$customerData->setCustomAttribute(Schema::GARAGE, $garageJ);
				$c->updateData($customerData);
				df_customer_resource()->saveAttribute($c, Schema::GARAGE);
			}
			wolf_sess_set($garageJ);
		}
		sort($garage['cars']);
		WCustomer::garage($garage);
		WCustomer::categoryPath(!$isComplete ? null : $categoryPath);
		WCustomer::uriName(!$isComplete ? null : $this->name($categoryPath));
		// 2019-09-08 «Remove a cookie»: https://stackoverflow.com/a/686166
		setcookie('car_selected', '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
	}

	/**
	 * 2019-09-08
	 * @used-by execute()
	 * @param string $s
	 * @return bool|null|string|string[]
	 */
	private function name($s) {return ucwords(preg_replace('/\/|-/', ' ', df_strip_ext($s)));}
}