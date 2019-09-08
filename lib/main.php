<?php
use Wolf\Filter\Observer\Navigation as Ob;
use Magento\Customer\Model\Session;
use Wolf\Filter\Session as SessionW;
/**
 * 2019-09-08
 * @used-by \Wolf\Filter\Observer\ControllerActionPredispatch::execute()
 * @return array(string => mixed[])
 */
function wolf_sess_get() {
	$sess = df_customer_session(); /** @var Session|SessionW $sess */
	$r = $sess->getCustomerGarageJson(); /** @var string|null $r */
	return !$r || '{}' === $r ? ['cars' => []] : df_json_decode($r);
}

/**
 * 2019-09-08
 * @used-by \Wolf\Filter\Controller\Garage\Clean::execute()
 * @used-by \Wolf\Filter\Controller\Garage\Remove::execute()
 * @used-by \Wolf\Filter\Observer\ControllerActionPredispatch::execute()
 * @param string $v
 */
function wolf_sess_set($v) {
	$sess = df_customer_session(); /** @var Session|SessionW $sess */
	$sess->setCustomerGarageJson($v);
}

/**
 * 2019-09-07
 * @used-by \Wolf\Filter\Block\Navigation::getConfigJson()
 * @used-by \Wolf\Filter\Controller\Index\Change::execute()
 * @used-by \Wolf\Filter\Observer\Navigation::execute()
 * @return mixed[]
 */
function wolf_tree_load() {return false === ($r = df_cache_load(Ob::CACHE_KEY)) ? [] : unserialize($r);}