<?php
/**
 * Copyright (C) 2017-2019 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017-2019 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_TB_VERSION_'))
	exit;
/**
*
* Added back office hooks
*
**/

class tbHtmlBlock extends Module
{
	/* @var boolean error */
	protected $_errors = false;
	protected $hooks_list = array();

	protected static $_cachedHooksList;

	protected $_tabs = array(
		'AdminHTMLBlock' => 'Custom Blocks',// class => label
		);

	public function __construct()
	{
		$this->name = 'tbhtmlblock';
		$this->tab = 'front_office_features';
		$this->version = '1.1.2';
		$this->author = 'thirty bees';
		$this->need_instance = 0;
		$this->table_name = 'tbhtmlblock';
		$this->table_name_lang = 'tbhtmlblock_lang';
		$this->table_name_hook = 'tbhtmlblock_hook';
		$this->bootstrap = true;

		// List of hooks 
		$this->hooks_list = array(
			'displayHeader',
			'displayLeftColumn',
			'displayRightColumn',
			'displayHome',
			'displayTop',
			'displayFooter',
			'displayFooterProduct',
			'displayMyAccountBlock',
			'displayBackOfficeFooter',
			'displayBackOfficeHeader',
			'displayBackOfficeHome',
			'displayBackOfficeTop',
			'displayBackOfficeCategory',
			'displayAdminOrder',
			'displayAdminCustomers',
			'displayBeforeCarrier',
			'displayBeforePayment',
			'displayCustomerAccount',
			'displayCustomerAccountForm',
			'displayCustomerAccountFormTop',
			'displayLeftColumnProduct',
			'displayMaintenance',
			'displayRightColumnProduct',
			'displayProductTab',
			'displayProductTabContent',
			'displayPayment',
			'displayPaymentReturn',
			'displayPaymentTop',
			'displayProductButtons',
			'displayProductComparison',
			'displayShoppingCart',
			'displayShoppingCartFooter',
			'displayTopColumn',
			'displayProductListFunctionalButtons',
			'displayPDFInvoice',
			'displayInvoice',
			'displayNav',
			'displayMyAccountBlockFooter',
			'displayHomeTab',
			'displayHomeTabContent',
			);

		if (version_compare(@_PS_VERSION_,'1.6','>'))
		{
			$this->hooks_list[] = 'displayMaintenance';
		}
	 	parent::__construct();

		$this->displayName = $this->l('Custom HTML Block');
		$this->description = $this->l('Add custom html or code anywhere in your theme');
	}
	
	public function install()
	{
		if (!parent::install() OR
			!$this->_createTabs() OR
			!$this->_installTable())
			return false;
		foreach ($this->hooks_list as $hook) {
			if(!$this->registerHook($hook))
				return false;
		}
		return true;
	}
	
	public function uninstall()
	{
		if (!parent::uninstall() OR !$this->_eraseTable() OR !$this->_eraseTabs())
			return false;
		return true;
	}



	private function _installTable(){
		$sql = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_name.'` (
				`id_block` INT( 12 ) AUTO_INCREMENT,
				`name` VARCHAR( 64 ) NOT NULL,
				`active` TINYINT(1) NOT NULL,
				PRIMARY KEY (  `id_block` )
				) ENGINE =' ._MYSQL_ENGINE_;
		$sql2 = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_name_lang.'` (
				`id_block` INT( 12 ),
				`id_lang` INT( 12 ) NOT NULL,
				`content` TEXT NOT NULL,
				PRIMARY KEY (  `id_block`, `id_lang` )
				) ENGINE =' ._MYSQL_ENGINE_;
		$sql3 = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_name_hook.'` (
				`id_block` INT( 12 ),
				`hook_name` VARCHAR( 64 ) NOT NULL,
				`position` INT( 12 ) NOT NULL,
				PRIMARY KEY (  `id_block`,  `hook_name`)
				) ENGINE =' ._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';
		if (!Db::getInstance()->Execute($sql) || !Db::getInstance()->Execute($sql2) || !Db::getInstance()->Execute($sql3))
			return false;
		else return true;
	}

	private function _eraseTable(){
		if(!Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.$this->table_name.'`') || !Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.$this->table_name_lang.'`') || !Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.$this->table_name_hook.'`'))
			return false;
		else return true;
	}


	private function _createTabs()
	{
	    /* This is the main tab, all others will be children of this */
		$all_langs = Language::getLanguages();
	    $id_tab = $this->_createSingleTab(0, 'Admin'.ucfirst($this->name), $this->displayName, $all_langs);
	    
	    foreach ($this->_tabs as $class => $name) {
	    	  $this->_createSingleTab($id_tab, $class, $name, $all_langs);
	    }

	    return true;

	}


	private function _createSingleTab($id_parent, $class, $name, $all_langs)
	{

		$tab = new Tab();
		$tab->active = 1;

		foreach ($all_langs as $language)
			$tab->name[$language['id_lang']] = $name;

		$tab->class_name = $class;
		$tab->module = $this->name;
		$tab->id_parent = $id_parent;

		if($tab->add())
			return $tab->id;
		else return false;

	}

	/**
	 * Get rid of all installed back office tabs
	 */
	private function _eraseTabs()
	{
		$id_tabm = (int)Tab::getIdFromClassName('Admin'.ucfirst($this->name));
		if($id_tabm)
		{
			$tabm = new Tab($id_tabm);
			$tabm->delete();
		}

		foreach ($this->_tabs as $class => $name) {
			$id_tab = (int)Tab::getIdFromClassName($class);
			if($id_tab)
			{
				$tab = new Tab($id_tab);
				$tab->delete();
			}
		}

		return true;
	}	



	public function getAllBlocks()
	{
		
		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
			SELECT b.*, bh.*, h.title as hook_title
			FROM '._DB_PREFIX_.$this->table_name.' b
			LEFT JOIN '._DB_PREFIX_.$this->table_name_hook.' bh ON (bh.id_block = b.id_block)
			LEFT JOIN '._DB_PREFIX_.'hook h ON (h.name = bh.hook_name)
			GROUP BY b.id_block
			ORDER BY bh.hook_name, bh.position
			');

		if(!$result)
			return false;

		$final_blocks = array();
		foreach ($result as $block) {
			$final_blocks[$block['hook_name']]['name'] = $block['hook_title'];
			$final_blocks[$block['hook_name']]['blocks'][] = $block;
		}
		return $final_blocks;
		    
	}

	public function getFrontBlocks()
	{
		
		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
			SELECT b.content, bh.hook_name
			FROM '._DB_PREFIX_.$this->table_name_lang.' b
			LEFT JOIN '._DB_PREFIX_.$this->table_name_hook.' bh ON (bh.id_block = b.id_block)
			WHERE id_lang = '.$this->context->language->id.'
			GROUP BY b.id_block
			ORDER BY bh.hook_name, bh.position
			');

		if(!$result)
			return false;

		$final_blocks = array();
		foreach ($result as $block) {
			$final_blocks[$block['hook_name']][] = $block['content'];
		}
		return $final_blocks;		
	}

	public function getHooksWithNames()
	{

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
			SELECT *
			FROM '._DB_PREFIX_.'hook
			WHERE name IN ("'.implode('","', $this->hooks_list).'")
			ORDER BY title
			');
		    
	}

	
	public function getSingleBlockData($id_block)
	{
		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
			SELECT *
			FROM '._DB_PREFIX_.$this->table_name .' t
			LEFT JOIN '._DB_PREFIX_.$this->table_name_lang.' tl ON (t.id_block = tl.id_block)
			LEFT JOIN '._DB_PREFIX_.$this->table_name_hook.' th ON (t.id_block = th.id_block)
			WHERE t.id_block ='.$id_block.'
			');


		if(!$result)
			return false;

		$new_block = $result[0];

		$new_block['content'] = array();
		foreach ($result as $key => $block)
		{
			$new_block['content'][$block['id_lang']] = $block['content'];			
		}		
		return $new_block;
		    
	}

	public function getBlockStatus($id_block)
	{
		return Db::getInstance()->getValue('SELECT active FROM '._DB_PREFIX_.$this->table_name.' WHERE id_block = '.$id_block);
	}

	public function hookCommon($hookname, $params)
	{

		//check if all hooks are cached, if not get them
		//add getFrontHooks
		
		if(!self::$_cachedHooksList)
		{
			self::$_cachedHooksList = $this->getFrontBlocks();
		}
		$hooks = self::$_cachedHooksList;

		if(!isset($hooks[$hookname]))
			return false;
		
		$this->smarty->assign('tbhtmlblock_blocks', $hooks[$hookname]);

		return $this->display(__FILE__, 'tbhtmlblock.tpl');
	}
	
	public function hookDisplayHeader($params)
	{
		return $this->hookCommon('displayHeader', $params);
	}

	public function hookDisplayLeftColumn($params)
	{
		return $this->hookCommon('displayLeftColumn',$params);
	}
	public function hookDisplayRightColumn($params)
	{
		return $this->hookCommon('displayRightColumn',$params);
	}
	public function hookDisplayHome($params)
	{
		return $this->hookCommon('displayHome',$params);
	}
	public function hookDisplayTop($params)
	{
		return $this->hookCommon('displayTop',$params);
	}
	public function hookDisplayFooter($params)
	{
		return $this->hookCommon('displayFooter',$params);
	}
	public function hookDisplayFooterProduct($params)
	{
		return $this->hookCommon('displayFooterProduct',$params);
	}
	public function hookDisplayMyAccountBlock($params)
	{
		return $this->hookCommon('displayMyAccountBlock',$params);
	}
	public function hookDisplayBackOfficeFooter($params)
	{
		return $this->hookCommon('displayBackOfficeFooter',$params);
	}
	public function hookDisplayBackOfficeHeader($params)
	{
		return $this->hookCommon('displayBackOfficeHeader',$params);
	}
	public function hookDisplayBackOfficeHome($params)
	{
		return $this->hookCommon('displayBackOfficeHome',$params);
	}
	public function hookDisplayBackOfficeTop($params)
	{
		return $this->hookCommon('displayBackOfficeTop',$params);
	}
	public function hookDisplayBackOfficeCategory($params)
	{
		return $this->hookCommon('displayBackOfficeCategory',$params);
	}
	public function hookDisplayAdminOrder($params)
	{
		return $this->hookCommon('displayAdminOrder',$params);
	}
	public function hookDisplayAdminCustomers($params)
	{
		return $this->hookCommon('displayAdminCustomers',$params);
	}
	public function hookDisplayBeforeCarrier($params)
	{
		return $this->hookCommon('displayBeforeCarrier',$params);
	}
	public function hookDisplayBeforePayment($params)
	{
		return $this->hookCommon('displayBeforePayment',$params);
	}
	public function hookDisplayCustomerAccount($params)
	{
		return $this->hookCommon('displayCustomerAccount',$params);
	}
	public function hookDisplayCustomerAccountForm($params)
	{
		return $this->hookCommon('displayCustomerAccountForm',$params);
	}
	public function hookDisplayCustomerAccountFormTop($params)
	{
		return $this->hookCommon('displayCustomerAccountFormTop',$params);
	}
	public function hookDisplayLeftColumnProduct($params)
	{
		return $this->hookCommon('displayLeftColumnProduct',$params);
	}
	public function hookDisplayMaintenance($params)
	{
		return $this->hookCommon('displayMaintenance',$params);
	}
	public function hookDisplayRightColumnProduct($params)
	{
		return $this->hookCommon('displayRightColumnProduct',$params);
	}
	public function hookDisplayProductTab($params)
	{
		return $this->hookCommon('displayProductTab',$params);
	}
	public function hookDisplayProductTabContent($params)
	{
		return $this->hookCommon('displayProductTabContent',$params);
	}
	public function hookDisplayPayment($params)
	{
		return $this->hookCommon('displayPayment',$params);
	}
	public function hookDisplayPaymentReturn($params)
	{
		return $this->hookCommon('displayPaymentReturn',$params);
	}
	public function hookDisplayPaymentTop($params)
	{
		return $this->hookCommon('displayPaymentTop',$params);
	}
	public function hookDisplayProductButtons($params)
	{
		return $this->hookCommon('displayProductButtons',$params);
	}
	public function hookDisplayProductComparison($params)
	{
		return $this->hookCommon('displayProductComparison',$params);
	}
	public function hookDisplayShoppingCart($params)
	{
		return $this->hookCommon('displayShoppingCart',$params);
	}
	public function hookDisplayShoppingCartFooter($params)
	{
		return $this->hookCommon('displayShoppingCartFooter',$params);
	}
	public function hookDisplayTopColumn($params)
	{
		return $this->hookCommon('displayTopColumn',$params);
	}
	public function hookDisplayProductListFunctionalButtons($params)
	{
		return $this->hookCommon('displayProductListFunctionalButtons',$params);
	}
	public function hookDisplayPDFInvoice($params)
	{
		return $this->hookCommon('displayPDFInvoice',$params);
	}
	public function hookDisplayInvoice($params)
	{
		return $this->hookCommon('displayInvoice',$params);
	}
	public function hookDisplayNav($params)
	{
		return $this->hookCommon('displayNav',$params);
	}
	public function hookDisplayMyAccountBlockFooter($params)
	{
		return $this->hookCommon('displayMyAccountBlockFooter',$params);
	}
	public function hookDisplayHomeTab($params)
	{
		return $this->hookCommon('displayHomeTab',$params);
	}
	public function hookDisplayHomeTabContent($params)
	{
		return $this->hookCommon('displayHomeTabContent',$params);
	}

}
