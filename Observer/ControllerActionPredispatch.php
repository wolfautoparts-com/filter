<?php
namespace Wolf\Filter\Observer;
use Magento\Framework\Event\Observer as Ob;
use Magento\Framework\Event\ObserverInterface;
use Wolf\Filter\Customer as WC;
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
		 * @var array(array(string => string)) $p
		 */
		$p = df_map($pathA, function($v) {return ['id' => null, 'name' => wolf_u2n($v), 'value' => $v];});
		WC::params($p);
		$current = '/' . df_cc_path(array_slice($pathA, 0, 5)) . '.html'; /** @var string $current */
		$cC = wolf_customer_get(); /** @var string[] $cC */
		$cS = wolf_sess_get(); /** @var string[] $cS */
		$c = array_unique(array_merge($cC, $cS)); /** @var string[] $c */
		$cookie = 'car_selected'; /** @var string $cookie */
		/** @var bool $complete */
		$complete = dfa($_COOKIE, $cookie) && 5 <= count($p) && in_array($p[0]['value'], ['audi', 'bmw', 'volkswagen']);
		/** @var bool $added */
		if ($added = $complete && !in_array($current, $c)) {
			$c[]= $current;
		}
		if ($added || array_diff($cS, $cC)) {
			wolf_customer_set($c);
			wolf_sess_set($c);
		}
		sort($c);
		WC::garage($c);
		WC::categoryPath(!$complete ? null : $current);
		// 2019-09-08 «Remove a cookie»: https://stackoverflow.com/a/686166
		setcookie($cookie, '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
	}
}