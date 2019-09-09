<?php
namespace Wolf\Filter\Block;
use Magento\Catalog\Block\Navigation as _P;
use Magento\Newsletter\Model\Session as Sess;
use Magento\Widget\Block\BlockInterface as IWidget;
use Wolf\Filter\Customer as WC;
use Wolf\Filter\Observer\TopMenuGetHTMLBefore as Ob;
class Navigation extends _P implements IWidget {
	/**                                                                              
	 * 2019-09-08
	 * @used-by hDropdowns()
	 * @return array
	 */
	function getConfigJson() {return dfc($this, function() {
		$r = ['id' => "cd-{$this->getNameInLayout()}", 'levels' => $this['levels'], 'params' => WC::params()];
		/** 2019-09-08 @uses \Magento\Framework\App\Request\Http::getOriginalPathInfo() removes the `?...` part. */
		$cacheId = 'config_' . md5(df_request_o()->getOriginalPathInfo());
		 /** @var array(string => mixed) $topLevel */
		if (false !== ($d = df_cache_load($cacheId))) {
			$topLevel = unserialize($d);
		}
		else {
			$topLevel = [];
			foreach (wolf_tree_load() as $c) { /** @var array(string => mixed) $c */
				if (isset($r['params'][0]) && $r['params'][0]['name'] === wolf_u2n($c['name'])) {
					$r['params'][0]['id'] = $c['id'];
				}
				array_push($topLevel, ['id' => $c['id'], 'name' => $c['name']]);
			}
			usort($topLevel, function($a, $b) {return strtolower($a['name']) > strtolower($b['name']);});
			df_cache_save(serialize($topLevel), $cacheId, [Ob::CACHE_TAG]);
		}
		$r['topLevel'] = $topLevel;
		return $r;
	});}

	/**
	 * 2019-09-08
	 * @used-by vendor/wolfautoparts.com/filter/view/frontend/templates/sidebar.phtml
	 * @return string
	 */
	function hDropdowns() {
		$r = '';
		/**
		 * 2019-09-08
		 *	[
		 *		{
		 *			"id": 1721,
		 *			"name": "Audi",
		 * 			"url": null,
		 *			"selected": false
		 *		},
		 *		{
		 *			"id": 3613,
		 *			"name": "BMW",
		 *			"url": null,
		 *			"selected": false
		 *		},
		 *		{
		 *			"id": 3,
		 *			"name": "Volkswagen",
		 *			"url": null,
		 *			"selected": false
		 *		}
		 *	]
		 * @var array(array(string => string|int|bool|null)) $topLevel
		 */
		$topLevel = $this->getConfigJson()['topLevel'];
		$levels = $this['levels'];
		$lastLevel = $levels - 1;
		$labels = df_csv_parse($this['select_labels']); /** @var string[] $labels */
		$prefix = $this->getNameInLayout(); /** @var string $prefix */
		for ($l = 0; $l < $levels; $l++) {
			$label = dfa($labels, $l, 'Select category'); /** @var string $label */
			$j = $l + 1; /** @var int $j */
			$r .= df_tag('div'
				,df_cc_s('select-outer', $l !== $lastLevel ? '' : 'select-outer-last')
				,[
					$this->labelsAreInside() || !$label ? null : df_tag('label', [], $label)
					,df_tag('select'
						,['class' => 'category-filter-select', 'dataId' => $j, 'id' => $prefix . $j]
						,array_merge(
							[
								df_tag('option', ['value' => ''],
									$this->labelsAreInside() && $label ? $label : 'Please Select'
								)
							]
							,$l ? [] : df_map($topLevel, function($c) {return df_tag(
								'option', ['value' => $c['id']], $c['name']
							);})
						)
					)
				]
			);
		}
		return $r;
	}

	/**
	 * 2019-09-08
	 * @used-by hDropdowns()
	 * @used-by vendor/wolfautoparts.com/filter/view/frontend/templates/sidebar.phtml
	 * @return bool
	 */
	function labelsAreInside() {return 'embedded' === $this['labels_embedded'];}

	/**
	 * 2019-09-09
	 * @used-by vendor/wolfautoparts.com/filter/view/frontend/templates/selected_car_onsearchresultpage.phtml
	 * @used-by vendor/wolfautoparts.com/filter/view/frontend/templates/sidebar.phtml
	 * @return string
	 */
	function selectedName() {return wolf_u2n($this->selectedPath());}

	/**
	 * 2019-09-09
	 * @used-by vendor/wolfautoparts.com/filter/view/frontend/templates/sidebar.phtml
	 * @used-by selectedName()
	 * @return string|null
	 */
	function selectedPath() {return dfc($this, function() {$s = df_o(Sess::class); /** @var Sess $s */ return
		!WC::garage() ? null : (
			!($id = intval(df_request('cat') ?: $s->getMyvalue()))
				? WC::categoryPath()
				: '/' . df_category($id)['url_path']
		)
	;});}

	/**
	 * 2019-09-07
	 * @override
	 * @see \Magento\Catalog\Block\Navigation::_construct()
	 */
	protected function _construct() {parent::_construct(); $this->setTemplate('sidebar.phtml');}
}