<?php
namespace Wolf\Filter\Setup;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;
class InstallData implements InstallDataInterface {
	/**
	 * @var \Magento\Eav\Setup\EavSetupFactory
	 */
	private $_eavSetupFactory;

	/**
	 * @var \Magento\Eav\Model\AttributeRepository
	 */
	private $_attributeRepository;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

	/**
	 * Init
	 *
	 * @param \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory
	 * @param \Magento\Eav\Model\AttributeRepository $attributeRepository
	 * @param \Magento\Framework\App\Cache\TypeListInterface
	 */
	function __construct(
		\Magento\Eav\Setup\EavSetupFactory $eavSetupFactory,
		\Magento\Eav\Model\AttributeRepository $attributeRepository,
		\Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
		\Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
		LoggerInterface $logger
	) {
		$this->_eavSetupFactory = $eavSetupFactory;
		$this->_attributeRepository = $attributeRepository;
		$this->_cacheTypeList = $cacheTypeList;
		$this->_cacheFrontendPool = $cacheFrontendPool;
		$this->_logger = $logger;
	}

	private function cleanCache(){
		$types = $this->_cacheTypeList->getTypes();
		foreach ($types as $type) {
			$this->_cacheTypeList->cleanType($type['id']);
		}
		foreach ($this->_cacheFrontendPool as $cacheFrontend) {
			$cacheFrontend->getBackend()->clean();
		}
	}

	/**
	 * Installs DB schema for module
	 *
	 * @param ModuleDataSetupInterface $setup
	 * @param ModuleContextInterface $context
	 * @throws \Exception
	 * @return void
	 */
	function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {
		$installer = $setup;
		$installer->startSetup();
		$eavSetup = $this->_eavSetupFactory->create(['setup' => $setup]);
		$eavSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, self::GARAGE);
		$this->cleanCache();
		// add customer_attribute to customer
		$eavSetup->addAttribute(
			\Magento\Customer\Model\Customer::ENTITY, self::GARAGE, [
			'type' => 'text',
			'label' => 'Garage JSON',
			'input' => 'text',
			'required' => false,
			'system' => 0,
			'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
			'sort_order' => '200']);
		// allow customer_attribute attribute to be saved in the specific areas
		$attribute = $this->_attributeRepository->get('customer', self::GARAGE);
		$setup->getConnection()
			->insertOnDuplicate(
				$setup->getTable('customer_form_attribute'),
				[
					['form_code' => 'adminhtml_customer', 'attribute_id' => $attribute->getId()],
					['form_code' => 'customer_account_create', 'attribute_id' => $attribute->getId()],
					['form_code' => 'customer_account_edit', 'attribute_id' => $attribute->getId()],
			   ]
		   );
		$installer->endSetup();
	}

	/**
	 * 2019-08-09
	 * @used-by install()
	 * @used-by \Wolf\Filter\Controller\Garage\Clean::execute()
	 * @used-by \Wolf\Filter\Controller\Garage\Remove::execute()
	 * @used-by \Wolf\Filter\Observer\ControllerActionPredispatch::execute()
	 * @type string
	 */
	const GARAGE = 'customer_garage_json';
}