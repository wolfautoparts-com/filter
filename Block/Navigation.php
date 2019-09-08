<?php
namespace Wolf\Filter\Block;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Helper\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Indexer\Category\Flat\State;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Newsletter\Model\Session as NewsletterSession;
use Magento\Widget\Block\BlockInterface;
use Wolf\Filter\Observer\Navigation as Ob;
class Navigation extends \Magento\Catalog\Block\Navigation implements BlockInterface {
	/**
	 * 2019-09-07
	 * @override
	 * @see \Magento\Catalog\Block\Navigation::__construct()
	 * @used-by \Magento\Framework\View\Element\BlockFactory::createBlock()
	 * @param Context $context
	 * @param CategoryFactory $categoryFactory
	 * @param CategoryRepositoryInterface $categoryRepository
	 * @param CollectionFactory $productCollectionFactory
	 * @param Resolver $layerResolver
	 * @param HttpContext $httpContext
	 * @param Category $catalogCategory
	 * @param Registry $registry
	 * @param State $flatState
	 * @param CacheInterface $cache
	 * @param array $data
	 */
	function __construct(
		Context $context,
		CategoryFactory $categoryFactory,
		CategoryRepositoryInterface $categoryRepository,
		CollectionFactory $productCollectionFactory,
		Resolver $layerResolver,
		HttpContext $httpContext,
		Category $catalogCategory,
		Registry $registry,
		State $flatState,
		CacheInterface $cache,
		array $data = []
	) {
		$this->_productCollectionFactory = $productCollectionFactory;
		$this->_httpContext = $httpContext;
		$this->_catalogCategory = $catalogCategory;
		$this->_registry = $registry;
		$this->_flatState = $flatState;
		$this->_categoryInstance = $categoryFactory->create();
		$this->_categoryRepository = $categoryRepository;
		$this->_cache = $cache;
		parent::__construct($context, $categoryFactory, $productCollectionFactory, $layerResolver, $httpContext,
			$catalogCategory, $registry, $flatState, $data);
	}

	/**
	 * @return mixed
	 */
	function getLabelsEmbedded() {return $this->getData('labels_embedded');}

	/**
	 * @return array
	 */
	function getSelectLabels() {
		$labels = array();
		foreach(explode(",", $this->getData('select_labels')) as $label) {
			$labels[] = __($label);
		}
		return $labels;
	}

	/**
	 * @param $i
	 * @return mixed
	 */
	function getSelectLabel($i) {
		$labels = $this->getSelectLabels();
		if (isset($labels[$i])) {
			return __($labels[$i]);
		} else {
			return __('Select category');
		}
	}

	/**
	 * @return mixed
	 */
	function getBaseUrl() {return $this->_storeManager->getStore()->getBaseUrl();}

	/**
	 * @return array
	 */
	function getCacheKeyInfo() {
		$shortCacheId = [
			'CATEGORY_FILTER',
			$this->_storeManager->getStore()->getId(),
			$this->_design->getDesignTheme()->getId(),
			$this->_httpContext->getValue('wolf_categoryfilter'),
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
	 * @return array
	 */
	function getConfigJson() {return dfc($this, function() {
		$sess = df_o(NewsletterSession::class); /** @var NewsletterSession $sess */
		$urlPath = '';
		$urlName ='';
		if (@$_GET['cat']!=''){
			$catid = @$_GET['cat'];
			$dfCategory = df_new_om('Magento\Catalog\Model\Category')->load($catid);
			$arr = $dfCategory->getData();
			if ($arr['url_path']!=''){
				$urlPath = $dfCategory->getUrl();
				$urlName = str_replace("-"," ",str_replace("/"," ",$arr['url_path']));
			}
		}
		/** 2019-09-08 @see app/design/frontend/One80solution/wolfautoparts/Magento_Search/templates/form.mini.phtml */
		else if ($sess->getMyvalue()!='') {
			$catid = $sess->getMyvalue();
			$dfCategory = df_new_om('Magento\Catalog\Model\Category')->load($catid);
			$arr = $dfCategory->getData();
			if ($arr['url_path']!=''){
				$urlPath = $dfCategory->getUrl();
				$urlName = str_replace("-"," ",str_replace("/"," ",$arr['url_path']));
			}
		}
		$urlName = str_replace(".html",'',$urlName);
		if ($this->getLabelsEmbedded() == 'outside') {
			$label = '';
		} 
		else {
			$label = $this->getLabelsEmbedded();
		}
		$config = array(
			'levels' => $this['levels'],
			'id' => 'cd-' . $this->getNameInLayout(),
			'current_category_id' =>($this->_registry->registry('current_category') ? $this->_registry->registry('current_category')->getId() : 0),
			'fetch_children_url' => $this->getUrl('categoryfilter/ajax/fetchChildren'),
			'labels' => $this->getSelectLabels(),
			'default_label' => __('Select category'),
			'labels_embedded' => $label,
			'please_wait_text' => __('Please wait...'),
		);
		$cacheTags = [Ob::CACHE_TAG];
		$menuTree = wolf_tree_load();
		$paramsHash = $this->_registry->registry('wolfCategoryParamsHash');
		$config['params'] = $this->_registry->registry('wolfCategoryParams');
		$selectedCategories = [];
		$selectedCategoriesCacheId = 'selected_categories';
		$configCacheId = 'config_' . $paramsHash;
		// Build categories by level
		$da = unserialize($this->_cache->load($configCacheId));
		if (false !==($data = $this->_cache->load($configCacheId)) && count($da[0])>0) {
			$categoriesByLevel = unserialize($data);
			if (false !==($data = $this->_cache->load($selectedCategoriesCacheId))) {
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
                        if (isset($config['params'][$l]) && $this->sanitizeUrl($menuTreeEntry['name']) == $config['params'][$l]['name']) {
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
			$this->_cache->save(serialize($categoriesByLevel), $configCacheId, $cacheTags);
			$this->_cache->save(serialize($selectedCategories), $selectedCategoriesCacheId, $cacheTags);
		}
		$config['categoriesByLevel'] = $categoriesByLevel;
		$config['selectedCategories'] = $selectedCategories;
        $config['customer_garage'] = $this->_registry->registry('wolfCategoryCustomerGarage');
        $config['customer_garage_is_empty'] = $this->_registry->registry('wolfCustomerGarageIsEmpty');
		if (@$urlPath!=''){
          $config['customer_garage_uri'] = $urlPath;
          $config['customer_garage_uri_name'] = $urlName;
		}else{
		$config['customer_garage_uri'] = $this->_registry->registry('wolfCustomerGarageUri');
        $config['customer_garage_uri_name'] = $this->_registry->registry('wolfCustomerGarageUriName');
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
		for ($l = 0; $l < $levels; $l++) {
			$label = $this->getSelectLabel($l); /** @var string $label */
			$j = $l + 1; /** @var int $j */
			$r .= df_tag('div'
				,df_cc_s('select-outer', $l !== $lastLevel ? '' : 'select-outer-last')
				,[
					'outside' !== $this->getLabelsEmbedded() || !$label ? null : df_tag('label', [], $label)
					,df_tag('select'
						,[
							'class' => 'category-filter-select'
							,'dataId' => $j
							,'id' => "{$this->getNameInLayout()}$j"
						]
						,array_merge(
							[
								df_tag('option', ['value' => ''],
									'embedded' === $this->getLabelsEmbedded() && $label
									? $label : 'Please Select'
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
	 * 2019-09-07
	 * @override
	 * @see \Magento\Catalog\Block\Navigation::_construct()
	 */
	protected function _construct() {parent::_construct(); $this->setTemplate('sidebar.phtml');}
	
	/**
	 * @param $name
	 * @return mixed
	 */
	private function sanitizeUrl($name) {
		$name = strtolower($name);
		$pos = strpos($name, '.html');
		if ($pos > 0) {
			$name = substr($name, 0, $pos);
		}
		$name = str_replace(array('.', '-'), ' ', $name);
		return $name;
	}	
	
	protected $_catalogCategory;
	protected $_categoryInstance;
	protected $_categoryRepository;
	protected $_flatState;
	protected $_httpContext;
	protected $_productCollectionFactory;
	protected $_registry;
}