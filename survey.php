<?php


// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');

require_once JPATH_SITE . '/components/com_fabrik/models/element.php';

/**
 * Plugin element to rate elements of list
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.element.survey
 * @since       3.0
 */
class PlgFabrik_ElementSurvey extends PlgFabrik_ElementList
{
	/**
	 * States the element should be ignored from advanced search all queries.
	 *
	 * @var bool  True, ignore in advanced search all.
	 */
	protected $ignoreSearchAllDefault = false;

	protected $canSurvey = null;

	/**
	 * Formid - override for comments plugin
	 *
	 * @var int
	 */
	public $formId = null;

	/**
	 * List id - override for comments plugin
	 *
	 * @var int
	 */
	public $listId = null;

	/**
	 * Reference for comments plugin
	 *
	 * @var string
	 */
	public $special = null;

    public function isJoin()
    {
        return true;
    }

    protected function buildQueryElementConcatId()
    {
        //$str        = parent::buildQueryElementConcatId();
        $joinTable  = $this->getJoinModel()->getJoin()->table_join;
        $parentKey  = $this->buildQueryParentKey();
        $fullElName = $this->_db->qn($this->getFullName(true, false) . '_id');
        $str = "(SELECT GROUP_CONCAT(" . $this->element->name . " SEPARATOR '" . GROUPSPLITTER . "') FROM $joinTable WHERE " . $joinTable
            . ".parent_id = " . $parentKey . ") AS $fullElName";

        return $str;
    }

    protected function buildQueryParentKey()
    {
        $item      = $this->getListModel()->getTable();
        $parentKey = $item->db_primary_key;

        if ($this->isJoin())
        {
            $groupModel = $this->getGroupModel();

            if ($groupModel->isJoin())
            {
                // Need to set the joinTable to be the group's table
                $groupJoin = $groupModel->getJoinModel();
                $parentKey = $groupJoin->getJoin()->params->get('pk');
            }
        }

        return $parentKey;
    }

    protected function canSurvey () {
        $params = $this->getParams();

        $gid           = (int) $params->get('survey_access')[0];
        $this->canSurvey = in_array($gid, $this->user->getAuthorisedViewLevels());

        return $this->canSurvey;
    }


	public function getSubOptionsValues() {
		$params = $this->getParams();

		return $params["sub_options"]->sub_values;
	}

	public function getSubOptionsLabels() {
		$params = $this->getParams();

		return $params["sub_options"]->sub_labels;
	}

	public function getNumValue($table, $values, $rowId, $elementName) {
		$db = JFactory::getDbo();
		$result = array();

		if (($values) && ($rowId))
		{
			foreach ($values as $value)
			{
				$query = $db->getQuery(true);
				$query->select("id")->from($table)->where("parent_id = " . $rowId . " AND {$elementName} = '" . $value . "'");
				$db->setQuery($query);
				$r        = $db->loadAssocList();
				$result[] = count($r);
			}
		}
		return $result;
	}

	/**
	 * Shows the data formatted for the list view
	 *
	 * @param   string    $data      Elements data
	 * @param   stdClass  &$thisRow  All the data in the lists current row
	 * @param   array     $opts      Rendering options
	 *
	 * @return  string	formatted value
	 */
	public function renderListData($data, stdClass &$thisRow, $opts = array())
	{
        $profiler = JProfiler::getInstance('Application');
        JDEBUG ? $profiler->mark("renderListData: {$this->element->plugin}: start: {$this->element->name}") : null;

        $input = $this->app->input;

		$data = FabrikWorker::JSONtoData($data, true);
		$listId = $this->getlistModel()->getTable()->id;
		$elementId = $this->getElement()->id;
		$formModel = $this->getFormModel();
		$formId = $formModel->getId();
		$rowId = $thisRow->__pk_val;
		$joinTable = $this->getJoinModel()->getJoin()->table_join;


		if ($this->user->get('id') == 0)
		{
			$userId = $this->getCookieName($listId);
		}
		else
		{
			$userId = $this->user->get('id');
		}

		if (empty($data))
		{
			$data = array(0);
		}

		$aux = array();
		$aux[] = $data[0];
		$data = $aux;

		for ($i = 0; $i < count($data); $i++)
		{
			$input->set('rowid', $rowId);
			$layout                   = $this->getLayout('list');
			$layoutData               = new stdClass;
			$layoutData->formId       = $formId;
			$layoutData->rowId        = $rowId;
			$layoutData->label        = $this->getSubOptionsLabels();
			$layoutData->values       = $this->getSubOptionsValues();
			$layoutData->numValues    = $this->getNumValue($joinTable, $this->getSubOptionsValues(), $rowId, $this->element->name);
			$layoutData->existsRating = $this->existsRating($userId, $joinTable, $rowId, $this->element->name);
			$layoutData->tmpl         = isset ($this->tmpl) ? $this->tmpl : '';
			$data[$i]                 = $layout->render($layoutData);
		}

		$data = json_encode($data);

		return parent::renderListData($data, $thisRow, $opts);
	}

	public function render($data, $repeatCounter = 0)
	{
        $name = $this->getHTMLName($repeatCounter);
        $id = $this->getHTMLId($repeatCounter);
        $input = $this->app->input;
        $j3 = FabrikWorker::j3();

        if ($input->get("view") == 'details') {
            $layout = $this->getLayout('form');
            $layoutData = new stdClass;
            $layoutData->j3 = $j3;
            $layoutData->name = $name;
            $layoutData->id = $id;
            $layoutData->tmpl = isset ($this->tmpl) ? $this->tmpl : '';
            $layoutData->labels = $this->getSubOptionsLabels();
            $layoutData->numValues = $this->getNumValue($this->getJoinModel()->getJoin()->table_join, $this->getSubOptionsValues(), $this->getFormModel()->getRowId(), $this->element->name);
            return $layout->render($layoutData);
        }
        else {
            return FText::_('PLG_ELEMENT_SURVEY_MSG_FORM');
        }

	}

	public function existsRating ($userId, $table, $rowId, $elementName) {
		$db = FabrikWorker::getDbo();
		$r=0;

		if (($userId) && ($rowId) && ($table))
		{
			$query = $db->getQuery(true);
			$query->select("{$elementName}, params");
			$query->from($table);
			$query->where("parent_id = {$rowId}");
			$db->setQuery($query);
			$result = $db->loadAssocList();
		}

		if ($result) {
            foreach ($result as $item) {
                if (json_decode($item['params'])->user_id === $userId) {
                    $r = $item[$elementName];
                }
            }
        }

		return $r;
	}

	/**
	 * Called via widget ajax, stores the selected thumb
	 * stores the diff (thumbs-up minus thumbs-down)
	 *
	 * @return  number  The new count for up and down
	 */
	public function onAjax_rate()
	{
		$input = $this->app->input;
		$values = $input->get("values");

		$userId = $input->get("userid");
		$listId = $input->get("listid");
		$formId = $input->get("formid");
		$rowId = $input->get("rowId");
		$value = $input->get("value");
		$labels = $input->get("labels");
		$date = $this->date->toSql();
		$elementId = $input->get("elementname");
		$element_name = $input->get("element_name");
		$table_name = $input->get("table_name");
		$exists = $input->get("exists");


		if ($exists === '1') {
			$this->deleteRating($userId, $table_name, $rowId, $value, $element_name);
		}
		else
		{
            $this->deleteRating($userId, $table_name, $rowId, $value, $element_name);
			$this->insertRating($userId, $table_name, $rowId, $value, $element_name);
		}

		$newValues = $this->getNumValue($table_name, $values, $rowId, $element_name);

		echo json_encode($newValues);
	}

	/**
	 * Get the cookie name
	 *
	 * @param   int     $listId  List id
	 * @param   string  $rowId  Row id
	 *
	 * @return  string
	 */
	private function getCookieName($listId)
	{
		$cookieName = 'survey-table_' . $listId . '_ip_' . FabrikString::filteredIp();
		jimport('joomla.utilities.utility');
		$version = new JVersion;

		if (version_compare($version->RELEASE, '3.1', '>'))
		{
			return JApplicationHelper::getHash($cookieName);
		}
		else
		{
			return JApplication::getHash($cookieName);
		}
	}

	public function insertRating ($userId, $table, $rowId, $value, $elementName) {
		$params = new stdClass();
		$params->user_id = $userId;

	    $insert = array();
		$insert['id'] = 0;
		$insert['parent_id'] = $rowId;
		$insert[$elementName] = $value;
		$insert['params'] = json_encode($params);
	    $insert = (Object) $insert;

	    JFactory::getDbo()->insertObject($table, $insert, 'id');
	}

	public function deleteRating ($userId, $table, $rowId, $value, $elementName) {
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);
		$query->select("id, params")->from($table)->where("parent_id = {$rowId}");
		$db->setQuery($query);
		$result = $db->loadAssocList();

		$id = 0;
		if ($result) {
		    foreach ($result as $item) {
		        if (json_decode($item['params'])->user_id === $userId) {
		            $id = $item['id'];
                }
            }
		    if ($id !== 0) {
		        $query = $db->getQuery(true);
		        $query->delete($table)->where("id = {$id}");
		        $db->setQuery($query);
		        $db->execute();
            }
        }
	}

    public function elementListJavascript()
	{
		$params = $this->getParams();
		$id = $this->getHTMLId();
		$formModel = $this->getFormModel();
		$rowId = $formModel->getRowId();
		$list = $this->getlistModel()->getTable();
		$formId = $list->form_id;
		$elementId = $this->getElement()->id;
		$listMyThumbs = array();
		$idFromCookie = null;
		$data = $this->getListModel()->getData();
		$groupKeys = array_keys($data);

        $canSurvey = $this->canSurvey();

		if ($this->user->get('id') == 0)
		{
			$userId = $this->getCookieName($list->id );
		}
		else
		{
			$userId = $this->user->get('id');
		}

		$this->lang->load('plg_fabrik_element_thumbs', JPATH_BASE . '/plugns/fabrik_element/thumbs');

		$opts = new stdClass;
		$opts->canUse = $this->canUse();
		$opts->noAccessMsg = FText::_($params->get('thumbs_no_access_msg', FText::_('PLG_ELEMENT_THUMBS_NO_ACCESS_MSG_DEFAULT')));
		$opts->listid = $list->id;
		$opts->formid = $this->getFormModel()->getId();
		$opts->imagepath = COM_FABRIK_LIVESITE . 'plugins/fabrik_element/thumbs/images/';
		$opts->elid = $this->getElement()->id;
		$opts->myThumbs = $listMyThumbs;
		$opts->userid = $userId;
		$opts->renderContext = $this->getListModel()->getRenderContext();
		$opts->canSurvey = $canSurvey;

		$opts->values = $this->getSubOptionsValues();
		$opts->labels = $this->getSubOptionsLabels();
		$opts->rowId = $this->app->input->get('rowid');
		$opts->table_name = $this->getJoinModel()->getJoin()->table_join;
        $opts->numValues = $this->getNumValue($opts->table_name, $opts->values, $opts->rowId, $this->element->name);
        $opts->element_name = $this->getElement()->name;
        $opts->exists = $this->existsRating($userId, $opts->table_name, $opts->rowId, $opts->element_name);

		$opts = json_encode($opts);

		return "new FbSurveyList('$id', $opts);\n";
	}

	/**
	 * Used by radio and dropdown elements to get a dropdown list of their unique
	 * unique values OR all options - based on filter_build_method
	 *
	 * @param   bool    $normal     do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  table name to use - defaults to element's current table
	 * @param   string  $label      field to use, defaults to element name
	 * @param   string  $id         field to use, defaults to element name
	 * @param   bool    $incjoin    include join
	 *
	 * @return  array  text/value objects
	 */

	public function filterValueList($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		return $this->filterValueList_All($normal, $tableName, $label, $id, $incjoin);
	}

	/**
	 * Create an array of label/values which will be used to populate the elements filter dropdown
	 * returns all possible options
	 *
	 * @param   bool    $normal     do we render as a normal filter or as an advanced search filter
	 * @param   string  $tableName  table name to use - defaults to element's current table
	 * @param   string  $label      field to use, defaults to element name
	 * @param   string  $id         field to use, defaults to element name
	 * @param   bool    $incjoin    include join
	 *
	 * @return  array	filter value and labels
	 */

	protected function filterValueList_All($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
        $labels = $this->getSubOptionsLabels();
        $values = $this->getSubOptionsValues();

		for ($i = 0; $i < count($values); $i++)
		{
			$return[] = JHTML::_('select.option',$values[$i], $labels[$i]);
		}

		return $return;
	}

    public function getFilterQuery($key, $condition, $value, $originalValue, $type = 'normal', $evalFilter = '0')
    {
        $condition = JString::strtoupper($condition);
        $this->encryptFieldName($key);
        $str = $this->filterQueryMultiValues($key, $condition, $originalValue, $evalFilter, $type);
        return $str;
    }
}
