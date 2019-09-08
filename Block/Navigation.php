<?php
namespace Wolf\Filter\Block;
use Magento\Catalog\Model\Category as C;
use Magento\Newsletter\Model\Session as NewsletterSession;
use Magento\Widget\Block\BlockInterface;
use Wolf\Filter\Observer\Navigation as Ob;
class Navigation extends \Magento\Catalog\Block\Navigation implements BlockInterface {
	/**
	 * @return array
	 */
	function getCacheKeyInfo() {
		$shortCacheId = [
			'CATEGORY_FILTER',
			df_store_id(),
			df_design()->getDesignTheme()->getId(),
			df_http_context()->getValue('wolf_categoryfilter'),
			'template' => $this->getTemplate(),
			'name' => $this->getNameInLayout()
		];
		$cacheId = $shortCacheId;
		$shortCacheId = array_values($shortCacheId);
		$shortCacheId = implode('|', $shortCacheId);
		$shortCacheId = md5($shortCacheId);
		$cacheId['category_path'] = $this->getCurrentCategoryKey();
		$cacheId['short_cache_id'] = $shortCacheId;
		return $cacheId;
	}

	/**                                                                              
	 * 2019-09-08
	 * @used-by vendor/wolfautoparts.com/filter/view/frontend/templates/sidebar.phtml 
	 * @used-by hDropdowns()
	 * @return array
	 */
	function getConfigJson() {return dfc($this, function() {
		list($urlPath, $urlName) = $this->urlPathAndName(); /** @var string $urlPath */ /** @var string $urlName */
		$config = [
			'current_category_id' => df_registry('current_category') ?: 0
			,'default_label' => __('Select category')
			,'fetch_children_url' => df_url('categoryfilter/ajax/fetchChildren')
			,'id' => 'cd-' . $this->getNameInLayout()
			,'levels' => $this['levels']
			,'please_wait_text' => __('Please wait...')
		];
		$cacheTags = [Ob::CACHE_TAG];
		$menuTree = wolf_tree_load();
		$paramsHash = df_registry('wolfCategoryParamsHash');
		$config['params'] = df_registry('wolfCategoryParams');
		$selectedCategories = [];
		$selectedCategoriesCacheId = 'selected_categories';
		$configCacheId = 'config_' . $paramsHash;
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
			for($l = 0; $l < 1 /*$config['levels']*/; $l++) {
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
                        	isset($config['params'][$l])
							&&
									$config['params'][$l]['name']
								===
									str_replace(
										['.', '-'], ' ', df_trim_text_right(strtolower($menuTreeEntry['name']), '.html')
									)
						) {
                            $config['params'][$l]['id'] = $menuTreeEntry['id'];
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
		$config['categoriesByLevel'] = $categoriesByLevel;
		$config['selectedCategories'] = $selectedCategories;
        $config['customer_garage'] = df_registry('wolfCategoryCustomerGarage');
        $config['customer_garage_is_empty'] = df_registry('wolfCustomerGarageIsEmpty');
		if (@$urlPath!='') {
          $config['customer_garage_uri'] = $urlPath;
          $config['customer_garage_uri_name'] = $urlName;
		}else{
		$config['customer_garage_uri'] = df_registry('wolfCustomerGarageUri');
        $config['customer_garage_uri_name'] = df_registry('wolfCustomerGarageUriName');
		}
		return $config;
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
		if (!($id = intval(df_request('cat') ?: $sess->getMyvalue()))) { /** @var int $id */
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