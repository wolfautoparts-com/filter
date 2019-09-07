<?php
namespace Wolf\Filter\Controller\Index;
use Df\Framework\W\Result\Json;
use Magento\Framework\App\Action\Action as _P;
use Wolf\Filter\Observer\Navigation as Ob;
// 2019-09-06
class Change extends _P {
    /**
	 * 2019-09-06 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
	 * «Decrease TTFB (time to first byte) for uncached category pages to 5 seconds»:
	 * https://www.upwork.com/ab/f/contracts/22684975
	 * @override
	 * @see _P::execute()
	 * @used-by \Magento\Framework\App\Action\Action::dispatch():
	 * 		$result = $this->execute();
	 * https://github.com/magento/magento2/blob/2.2.1/lib/internal/Magento/Framework/App/Action/Action.php#L84-L125
	 * @return Json
	 */
	function execute() {return Json::i(df_cache_get_simple('category_filter_' . df_request('selectedValue'), function() {
		if (!($bTree = wolf_tree_load())) {
			$r = [];
		}
		else {
			$levels = (int)df_request('levels'); /** @var int $levels «5» */
			$levelValues = [];
			for ($i = 0; $i < $levels; $i++) {
				$levelValues[$i] = (int)df_request("level_{$i}_value");
				if (0 < $levelValues[$i]) {
					if (isset($bTree[$levelValues[$i]])) {
						if (isset($bTree[$levelValues[$i]]['children'])) {
							$bTree = $bTree[$levelValues[$i]]['children'];
						}
					}
				}
			}
			$r = df_sort(
				df_map($bTree, function($v) {return dfa_select($v, ['id', 'name', 'url']);})
				,function($a, $b) {return strcasecmp($a['name'], $b['name']);}
			);
			// 2019-09-07 We show years in the reverse order.
			$r = array_values(1 !== (int)df_request('dataId') ? $r : array_reverse($r));
		}
		return $r;
	}, [Ob::CACHE_TAG]));}
}