<?php
if (!defined('XOOPS_ROOT_PATH')) exit();
/**
 * 汎用テーブル操作XoopsObject
 * 
 * @copyright copyright (c) 2000-2003 Kowa.ORG
 * @author Nobuki Kowa <Nobuki@Kowa.ORG> 
 * @package XoopsTableObject
 */
include_once XOOPS_ROOT_PATH."/class/xoopsobject.php";
include_once XOOPS_ROOT_PATH.'/modules/'.basename(dirname(dirname(__FILE__))).'/class/gtickets.php';

if (!defined('XOBJ_DTYPE_FLOAT')) define('XOBJ_DTYPE_FLOAT', 101);
if (!defined('XOBJ_VCLASS_TFIELD')) define('XOBJ_VCLASS_TFIELD', 1);
if (!defined('XOBJ_VCLASS_ATTRIB')) define('XOBJ_VCLASS_ATTRIB', 2);
if (!defined('XOBJ_VCLASS_EXTRA')) define('XOBJ_VCLASS_EXTRA', 3);

if (!defined('XMTO_PREFIX')) define('XMTO_PREFIX', 'xo_');
if (!defined('XMTO_FORM_TYPE_NEW')) define('XMTO_FORM_TYPE_NEW', 'new');
if (!defined('XMTO_FORM_TYPE_EDIT')) define('XMTO_FORM_TYPE_EDIT', 'edit');
if (!defined('XMTO_FORM_TYPE_DELETE')) define('XMTO_FORM_TYPE_DELETE', 'delete');
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileTableObject extends XoopsObject
{
	var $_extra_vars = array();
	var $_keys;
	var $_autoIncrement;
	var $_formElements;
	var $_listTableElements;
	var $_handler;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function initVar($key, $data_type, $value = null, $required = false, $maxlength = null, $options = '')
	{
		parent::initVar($key, $data_type, $value, $required, $maxlength, $options);
		$this->vars[$key]['var_class'] = XOBJ_VCLASS_TFIELD;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setAttribute($key, $value)
	{
		$this->vars[$key] = array('value' => $value, 'required' => false, 'data_type' => XOBJ_DTYPE_OTHER, 'maxlength' => null, 'changed' => false, 'options' => '');
		$this->vars[$key]['var_class'] = XOBJ_VCLASS_ATTRIB;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setKeyFields($keys)
	{
		$this->_keys = $keys;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getKeyFields()
	{
		return $this->_keys;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function isKey($field)
	{
		return in_array($field,$this->_keys);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function cacheKey()
	{
		$recordKeys = $this->getKeyFields();
		$recordVars = $this->getVars();
		$cacheKey = array();
		foreach ($this->getKeyFields() as $k => $v) {
			$cacheKey[$v] = $this->getVar($v);
		}
		return(serialize($cacheKey));
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//AUTO_INCREMENT属性のフィールドはテーブルに一つしかない前提
	function setAutoIncrementField($fieldName)
	{
		$this->_autoIncrement = $fieldName;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function &getAutoIncrementField()
	{
		return $this->_autoIncrement;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function isAutoIncrement($fieldName)
	{
		return ($fieldName == $this->_autoIncrement);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function resetChenged()
	{
		foreach($this->vars as $k=>$v) {
			$this->vars[$k]['changed'] = false;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function assignVar($key, $value)
	{
		if (isset($value) && isset($this->vars[$key])) {
			$this->vars[$key]['value'] =& $value;
		} else {
			$this->setExtraVar($key, $value);
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function &getExtraVar($key)
	{
		return $this->_extra_vars[$key];
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setExtraVar($key, $value)
	{
		$this->_extra_vars[$key] =& $value;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	* assign a value to a variable
	* 
	* @access public
	* @param string $key name of the variable to assign
	* @param mixed $value value to assign
	* @param bool $not_gpc
	*/
	function setVar($key, $value, $not_gpc = false)
	{
		if (!empty($key) && isset($this->vars[$key])) {
			$this->vars[$key]['value'] =& $value;
			$this->vars[$key]['not_gpc'] = $not_gpc;
			$this->vars[$key]['changed'] = true;
			$this->setDirty();
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	* returns a specific variable for the object in a proper format
	* 
	* @access public
	* @param string $key key of the object's variable to be returned
	* @param string $format format to use for the output
	* @return mixed formatted value of the variable
	*/
	function &getVar($key, $format = 's')
	{
		$ret =& parent::getVar($key, $format);
		if ($this->vars[$key]['data_type'] == XOBJ_DTYPE_TXTAREA && ($format=='e' || $format=='edit')) {
			$ret = preg_replace("/&amp;(#[0-9]+;)/i", '&$1', $ret);
		}
		return $ret;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function cleanVars()
	{
		$iret =parent::cleanVars();
		foreach ($this->vars as $k => $v) {
			$cleanv = $v['value'];
			if (!$v['changed']) {
			} else {
				$cleanv = is_string($cleanv) ? trim($cleanv) : $cleanv;
				switch ($v['data_type']) {
					case XOBJ_DTYPE_FLOAT:
						$cleanv = (float)($cleanv);
						break;
					default:
						break;
				}
				//個別の変数チェックがあれば実行;
				$checkMethod = 'checkVar_'.$k;
				if(method_exists($this, $checkMethod)) {
					$this->$checkMethod($cleanv);
				}
			}
			$this->cleanVars[$k] =& $cleanv;
			unset($cleanv);
		}
		if (count($this->_errors) > 0) {
			return false;
		}
		$this->unsetDirty();
		return true;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function &getVarArray($type='s')
	{
		$varArray=array();
		foreach ($this->vars as $k => $v) {
			$varArray[$k]=$this->getVar($k,$type);
		}
		return $varArray;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getFormCaption()
	{
		return $this->_formCaption;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function initFormElements($type)
	{
		switch ($type)
		{
			case XMTO_FORM_TYPE_NEW:
				$this->initNewFormElements();
				break;

			case XMTO_FORM_TYPE_EDIT:
				$this->initEditFormElements();
				break;

			case XMTO_FORM_TYPE_DELETE:
				$this->initDeleteFormElements();
				break;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function initNewFormElements()
	{
// example:
//		assignEditFormElement($key, $elementParams(array($name, $caption, )));

		$this->_formCaption = _MD_XMOBILE_POSTNEW;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function initEditFormElements()
	{
		$this->_formCaption = _EDIT;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function initDeleteFormElements()
	{
		$this->_formCaption = _DELETE;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function assignFormElement($key, $elementParams='')
	{
		if (is_array($elementParams))
		{
			$key = htmlspecialchars($key, ENT_QUOTES);

			if (array_key_exists('type',$elementParams))
			{
				$this->_formElements[$key]['type'] = htmlspecialchars($elementParams['type'], ENT_QUOTES);
			}
			if (array_key_exists('caption',$elementParams))
			{
				$this->_formElements[$key]['caption'] = htmlspecialchars($elementParams['caption'], ENT_QUOTES);
			}
			else
			{
				$this->_formElements[$key]['caption'] = '';
			}

			if (array_key_exists('value',$elementParams))
			{
				$this->_formElements[$key]['value'] = htmlspecialchars($elementParams['value'], ENT_QUOTES);
			}

			if (array_key_exists('params',$elementParams))
			{
				$this->_formElements[$key]['params'] = $elementParams['params'];
			}
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	function renderFormElements($formCaption='', $formName='edit_form', $formAction='')
	function renderFormElements($formAction='', $formCaption='', $formName='edit_form')
	{
		$myts =& MyTextSanitizer::getInstance();

		$postForm = '';
		$postForm .= $formCaption.'<hr />';
		$postForm .= '<form action="'.$formAction.'" name="'.$formName.'" method="post">';
		$postForm .= $this->makeTicket();

		$var_arr =& $this->getVars();
		$var_arr_keys =& array_keys($var_arr);

		foreach($this->_formElements as $key=>$formElement)
		{
			$name = $key;
			$type = $formElement['type'];
			$caption = $formElement['caption'];
			$value = '';
			if (array_key_exists('value', $formElement))
			{
				$value = $formElement['value'];
			}
			if (in_array($key, $var_arr_keys))
			{
				$name = XMTO_PREFIX.$key;
				$value =& $this->getVar($key, 'e');
			}

			switch ($type)
			{
				case 'label':
					$postForm .= $this->makeInputLabel($caption,$name,$value);
					break;

				case 'hidden':
					$postForm .= $this->makeInputHidden($name,$value);
					break;

				case 'text':
					$postForm .= $this->makeInputText($caption,$name,$value,$formElement['params']);
					break;

				case 'textarea':
					$postForm .= $this->makeInputTextArea($caption,$name,$value,$formElement['params']);
					break;

				case 'checkbox':
					$postForm .= $this->makeInputCheckBox($caption,$name,$value,$formElement['params']);
					break;

				case 'radio':
					$postForm .= $this->makeInputRadio($caption,$name,$value,$formElement['params']);
					break;

				case 'select':
					$postForm .= $this-> makeInputSelect($caption,$name,$value,$formElement['params']);
					break;

				case 'dateselect':
					$postForm .= $this->makeInputDateSelect($caption,$name,$value,$formElement['params']);
					break;

				case 'submit':
					$postForm .= $this->makeButtonSubmit();
					break;

				case 'cancel':
					$postForm .= $this->makeButtonCancel();
					break;

				case 'button':
					$postForm .= $this->makeButton($name,$value);
					break;

			}
		}

		$postForm .= $this->makeButtonSubmit().'&nbsp;';
		$postForm .= $this->makeButtonCancel();

		$postForm .= '</form>';

		return $postForm;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function _checkToken()
	{
		$ticket = new XoopsGTicket;
		if ($ticket->check(true,'',false))
		{
			return true;
		}
		else
		{
//			$ticket->getErrors();
			return false;
//			return _MD_XMOBILE_TICKET_ERROR;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// not use
	/**
	 * check token
	 * @access private
	 * @return bool
	 */
/*
	function _checkToken()
	{
		if (class_exists('XoopsMultiTokenHandler'))
		{
			$tokenhandler = new XoopsMultiTokenHandler();
			$ret = $tokenhandler->autoValidate($this->_entityClassName.'_edit');
		}
		return $ret;
	}
*/
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function makeInputHidden($name,$value)
	{
		$formElement = '<input type="hidden" name="'.$name.'" value="'.$value.'" />';
		return $formElement;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function makeInputLabel($caption,$name,$value)
	{
		$formElement = '';
		$formElement .= $caption.'<br />';
		$formElement .= $value.'<br />';
		return $formElement;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function makeInputText($caption,$name,$value,$params)
	{
		$formElement = '';
		$formElement .= $caption.'<br />';

		$size = intval($params['size']);
		$maxlength = intval($params['maxlength']);
		$formElement .= '<input type="text" name="'.$name.'" value="'.$value.'" size="'.$size.'" maxlength="'.$maxlength.'" /><br />';
		return $formElement;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function makeInputTextArea($caption,$name,$value)
	{
		global $xoopsModuleConfig;

		$tarea_cols = $xoopsModuleConfig['tarea_cols'];
		$tarea_rows = $xoopsModuleConfig['tarea_rows'];

		$formElement = '';
		$formElement .= $caption.'<br />';
		$formElement .= '<textarea name="'.$name.'" cols="'.$tarea_cols.'" rows="'.$tarea_rows.'">'.$value.'</textarea><br />';
		return $formElement;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function makeInputCheckBox($caption,$name,$value,$params)
	{
		$formElement = '';
		$formElement .= $caption.'<br />';

		foreach($params as $id => $title)
		{
			$checked = '';
			if ($id == $value)
			{
				$checked = ' checked="checked"';
			}
			$formElement .= '<input type="checkbox" name="'.$name.'[]" value="'.intval($id).'"'.$checked.' />'.htmlspecialchars($title, ENT_QUOTES).'<br />';
		}
		return $formElement;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function makeInputRadio($caption,$name,$value,$params)
	{
		$formElement = '';
		$formElement .= $caption.'<br />';

		foreach($params as $id => $title)
		{
			$checked = '';
			if ($id == $value)
			{
				$checked = ' checked="checked"';
			}
			$formElement .= '<input type="radio" name="'.$name.'" value="'.intval($id).'"'.$checked.' />'.htmlspecialchars($title, ENT_QUOTES).'<br />';
		}
		return $formElement;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function makeInputSelect($caption,$name,$value,$params)
	{

		$formElement = '';
		$formElement .= $caption.'<br />';
		$formElement .= '<select name="'.$name.'">';

		foreach($params as $id => $title)
		{
			$sel = '';
			if ($id == $value)
			{
				$sel = ' selected="selected"';
			}
			$formElement .= '<option value="'.intval($id).'"'.$sel.'>'.htmlspecialchars($title, ENT_QUOTES).'</option>';
		}
		$formElement .= '</select><br />';
		return $formElement;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// not use
/*
	function makeInputDateSelect($value=null)
	{
		if (is_null($value))
		{
			$value = time();
		}

		$year = formatTimestamp('Y',intval($value));
		$month = strftime('m',intval($value));
		$day = strftime('d',intval($value));

		$formElement = '';
		$formElement.= '<select name="year">';
		for($ii=2004; $ii<=2008; $ii++)
		{
			$temp = '';
			if ($ii == $year)
			{
			$temp = 'selected';
			}
			$formElement.='<option value="'.$ii.'"'.$temp.'>'.$ii.'</option>';
		}
		$formElement.= '</select>';
		$formElement.= '<select name="month">';
		for($ii=1; $ii<=12; $ii++)
		{
			$temp = '';
			if ($ii == $month){
			$temp = 'selected';
			}
			$formElement.='<option value="'.$ii.'" '.$temp.'>'.$ii.'</option>';
		}
		$formElement.= '</select>';
		$formElement.= '<select name="date">';

		if ($date)
		{
			for($ii=1; $ii<=31; $ii++)
			{
				$temp = '';
				if ($ii == $day)
				{
					$temp = 'selected';
				}
				$formElement.='<option value="'.$ii.'" '.$temp.'>'.$ii.'</option>';
			}
			$formElement.= '</select><br />';
		}
		return $formElement;
	}
*/
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function makeButtonSubmit($name='submit',$value=_SUBMIT)
	{
		$formElement = '<input type="submit" name="'.$name.'" value="'.$value.'" />';
		return $formElement;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function makeButtonCancel($name='cancel',$value=_CANCEL)
	{
//		$formElement = '<input type="button" name="cancel" value="'._CANCEL.'" onClick="location=\''.$element['target_url'].'\'" />';
		$formElement = '<input type="submit" name="'.$name.'" value="'.$value.'" />';
		return $formElement;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function makeButton($name,$value)
	{
		$formElement = '<input type="button" name="'.$name.'" value="'.$value.'" />';
		return $formElement;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function makeTicket()
	{
		$ticket = new XoopsGTicket;
		$formElement = $ticket->getTicketHtml();
		return $formElement;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// not use
/*
	function makeXoopsTicket()
	{
		$tokenhandler = new XoopsMultiTokenHandler();
		$ticket =& $tokenhandler->create(get_class($this).'_edit', 600);
		$formElement = $ticket->getHtml();
		return $formElement;
	}
*/
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}




/////////////////////////////////////////////////////////////////////////////////////////////////////////////
class XmobileTableObjectHandler extends XoopsObjectHandler
{
	var $tableName;
	var $useFullCache;
	var $cacheLimit;
	var $_entityClassName;
	var $_errors;
	var $_fullCached;
	var $_sql;
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function XmobileTableObjectHandler($db)
	{
		$this->_entityClassName = preg_replace("/handler$/i","", get_class($this));
		$this->XoopsObjectHandler($db);
		$this->_errors = array();
		$this->useFullCache = true;
		$this->cacheLimit = 0;
		$this->_fullCached = false;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function renderForm(&$object, $action, $type='new')
	{
		$this->_record =& $object;

		if (is_object($this->_record))
		{
			$this->_record->initFormElements($type);
			return $this->_record->renderFormElements($action, $this->_record->getFormCaption());
		}
		else
		{
			return false;
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getErrors($html=true, $clear=true)
	{
		$error_str = "";
		$delim = $html ? "<br />\n" : "\n";
		if (count($this->_errors)) {
			$error_str = implode($delim, $this->_errors);
		}
		if ($clear) {
			$this->_errors = array();
		}
		return $error_str;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function setError($error_str)
	{
		$this->_errors[] = $error_str;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * レコードオブジェクトの生成
	 * 
	 * @param	boolean $isNew 新規レコード設定フラグ
	 * 
	 * @return	object  {@link XoopsTableObject}
	 */
	function &create($isNew = true)
	{
		$record = new $this->_entityClassName;
		if ($isNew) {
			$record->setNew();
		}
		$record->_handler =& $this;
		return $record;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * レコードの取得(プライマリーキーによる一意検索）
	 * 
	 * @param	mixed $key 検索キー
	 * 
	 * @return	object  {@link XoopsTableObject}, FALSE on fail
	 */
	function &get($keys)
	{
		$result = false;
		$record =& $this->create(false);
		$recordKeys = $record->getKeyFields();
		$recordVars = $record->getVars();
		if (gettype($keys) != 'array')
		{
			if (count($recordKeys) == 1)
			{
				$keys = array($recordKeys[0] => $keys);
			}
			else
			{
				return false;
			}
		}
		$whereStr = "";
		$whereAnd = "";
		$cacheKey = array();
		foreach ($record->getKeyFields() as $k => $v)
		{
			if (array_key_exists($v, $keys))
			{
				$whereStr .= $whereAnd . "`$v` = ";
				if (($recordVars[$v]['data_type'] == XOBJ_DTYPE_INT) || ($recordVars[$v]['data_type'] == XOBJ_DTYPE_FLOAT))
				{
					$whereStr .= $keys[$v];
				}
				else
				{
					$whereStr .= $this->db->quoteString($keys[$v]);
				}
				$whereAnd = " AND ";
				$cacheKey[$v] = $keys[$v];
			}
			else
			{
				return $result;
			}
		}
		$sql = sprintf("SELECT * FROM %s WHERE %s",$this->tableName, $whereStr);

		if (!$result =& $this->query($sql))
		{
			return $result;
		}
		$numrows = $this->db->getRowsNum($result);
		if ($numrows == 1)
		{
			$row = $this->db->fetchArray($result);
			$record->assignVars($row);
			$result =& $record;
			return $result;
		}
		unset($record);
		$result = false;
		return $result;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	* レコードの保存
	* 
	* @param	object	&$record	{@link XoopsTableObject} object
	* @param	bool	$force		POSTメソッド以外で強制更新する場合はture
	* 
	* @return	bool    成功の時は TRUE
	*/
	function insert(&$record,$force=false,$updateOnlyChanged=false)
	{
		if ( get_class($record) != $this->_entityClassName ) {
			return false;
		}
		if ( !$record->isDirty() ) {
			return true;
		}
		if (!$record->cleanVars()) {
			$this->_errors += $record->getErrors();
			return false;
		}
		$vars = $record->getVars();
		$cacheRow = array();
		if ($record->isNew()) {
			$fieldList = "(";
			$valueList = "(";
			$delim = "";
			foreach ($record->cleanVars as $k => $v) {
				if ($vars[$k]['var_class'] != XOBJ_VCLASS_TFIELD) {
					continue;
				}
				$fieldList .= $delim ."`$k`";
				if ($record->isAutoIncrement($k)) {
					$v = $this->getAutoIncrementValue();
				}
				if (preg_match("/^__MySqlFunc__/", $v)) {  // for value using MySQL function.
					$value = preg_replace('/^__MySqlFunc__/', '', $v);
				} elseif ($vars[$k]['data_type'] == XOBJ_DTYPE_INT) {
					if (!is_null($v)) {
						$v = intval($v);
						$v = ($v) ? $v : 0;
						$valueList .= $delim . $v;
					} else {
						$valueList .= $delim . 'null';
					}
				} elseif ($vars[$k]['data_type'] == XOBJ_DTYPE_FLOAT) {
					if (!is_null($v)) {
						$v = (float)($v);
						$v = ($v) ? $v : 0;
						$valueList .= $delim . $v;
					} else {
						$valueList .= $delim . 'null';
					}
				} else {
					$valueList .= $delim . $this->db->quoteString($v);
				}
				$cacheRow[$k] = $v;
				$delim = ", ";
			}
			$fieldList .= ")";
			$valueList .= ")";
			$sql = sprintf("INSERT INTO %s %s VALUES %s", $this->tableName,$fieldList,$valueList);
		} else {
			$setList = "";
			$setDelim = "";
			$whereList = "";
			$whereDelim = "";
			foreach ($record->cleanVars as $k => $v) {
				if ($vars[$k]['var_class'] != XOBJ_VCLASS_TFIELD) {
					continue;
				}
				if (preg_match("/^__MySqlFunc__/", $v)) {  // for value using MySQL function.
					$value = preg_replace('/^__MySqlFunc__/', '', $v);
				} elseif ($vars[$k]['data_type'] == XOBJ_DTYPE_INT) {
					$v = intval($v);
					$value = ($v) ? $v : 0;
				} elseif ($vars[$k]['data_type'] == XOBJ_DTYPE_FLOAT) {
					$v = (float)($v);
					$value = ($v) ? $v : 0;
				} else {
					$value = $this->db->quoteString($v);
				}

				if ($record->isKey($k)) {
					$whereList .= $whereDelim . "`$k` = ". $value;
					$whereDelim = " AND ";
				} else {
					if ($updateOnlyChanged && !$vars[$k]['changed']) {
						continue;
					}
					$setList .= $setDelim . "`$k` = ". $value . " ";
					$setDelim = ", ";
				}
				$cacheRow[$k] = $v;
			}
			if (!$setList) {
				$record->resetChenged();
				return true;
			}
			$sql = sprintf("UPDATE %s SET %s WHERE %s", $this->tableName, $setList, $whereList);
		}
		if (!$result =& $this->query($sql, $force)) {
			return false;
		}
		if ($record->isNew()) {
			$idField=$record->getAutoIncrementField();
			$idValue=$this->db->getInsertId();
			$record->assignVar($idField,$idValue);
			$cacheRow[$idField] = $idValue;
		}
//		if (!$updateOnlyChanged) {
//			$GLOBALS['_xoopsTableCache']->set($this->tableName, $record->cacheKey() ,$cacheRow, $this->cacheLimit);
//		} else {
//			$GLOBALS['_xoopsTableCache']->reset($this->tableName, $record->cacheKey());
//			$this->_fullCached = false;
//		}
		$record->resetChenged();
		return true;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function updateByField(&$record, $fieldName, $fieldValue)
	{
		$record->setVar($fieldName, $fieldValue);
		return $this->insert($record, true, true);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * レコードの削除
	 * 
	* @param	object  &$record  {@link XoopsTableObject} object
	* @param	bool	$force		POSTメソッド以外で強制更新する場合はture
	* 
	* @return	bool    成功の時は TRUE
	 */
	function delete(&$record,$force=false)
	{
		if ( get_class($record) != $this->_entityClassName ) {
			return false;
		}
		if (!$record->cleanVars()) {
			$this->_errors[] = $this->db->error();
			return false;
		}
		$vars = $record->getVars();
		$whereList = "";
		$whereDelim = "";
		foreach ($record->cleanVars as $k => $v) {
			if ($record->isKey($k)) {
				if (($vars[$k]['data_type'] == XOBJ_DTYPE_INT)||($vars[$k]['data_type'] == XOBJ_DTYPE_FLOAT)) {
					$value = $v;
				} else {
					$value = $this->db->quoteString($v);
				}
				$whereList .= $whereDelim . "`$k` = ". $value;
				$whereDelim = " AND ";
			}
		}
		$sql = sprintf("DELETE FROM %s WHERE %s", $this->tableName, $whereList);
		if (!$result =& $this->query($sql, $force)) {
			return false;
		}
//		$GLOBALS['_xoopsTableCache']->reset($this->tableName, $record->cacheKey());
		return true;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * テーブルの条件検索による複数レコード取得
	 * 
	 * @param	object	$criteria 	{@link XoopsTableObject} 検索条件
	 * @param	bool $id_as_key		プライマリーキーを、戻り配列のキーにする場合はtrue
	 * 
	 * @return	mixed Array			検索結果レコードの配列
	 */
	function &getObjects($criteria = null, $id_as_key = false, $fieldlist="", $distinct = false, $joindef = false)
	{
		$ret = array();
		$limit = $start = 0;
		$whereStr = '';
		$orderStr = '';
		if ($distinct) {
			$distinct = "DISTINCT ";
		} else {
			$distinct = "";
		}
		if ($fieldlist) {
			$sql = 'SELECT '.$distinct.$fieldlist.' FROM '.$this->tableName;
		} else {
			$sql = 'SELECT '.$distinct.'* FROM '.$this->tableName;
		}
		if ($joindef) {
			$sql .= $joindef->render($this->tableName);
		}
		if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
			$whereStr = $criteria->renderWhere();
			$sql .= ' '.$whereStr;
		}
		if (isset($criteria) && (is_subclass_of($criteria, 'criteriaelement')||get_class($criteria)=='criteriaelement')) {
			if ($criteria->getGroupby() != ' GROUP BY ') {
				$sql .= ' '.$criteria->getGroupby();
			}
			if ((is_array($criteria->getSort()) && count($criteria->getSort()) > 0)) {
				$orderStr = 'ORDER BY ';
				$orderDelim = "";
				foreach ($criteria->getSort() as $sortVar) {
					$orderStr .= $orderDelim . $sortVar.' '.$criteria->getOrder();
					$orderDelim = ",";
				}
				$sql .= ' '.$orderStr;
			} elseif ($criteria->getSort() != '') {
				$orderStr = 'ORDER BY '.$criteria->getSort().' '.$criteria->getOrder();
				$sql .= ' '.$orderStr;
			}
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		// debug
		$this->utils->setDebugMessage(__CLASS__, 'getObjects SQL', $sql);

		$result =& $this->query($sql, false ,$limit, $start);
		if (!$result) {
			return $ret;
		}
		if ((!$whereStr) && ($limit==0) && ($start ==0) && ($this->useFullCache) && ($this->cacheLimit==0)) {
			$this->_fullCached = true;
		}
		$records = array();
		while ($myrow = $this->db->fetchArray($result)) {
			$record =& $this->create(false);
			$record->assignVars($myrow);
			if (!$id_as_key) {
				$records[] =& $record;
			} else {
				$ids = $record->getKeyFields();
				$r =& $records;
				$count_ids = count($ids);
				for ($i=0; $i<$count_ids; $i++) {
					if ($i == $count_ids-1) {
						$r[$myrow[$ids[$i]]] =& $record;
					} else {
						if (!isset($r[$myrow[$ids[$i]]])) {
							$r[$myrow[$ids[$i]]] = array();
						}
						$r =& $r[$myrow[$ids[$i]]];
					}
				}
			}
			unset($record);
		}
		return $records;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * テーブルの条件検索による対象レコード件数
	 * 
	 * @param	object	$criteria 		{@link XoopsTableObject} 検索条件
	 * 
	 * @return	integer					検索結果レコードの件数
	 */
	function getCount($criteria = null)
	{
		$sql = 'SELECT COUNT(*) FROM '.$this->tableName;
		if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
			$sql .= ' '.$criteria->renderWhere();
		}
		$result =& $this->query($sql);
		if (!$result) {
			return 0;
		}
		list($count) = $this->db->fetchRow($result);
		return $count;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * テーブルの条件検索による複数レコード一括更新(対象フィールドは一つのみ)
	 * 
	 * @param	string	$fieldname 	更新フィールド名
	 * @param	mixed	$fieldvalue	更新値
	 * @param	object	$criteria 	{@link XoopsTableObject} 検索条件
	 * @param	bool	$force		POSTメソッド以外で強制更新する場合はture
	 * 
	 * @return	mixed Array			検索結果レコードの配列
	 */
	function updateAll($fieldname, $fieldvalue, $criteria = null, $force=false)
	{
		$record = $this->create();
		if ($record->vars[$fieldname]['data_type'] == XOBJ_DTYPE_INT) {
			$fieldvalue = intval($fieldvalue);
			$fieldvalue = ($fieldvalue) ? $fieldvalue : 0;
		} elseif ($record->vars[$fieldname]['data_type'] == XOBJ_DTYPE_FLOAT) {
			$fieldvalue = (float)($fieldvalue);
			$fieldvalue = ($fieldvalue) ? $fieldvalue : 0;
		} else {
			$fieldvalue = $this->db->quoteString($fieldvalue);
		}
		$set_clause = $fieldname.' = '.$fieldvalue;
		$sql = 'UPDATE '.$this->tableName.' SET '.$set_clause;
		if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
		$sql .= ' '.$criteria->renderWhere();
		}
		if (!$result =& $this->query($sql, $force)) {
			return false;
		}
		//キャッシュのクリア(更新されたレコード再取得の方がコストが高そうなので)
//		$GLOBALS['_xoopsTableCache']->clear($this->tableName);
//		$this->_fullCached = false;
		return true;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * テーブルの条件検索による複数レコード削除
	 * 
	 * @param	object	$criteria 	{@link XoopsTableObject} 検索条件
	 * @param	bool	$force		POSTメソッド以外で強制更新する場合はture
	 * 
	 * @return	bool    成功の時は TRUE
	 */
	function deleteAll($criteria = null, $force=false)
	{
		$sql = 'DELETE FROM '.$this->tableName;
		if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
			$sql .= ' '.$criteria->renderWhere();
		}
		if (!$result =& $this->query($sql, $force)) {
			return false;
		}
		//キャッシュのクリア(削除されたレコード再取得の方がコストが高そうなので)
//		$GLOBALS['_xoopsTableCache']->clear($this->tableName);
//		$this->_fullCached = false;
		return true;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getAutoIncrementValue()
	{
		return $this->db->genId(get_class($this).'_id_seq');
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function &query($sql, $force=false, $limit=0, $start=0) {
		if (empty($GLOBALS['_xoopsTableQueryCount'])) {
			$GLOBALS['_xoopsTableQueryCount'] = 1;
		} else {
			$GLOBALS['_xoopsTableQueryCount']++;
		}
		if (!empty($GLOBALS['wpdb'])) {
			$GLOBALS['wpdb']->querycount++;
		}
		if ($force) {
			$result =& $this->db->queryF($sql, $limit, $start);
		} else {
			$result =& $this->db->query($sql, $limit, $start);
		}
		$this->_sql = $sql;
		$this->_start = $start;
		$this->_limit = $limit;

		if (!$result) {
			$this->_errors[] = $this->db->error();
			$result = false;
		}
		return $result;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getLastSQL()
	{
		return $this->_sql;
	}
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>