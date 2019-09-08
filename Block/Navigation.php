<?php
namespace Wolf\Filter\Block;
use Magento\Catalog\Block\Navigation as _P;
use Magento\Catalog\Model\Category as C;
use Magento\Newsletter\Model\Session as NewsletterSession;
use Magento\Widget\Block\BlockInterface as IWidget;
use Wolf\Filter\Customer as WCustomer;
use Wolf\Filter\Observer\Navigation as Ob;
class Navigation extends _P implements IWidget {
	/**                                                                              
	 * 2019-09-08
	 * @used-by vendor/wolfautoparts.com/filter/view/frontend/templates/sidebar.phtml 
	 * @used-by hDropdowns()
	 * @return array
	 */
	function getConfigJson() {return dfc($this, function() {
		list($urlPath, $urlName) = $this->urlPathAndName(); /** @var string $urlPath */ /** @var string $urlName */
		$r = [
			'id' => "cd-{$this->getNameInLayout()}"
			,'levels' => $this['levels']
			,'params' => df_registry('wolfCategoryParams')
		];
		$cacheTags = [Ob::CACHE_TAG];
		$menuTree = wolf_tree_load();
		$selectedCategories = [];
		$selectedCategoriesCacheId = 'selected_categories';
		$configCacheId = 'config_' . WCustomer::hash();
		// Build categories by level
		$da = unserialize(df_cache_load($configCacheId));
		if (false !==($data = df_cache_load($configCacheId)) && count($da[0])>0) {
			$categoriesByLevel = unserialize($data);
			if (false !==($data = df_cache_load($selectedCategoriesCacheId))) {
				$selectedCategories = unserialize($data);
			} 
			else {
				$selectedCategories = [];
			}
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
									str_replace(
										['.', '-'], ' ', df_trim_text_right(strtolower($menuTreeEntry['name']), '.html')
									)
						) {
                            $r['params'][$l]['id'] = $menuTreeEntry['id'];
                            $category['selected'] = true;
                            array_push($selectedCategories, $category);
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
			df_cache_save(serialize($selectedCategories), $selectedCategoriesCacheId, $cacheTags);
		}
		$r['categoriesByLevel'] = $categoriesByLevel;
		$r['selectedCategories'] = $selectedCategories;
        $r['customer_garage'] = WCustomer::garage();
        $r['customer_garage_is_empty'] = df_registry('wolfCustomerGarageIsEmpty');
		if (@$urlPath!='') {
          $r['customer_garage_uri'] = $urlPath;
          $r['customer_garage_uri_name'] = $urlName;
		}else{
		$r['customer_garage_uri'] = df_registry('wolfCustomerGarageUri');
        $r['customer_garage_uri_name'] = df_registry('wolfCustomerGarageUriName');
		}
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
	 * 2019-09-07
	 * @override
	 * @see \Magento\Catalog\Block\Navigation::_construct()
	 */
	protected function _construct() {parent::_construct(); $this->setTemplate('sidebar.phtml');}
	
	/**             
	 * 2019-09-08
	 * @used-by getConfigJson()
	 * @return array(string, string)
	 */
	private function urlPathAndName() {/** @var string $name */  /** @var string $path */
		/**
		 * 2019-09-08
		 * @see app/design/frontend/One80solution/wolfautoparts/Magento_Search/templates/form.mini.phtml
		 */
		$sess = df_o(NewsletterSession::class); /** @var NewsletterSession $sess */
		if (!($id = intval(df_request('cat') ?: $sess->getMyvalue()))) {/** @var int $id */
			$name = $path = '';
		}
		else {
			$c = df_category($id); /** @var C $c */
			$path = $c->getUrl();
			$name = strtr($c['url_path'], ['-' => ' ', '/' => ' ', '.html' => '']);
		}
		return [$path, $name];
	}
}