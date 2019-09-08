<?php
namespace Wolf\Filter\Controller\Garage;
use Df\Framework\W\Result\Json;
use Magento\Framework\App\Action\Action as _P;
// 2019-09-08
class Clean extends _P {
    /**
	 * 2019-09-08
	 * @override
	 * @see _P::execute()
	 * @used-by \Magento\Framework\App\Action\Action::dispatch():
	 * 		$result = $this->execute();
	 * https://github.com/magento/magento2/blob/2.2.1/lib/internal/Magento/Framework/App/Action/Action.php#L84-L125
	 * @return Json
	 */
	function execute() {wolf_customer_set([]); wolf_sess_set([]); return Json::i(['success' => true]);}
}