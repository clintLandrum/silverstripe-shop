<?php

/**
 * Product Catalog Admin
 * @package shop
 * @subpackage cms
 **/
class ProductCatalogAdmin extends ModelAdmin {

	static $menu_priority = 2;

	public static $collection_controller_class = "ProductCatalogAdmin_CollectionController";
	public static $record_controller_class = "ProductCatalogAdmin_RecordController";
	public static $managed_models = array("Product", "ProductCategory","ProductVariation","ProductAttributeType");

	public static function set_managed_models(array $array) {
		self::$managed_models = $array;
	}

	public static $url_segment = 'products';

	public static $menu_title = 'Products';
	
	public static $model_importers = array(
		'Product' => 'ProductBulkLoader',
		'ProductCategory' => null,
		'ProductVariation' => null
	);

}

/**
 * Removes the import form "Empty Before Import" option.
 * @package shop
 * @subpackage cms
 */
class ProductCatalogAdmin_CollectionController extends ModelAdmin_CollectionController {
	
	 //note that these are called once for each $managed_models
	function ImportForm(){
		$form = parent::ImportForm();
		if($form){
			//EmptyBeforeImport checkbox does not appear to work for SiteTree objects, so removed for now
			$form->Fields()->removeByName('EmptyBeforeImport'); 
		}
		return $form;
	}
	
	function getResultsTable($searchCriteria){
		$table = parent::getResultsTable($searchCriteria);
		$table->setPermissions(array("view","edit"));
		return $table;
	}
	
}

/**
 * Modifies the record controller to disable some actions.
 * @package shop
 * @subpackage cms
 */
class ProductCatalogAdmin_RecordController extends ModelAdmin_RecordController{

	protected static $actions_to_keep = array(
		"Back",
		"doDelete",
		"doSave"
	);

	/**
	 * Returns a form for editing the attached model
	 */
	public function EditForm() {
		$form = parent::EditForm();
		$oldActions = $form->Actions();
		//in order of appearance
		//$form->unsetActionByName("action_doDelete");
		$form->unsetActionByName("action_unpublish");
		$form->unsetActionByName("action_delete");
		$form->unsetActionByName("action_save");
		$form->unsetActionByName("action_publish");
		//$form->unsetActionByName("action_doSave");
		$actions = $form->Actions();
		$actions->push(new FormAction("doGoto", "go to page"));
		$form->setActions($actions);
		return $form;
	}

	function doSave($data, $form, $request) {
		$form->saveInto($this->currentRecord);
		
		if($this->currentRecord instanceof SiteTree){
			$this->currentRecord->writeToStage("Stage");
			$this->currentRecord->publish("Stage", "Live"); //make sure products are published also
		}else{
			$this->currentRecord->write();
		}
		$this->currentRecord->flushCache();
		if(Director::is_ajax()) {
			return $this->edit($request);
		} else {
			Director::redirectBack();
		}
	}

	function doGoto($data, $form, $request) {
		Director::redirect($this->currentRecord->Link());
	}
	
	public function doDelete($data, $form, $request) {
		if($this->currentRecord->canDelete(Member::currentUser())) {
			$this->currentRecord->doUnpublish();
			$this->currentRecord->delete();
		}
		return "product deleted";
	}

}