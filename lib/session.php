<?php
use Magento\Customer\Api\Data\CustomerInterface as ICD;
use Magento\Customer\Model\Customer as C;
use Magento\Customer\Model\Data\Customer as CD;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\AttributeInterface as IAV;
use Magento\Framework\Api\AttributeValue as AV;
use Wolf\Filter\Session as SessionW;
use Wolf\Filter\Setup\InstallData as Schema;
/**
 * 2019-09-08
 * @used-by \Wolf\Filter\Observer\ControllerActionPredispatch::execute()
 * @return string[]
 */
function wolf_customer_get() {
	$r = []; /** @var string[] $r */
	if (($c = df_customer()) /** @var C $c */
		&& ($av = $c->getDataModel()->getCustomAttribute(Schema::GARAGE)) /** @var IAV|AV $av */
	) {
		$v = $av->getValue();
		$r = !$v || '{}' === $v ? [] : df_json_decode($v);
		$r = dfa($r, 'cars', $r); // 2019-09-08 For backward compatibility
	}
	return $r;
}

/**
 * 2019-09-08
 * @used-by \Wolf\Filter\Observer\ControllerActionPredispatch::execute()
 * @return string[]
 */
function wolf_sess_get() {
	$sess = df_customer_session(); /** @var Session|SessionW $sess */
	$r = $sess->getWolfCategories(); /** @var string|null $r */
	return !$r || '{}' === $r ? [] : df_json_decode($r);
}

/**
 * 2019-09-08
 * @used-by \Wolf\Filter\Controller\Garage\Clean::execute()
 * @used-by \Wolf\Filter\Controller\Garage\Remove::execute()
 * @used-by \Wolf\Filter\Observer\ControllerActionPredispatch::execute()
 * @param string[] $v
 */
function wolf_set(array $v) {
	$sess = df_customer_session(); /** @var Session|SessionW $sess */
	$sess->setWolfCategories($j = df_json_encode($v)); /** @var string $j */
	if ($c = df_customer()) { /** @var C|false $c */
		$d = $c->getDataModel(); /** @var ICD|CD $d */
		$d->setCustomAttribute(Schema::GARAGE, $j);
		$c->updateData($d);
		df_customer_resource()->saveAttribute($c, Schema::GARAGE);
	}
}