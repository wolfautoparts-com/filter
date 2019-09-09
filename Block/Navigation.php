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
	 * @used-by vendor/wolfautoparts.com/filter/view/frontend/templates/sidebar.phtml 
	 * @used-by hDropdowns()
	 * @return array
	 */
	function getConfigJson() {return dfc($this, function() {
		$r = [
			'id' => "cd-{$this->getNameInLayout()}"
			,'levels' => $this['levels']
			,'params' => WC::params()
		];
		$cacheTags = [Ob::CACHE_TAG];
		$menuTree = wolf_tree_load();
		/** 2019-09-08 @uses \Magento\Framework\App\Request\Http::getOriginalPathInfo() removes the `?...` part. */
		$configCacheId = 'config_' . md5(df_request_o()->getOriginalPathInfo());
		// Build categories by level
		$da = unserialize(df_cache_load($configCacheId));
		if (false !==($data = df_cache_load($configCacheId)) && count($da[0])>0) {
			$categoriesByLevel = unserialize($data);
		}
		else {
			$categoriesByLevel = [];
			// 2019-09-05 Dmitry Fedyuk https://www.upwork.com/fl/mage2pro
			// «Decrease TTFB(time to first byte) for uncached category pages to 5 seconds»:
			// https://www.upwork.com/ab/f/contracts/22684975
			for ($l = 0; $l < 1 /*$config['levels']*/; $l++) {
				$categoriesByLevel[$l] = [];
				$nextTree = null;
				if ($menuTree) {
                    foreach($menuTree as $menuTreeEntry) {
                        $category = null;
                        $category = array(
                            'id' => $menuTreeEntry['id'],
                            'name' => $menuTreeEntry['name'],
                            'url' => dfa($menuTreeEntry, 'url'),
                            'selected' => false
                      );
                        if (
                        	isset($r['params'][$l])
							&&
									$r['params'][$l]['name']
								===
									str_replace(['.', '-'], ' ', df_strip_ext(strtolower($menuTreeEntry['name'])))
						) {
                            $r['params'][$l]['id'] = $menuTreeEntry['id'];
                            $category['selected'] = true;
                            if (isset($menuTreeEntry['children']) && !empty($menuTreeEntry['children'])) {
                                $nextTree = $menuTreeEntry['children'];
                            } else {
                                $nextTree = null;
                            }
                        }
                        array_push($categoriesByLevel[$l], $category);
                    }
                }
                $menuTree = $nextTree;
                if (!empty($categoriesByLevel[$l])) {
                    usort($categoriesByLevel[$l], function($first, $second) {
                        return strtolower($first['name']) > strtolower($second['name']);
                    });
                }
			}
			df_cache_save(serialize($categoriesByLevel), $configCacheId, $cacheTags);
		}
		$r['categoriesByLevel'] = $categoriesByLevel;
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
		 * @var array(array(string => string|int|bool|null)) $topLevelCategories
		 */
		$topLevelCategories = $this->getConfigJson()['categoriesByLevel'][0];
		$levels = $this['levels'];
		$lastLevel = $levels - 1;
		$labels = df_csv_parse($this['select_labels']); /** @var string[] $labels */
		for ($l = 0; $l < $levels; $l++) {
			$label = dfa($labels, $l, 'Select category'); /** @var string $label */
			$j = $l + 1; /** @var int $j */
			$r .= df_tag('div'
				,df_cc_s('select-outer', $l !== $lastLevel ? '' : 'select-outer-last')
				,[
					$this->labelsAreInside() || !$label ? null : df_tag('label', [], $label)
					,df_tag('select'
						,[
							'class' => 'category-filter-select'
							,'dataId' => $j
							,'id' => "{$this->getNameInLayout()}$j"
						]
						,array_merge(
							[
								df_tag('option', ['value' => ''],
									$this->labelsAreInside() && $label ? $label : 'Please Select'
								)
							]
							,$l ? [] : df_map($topLevelCategories, function($c) {return df_tag('option'
								,['dataUrl' => $c['url'], 'value' => $c['id']]
								,$c['name']
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