<?php
namespace Wolf\Filter;
// 2019-09-08
final class Customer {
	/**
	 * 2019-09-08
	 * @used-by \Wolf\Filter\Block\Navigation::getConfigJson()
	 * @used-by \Wolf\Filter\Controller\Garage\Index::execute()
	 * @used-by \Wolf\Filter\Controller\Garage\Remove::execute()
	 * @used-by \Wolf\Filter\Observer\ControllerActionPredispatch::execute()
	 * @param mixed[]|null $v
	 * @return mixed[]|null
	 */
	static function garage($v = null) {return df_prop(null, $v, []);}

	/**
	 * 2019-09-08
	 * @used-by \Wolf\Filter\Block\Navigation::getConfigJson()
	 * @used-by \Wolf\Filter\Observer\ControllerActionPredispatch::execute()
	 * @param string|null $v
	 * @return string|null
	 */
	static function hash($v = null) {return df_prop(null, $v);}

	/**
	 * 2019-09-08
	 * @used-by \Wolf\Filter\Block\Navigation::getConfigJson()
	 * @used-by \Wolf\Filter\Observer\ControllerActionPredispatch::execute()
	 * @param string|null $v
	 * @return string|null
	 */
	static function uriName($v = null) {return df_prop(null, $v);}
}