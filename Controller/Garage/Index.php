<?php
namespace Wolf\Filter\Controller\Garage;
use Df\Framework\W\Result\Json;
use Magento\Framework\App\Action\Action as _P;
use Wolf\Filter\Customer as WCustomer;
// 2019-09-09
/** @final Unable to use the PHP «final» keyword here because of the M2 code generation. */
class Index extends _P {
    /**
	 * 2019-09-09
	 * @override
	 * @see _P::execute()
	 * @used-by \Magento\Framework\App\Action\Action::dispatch():
	 * 		$result = $this->execute();
	 * https://github.com/magento/magento2/blob/2.2.1/lib/internal/Magento/Framework/App/Action/Action.php#L84-L125
	 * @return Json
	 */
	function execute() {return Json::i(WCustomer::garage());}
}