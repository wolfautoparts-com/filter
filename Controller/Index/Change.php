<?php
namespace Wolf\Filter\Controller\Index;
use Df\Framework\W\Result\Json;
use Magento\Catalog\Model\Category as C;
use Magento\Framework\App\Action\Action as _P;
use Wolf\Filter\Observer\TopMenuGetHTMLBefore as Ob;
// 2019-09-06
/** @final Unable to use the PHP «final» keyword here because of the M2 code generation. */
class Change extends _P {
    /**
	 * 2019-09-06
	 * @override
	 * @see _P::execute()
	 * @used-by \Magento\Framework\App\Action\Action::dispatch():
	 * 		$result = $this->execute();
	 * https://github.com/magento/magento2/blob/2.2.1/lib/internal/Magento/Framework/App/Action/Action.php#L84-L125
	 * @return Json
	 */
	function execute() {$i = (int)df_request('dataId'); return Json::i(df_cache_get_simple(null,
		function($id, $rev, $needURL) {return df_sort(
			array_values(df_map(function(C $c) use($needURL) {return df_clean([
				'id' => (int)$c->getId(), 'name' => $c->getName(), 'url' => $needURL ? $c->getUrl() : null
			]);}, df_category($id)->getChildrenCategories()))
			,function($a, $b) use($rev) {$r = strcasecmp($a, $b); return $rev ? -$r : $r;}
		);}
	,[Ob::CACHE_TAG], (int)df_request('selectedValue'), 1 === $i, 4 === $i));}
}