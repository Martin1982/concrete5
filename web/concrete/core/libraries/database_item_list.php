<?
/**
*
* @package Utilities
*/
defined('C5_EXECUTE') or die("Access Denied.");

/**
 * Class Concrete5_Library_DatabaseItemList
 */
class Concrete5_Library_DatabaseItemList extends ItemList {

    /**
     * @var string
     */
    protected $query = '';
    /**
     * @var string
     */
    protected $userQuery = '';
    /**
     * @var bool
     */
    protected $debug = false;
    /**
     * @var array
     */
    protected $filters = array();
    /**
     * @var string
     */
    protected $sortByString = '';
    /**
     * @var string
     */
    protected $groupByString = '';
    /**
     * @var string
     */
    protected $havingString = '';
    /**
     * @var array
     */
    protected $autoSortColumns = array();
    /**
     * @var string
     */
    protected $userPostQuery = '';

    /**
     * @return int
     */
    public function getTotal() {
		if ($this->total == -1) {
			$db = Loader::db();
			$arr = $this->executeBase(); // returns an associated array of query/placeholder values				
			$r = $db->Execute($arr);
			$this->total = $r->NumRows();
		}		
		return $this->total;
	}

    /**
     * @param bool $dbg
     */
    public function debug($dbg = true) {
		$this->debug = $dbg;
	}

    /**
     * @param $query
     */
    protected function setQuery($query) {
		$this->query = $query . ' ';
	}

    /**
     * @return string
     */
    protected function getQuery() {
		return $this->query;
	}

    /**
     * @param $query
     */
    public function addToQuery($query) {
		$this->userQuery .= $query . ' ';
	}

    /**
     *
     */
    protected function setupAutoSort() {
		if (count($this->autoSortColumns) > 0) {
			$req = $this->getSearchRequest();
			if (in_array($req[$this->queryStringSortVariable], $this->autoSortColumns)) {
				$this->sortBy($req[$this->queryStringSortVariable], $req[$this->queryStringSortDirectionVariable]);
			}
		}
	}

    /**
     * @return string
     */
    protected function executeBase() {
		$db = Loader::db();
		$q = $this->query . $this->userQuery . ' where 1=1 ';
		foreach($this->filters as $f) {
			$column = $f[0];
			$comp = $f[2];
			$value = $f[1];
			// if there is NO column, then we have a free text filter that we just add on
			if ($column == false || $column == '') {
				$q .= 'and ' . $f[1] . ' ';
			} else {
				if (is_array($value)) {
					if (count($value) > 0) {
						switch($comp) {
							case '=':
								$comp = 'in';
								break;
							case '!=':
								$comp = 'not in';
								break;
						}
						$q .= 'and ' . $column . ' ' . $comp . ' (';
						for ($i = 0; $i < count($value); $i++) {
							if ($i > 0) {
								$q .= ',';
							}
							$q .= $db->quote($value[$i]);
						}
						$q .= ') ';
					} else {
						$q .= 'and 1 = 2 ';
					}
				} else { 
					$comp = (is_null($value) && stripos($comp, 'is') === false) ? (($comp == '!=' || $comp == '<>') ? 'IS NOT' : 'IS') : $comp;
					$q .= 'and ' . $column . ' ' . $comp . ' ' . $db->quote($value) . ' ';
				}
			}
		}
		
		if ($this->userPostQuery != '') {
			$q .= ' ' . $this->userPostQuery . ' ';
		}
		
		if ($this->groupByString != '') {
			$q .= 'group by ' . $this->groupByString . ' ';
		}		

		if ($this->havingString != '') {
			$q .= 'having ' . $this->havingString . ' ';
		}		
		
		return $q;
	}

    /**
     *
     */
    protected function setupSortByString() {
		if ($this->sortByString == '' && $this->sortBy != '') {
			$this->sortByString = $this->sortBy . ' ' . $this->sortByDirection;
		}
	}

    /**
     *
     */
    protected function setupAttributeSort() {
		if (is_callable(array($this->attributeClass, 'getList'))) {
			$l = call_user_func(array($this->attributeClass, 'getList'));
			foreach($l as $ak) {
				$this->autoSortColumns[] = 'ak_' . $ak->getAttributeKeyHandle();
			}
			if ($this->sortBy != '' && in_array('ak_' . $this->sortBy, $this->autoSortColumns)) {
				$this->sortBy = 'ak_' . $this->sortBy;
			}
		}
	}
	
	/** 
	 * Returns an array of whatever objects extends this class (e.g. PageList returns a list of pages).
	 */
	public function get($itemsToGet = 0, $offset = 0) {
		$q = $this->executeBase();
		// handle order by
		$this->setupAttributeSort();
		$this->setupAutoSort();
		$this->setupSortByString();
		
		if ($this->sortByString != '') {
			$q .= 'order by ' . $this->sortByString . ' ';
		}	
		if ($this->itemsPerPage > 0 && (intval($itemsToGet) || intval($offset)) ) {
			$q .= 'limit ' . $offset . ',' . $itemsToGet . ' ';
		}
		
		$db = Loader::db();
		if ($this->debug) { 
			Database::setDebug(true);
		}
		//echo $q.'<br>'; 
		$resp = $db->GetAll($q);
		if ($this->debug) { 
			Database::setDebug(false);
		}
		
		$this->start = $offset;
		return $resp;
	}
	
	/** 
	 * Adds a filter to this item list
	 */
	public function filter($column, $value, $comparison = '=') {
		$this->filters[] = array($column, $value, $comparison);
	}

    /**
     * @param $field
     * @return string
     */
    public function getSearchResultsClass($field) {
		if ($field instanceof AttributeKey) {
			$field = 'ak_' . $field->getAttributeKeyHandle();
		}
		return parent::getSearchResultsClass($field);
	}

    /**
     * @param string $key
     * @param string $dir
     */
    public function sortBy($key, $dir = 'asc') {
		if ($key instanceof AttributeKey) {
			$key = 'ak_' . $key->getAttributeKeyHandle();
		}
		parent::sortBy($key, $dir);
	}

    /**
     * @param $key
     */
    public function groupBy($key) {
		if ($key instanceof AttributeKey) {
			$key = 'ak_' . $key->getAttributeKeyHandle();
		}
		$this->groupByString = $key;
	}

    /**
     * @param $column
     * @param $value
     * @param string $comparison
     */
    public function having($column, $value, $comparison = '=') {
		if ($column == false) {
			$this->havingString = $value;
		} else {
			$this->havingString = $column . ' ' . $comparison . ' ' . $value;
		}
	}

    /**
     * @param $column
     * @param string $dir
     * @param bool $baseURL
     * @param array $additionalVars
     */
    public function getSortByURL($column, $dir = 'asc', $baseURL = false, $additionalVars = array()) {
		if ($column instanceof AttributeKey) {
			$column = 'ak_' . $column->getAttributeKeyHandle();
		}
		return parent::getSortByURL($column, $dir, $baseURL, $additionalVars);
	}

    /**
     * @param $join
     */
    protected function setupAttributeFilters($join) {
		$i = 1;
		$this->addToQuery($join);
		foreach($this->attributeFilters as $caf) {
			$this->filter($caf[0], $caf[1], $caf[2]);
		}
	}

    /**
     * @param $column
     * @param $value
     * @param string $comparison
     */
    public function filterByAttribute($column, $value, $comparison = '=') {
		if (is_array($column)) {
			$column = $column[key($column)] . '_' . key($column);
		}
		$this->attributeFilters[] = array('ak_' . $column, $value, $comparison);
	}
	

}

/**
 * Class Concrete5_Library_DatabaseItemListColumn
 */
class Concrete5_Library_DatabaseItemListColumn {

    /**
     * @param $obj
     * @return mixed
     */
    public function getColumnValue($obj) {
		if (is_array($this->callback)) {
			return call_user_func($this->callback, $obj);
		} else {
			return call_user_func(array($obj, $this->callback));
		}
	}

    /**
     * @return mixed
     */
    public function getColumnKey() {return $this->columnKey;}

    /**
     * @return mixed
     */
    public function getColumnName() {return $this->columnName;}

    /**
     * @return string
     */
    public function getColumnDefaultSortDirection() {return $this->defaultSortDirection;}

    /**
     * @return bool
     */
    public function isColumnSortable() {return $this->isSortable;}

    /**
     * @return mixed
     */
    public function getColumnCallback() {return $this->callback;}

    /**
     * @param $dir
     */
    public function setColumnDefaultSortDirection($dir) {$this->defaultSortDirection = $dir;}

    /**
     * @param $key
     * @param $name
     * @param $callback
     * @param bool $isSortable
     * @param string $defaultSort
     */
    public function __construct($key, $name, $callback, $isSortable = true, $defaultSort = 'asc') {
		$this->columnKey = $key;
		$this->columnName = $name;
		$this->isSortable = $isSortable;
		$this->callback = $callback;
		$this->defaultSortDirection = $defaultSort;
	}
}

/**
 * Class Concrete5_Library_DatabaseItemListAttributeKeyColumn
 */
class Concrete5_Library_DatabaseItemListAttributeKeyColumn extends Concrete5_Library_DatabaseItemListColumn {

    /**
     * @var bool
     */
    protected $attributeKey = false;

    /**
     * @return bool
     */
    public function getAttributeKey() {
		return $this->attributeKey;
	}

    /**
     * @param $attributeKey
     * @param bool $isSortable
     * @param string $defaultSort
     */
    public function __construct($attributeKey, $isSortable = true, $defaultSort = 'asc') {
		$this->attributeKey = $attributeKey;
		parent::__construct('ak_' . $attributeKey->getAttributeKeyHandle(), $attributeKey->getAttributeKeyDisplayName('text'), false, $isSortable, $defaultSort);
	}

    /**
     * @param $obj
     * @return mixed
     */
    public function getColumnValue($obj) {
		if (is_object($this->attributeKey)) {
			$vo = $obj->getAttributeValueObject($this->attributeKey);
			if (is_object($vo)) {
				return $vo->getValue('display');
			}
		}
	}
}

/**
 * Class Concrete5_Library_DatabaseItemListColumnSet
 */
class Concrete5_Library_DatabaseItemListColumnSet {

    /**
     * @var array
     */
    protected $columns = array();
    /**
     * @var
     */
    protected $defaultSortColumn;

    /**
     * @param $col
     */
    public function addColumn($col) {
		$this->columns[] = $col;
	}

    /**
     *
     */
    public function __wakeup() {
		$i = 0;
		foreach($this->columns as $col) {
			if ($col instanceof DatabaseItemListAttributeKeyColumn) {
				$ak = call_user_func(array($this->attributeClass, 'getByHandle'), substr($col->getColumnKey(), 3));
				if (!is_object($ak)) {
					unset($this->columns[$i]);
				}
			}
			$i++;
		}		
	}

    /**
     * @return array
     */
    public function getSortableColumns() {
		$tmp = array();
		$columns = $this->getColumns();
		foreach($columns as $col) {
			if ($col->isColumnSortable()) {
				$tmp[] = $col;
			}
		}
		return $tmp;
	}

    /**
     * @param DatabaseItemListColumn $col
     * @param bool $direction
     */
    public function setDefaultSortColumn(DatabaseItemListColumn $col, $direction = false) {
		if ($direction != false) {
			$col->setColumnDefaultSortDirection($direction);
		}
		$this->defaultSortColumn = $col;
	}

    /**
     * @return mixed
     */
    public function getDefaultSortColumn() {
		return $this->defaultSortColumn;
	}

    /**
     * @param $key
     * @return DatabaseItemListAttributeKeyColumn
     */
    public function getColumnByKey($key) {
		if (substr($key, 0, 3) == 'ak_') {
			$ak = call_user_func(array($this->attributeClass, 'getByHandle'), substr($key, 3));
			$col = new DatabaseItemListAttributeKeyColumn($ak);
			return $col;
		} else {
			foreach($this->columns as $col) {
				if ($col->getColumnKey() == $key) {
					return $col;			
				}
			}
		}
	}

    /**
     * @return array
     */
    public function getColumns() {return $this->columns;}

    /**
     * @param $col
     * @return bool
     */
    public function contains($col) {
		foreach($this->columns as $_col) {
			if ($col instanceof DatabaseItemListColumn) {
				if ($_col->getColumnKey() == $col->getColumnKey()) {
					return true;
				}
			} else if (is_a($col, 'AttributeKey')) {
				if ($_col->getColumnKey() == 'ak_' . $col->getAttributeKeyHandle()) {
					return true;
				}
			}
		}
		return false;
	}
}
