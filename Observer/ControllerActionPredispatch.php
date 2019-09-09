<?php
namespace Wolf\Filter\Observer;
use Magento\Framework\Event\Observer as Ob;
use Magento\Framework\Event\ObserverInterface;
use Wolf\Filter\Customer as WCustomer;
// 2019-09-08
final class ControllerActionPredispatch implements ObserverInterface {
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
		$categories = wolf_customer_get();
		$garageJ_session_used = false;
		foreach (wolf_sess_get() as $category) { /** @var string $category */
			if (!in_array($category, $categories)) {
				array_push($categories, $category);
				$garageJ_session_used = true;
			}
		}
		$complete_car_entry_added = false;
		$isComplete =
			dfa($_COOKIE, 'car_selected')
			&& 5 <= count($params)
			&& in_array($params[0]['value'], ['audi', 'bmw', 'volkswagen'])
		; /** @var bool $isComplete */
		if ($isComplete && !in_array($categoryPath, $categories)) {
			array_push($categories, $categoryPath);
			$complete_car_entry_added = true;
		}
		if ($garageJ_session_used || $complete_car_entry_added) {
			wolf_customer_set($categories);
			wolf_sess_set($categories);
		}
		sort($categories);
		WCustomer::garage($categories);
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