<?php
namespace Wolf\Filter\Block;
use Magento\Catalog\Block\Navigation as _P;
use Magento\Newsletter\Model\Session as Sess;
use Magento\Widget\Block\BlockInterface as IWidget;
use Wolf\Filter\Customer as WC;
class Navigation extends _P implements IWidget {
	/**
	 * 2019-09-08
	 * @used-by vendor/wolfautoparts.com/filter/view/frontend/templates/sidebar.phtml
	 * @return string
	 */
	function hDropdowns() {
		$r = '';
		$levels = $this['levels'];
		$lastLevel = $levels - 1;
		$labels = df_csv_parse($this['select_labels']); /** @var string[] $labels */
		$prefix = $this->getNameInLayout(); /** @var string $prefix */
		// 2019-09-09 [1721 => "Audi", 3613 => "BMW", 3 => "Volkswagen"]
		$topLevel = df_cache_get_simple([__METHOD__, df_store_id()], function() {return df_sort(
			df_column(df_category(df_store()->getRootCategoryId())->getChildrenCategories(), 'getName', 'getId')
			,function($a, $b) {return strcasecmp($a, $b);}
		);}); /** @var array(int => string) $topLevel */
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
							[df_tag('option', ['value' => ''],
								$this->labelsAreInside() && $label ? $label : 'Please Select'
							)]
							,$l ? [] : df_map_k($topLevel, function($id, $name) {return df_tag(
								'option', ['value' => $id], $name
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
		!WC::garage() ? null : (!($id = intval(df_request('cat') ?: $s->getMyvalue()))
			? WC::categoryPath() : '/' . df_category($id)['url_path']
		)
	;});}

	/**
	 * 2019-09-07
	 * @override
	 * @see \Magento\Catalog\Block\Navigation::_construct()
	 */
	protected function _construct() {parent::_construct(); $this->setTemplate('sidebar.phtml');}
}