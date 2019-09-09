<?php
namespace Wolf\Filter\Controller\Index;
use Df\Framework\W\Result\Json;
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
	function execute() {return Json::i(df_cache_get_simple('category_filter_' . df_request('selectedValue'), function() {
		if (!($tree = wolf_tree_load())) {
			$r = [];
		}
		else {
			$levels = (int)df_request('levels'); /** @var int $levels «5» */
			$levelValues = [];
			for ($i = 0; $i < $levels; $i++) {
				$levelValues[$i] = (int)df_request("level_{$i}_value");
				if (0 < $levelValues[$i]) {
					if (isset($tree[$levelValues[$i]])) {
						if (isset($tree[$levelValues[$i]]['children'])) {
							$tree = $tree[$levelValues[$i]]['children'];
						}
					}
				}
			}
			$r = df_sort(
				df_map($tree, function($v) {return dfa_select($v, ['id', 'name', 'url']);})
				,function($a, $b) {return strcasecmp($a['name'], $b['name']);}
			);
			// 2019-09-07 We show years in the reverse order.
			$r = array_values(1 !== (int)df_request('dataId') ? $r : array_reverse($r));
		}
		return $r;
	}, [Ob::CACHE_TAG]));}
}