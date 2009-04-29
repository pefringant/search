<?php
class SearchesController extends SearchAppController
{
	var $name = 'Searches';
	
	/**
	 * Search mode : natural language or boolean mode
	 * Override it with Configure::write('Search.mode', '...')
	 * 
	 * @var string Mode : 'natural' or 'boolean'. Defaults to 'boolean'
	 */
	var $mode = 'boolean';
	
	/**
	 * Option for natural language mode.
	 * http://dev.mysql.com/doc/refman/5.0/en/fulltext-query-expansion.html
	 *
	 * "Because blind query expansion tends to increase noise significantly by 
	 *  returning non-relevant documents, it is meaningful to use only when a 
	 *  search phrase is rather short."
	 * 
	 * Override it with Configure::write('Search.withQueryExpansion', true/false)
	 * 
	 * If you do not override it, it will be set to true if the search query
	 * has only one word.
	 * 
	 * @var boolean Defaults to null
	 */
	var $withQueryExpansion = null;
	
	/**
	 * Characters allowed in the search query
	 * Override it with Configure::write('Search.allowed_chars', array(...))
	 *
	 * @var array Defaults to valid french accents
	 */
	var $allowedChars = array(
		' ',
		'â', 'à', 'ä',
		'é', 'è', 'ê', 'ë',
		'î', 'ï',
		'ô', 'ö',
		'ù', 'û', 'ü',
		'ç', 'œ', 'æ'
	);

	/**
	 * Looks for config options overrides in Configure class
	 * - Search.mode : type of MySQL search, natural or boolean
	 * - Search.allowedChars : characters allowed in the search query
	 */
	function beforeFilter()
	{
		// Search mode
		if($mode = Configure::read('Search.mode'))
		{
			if(in_array($mode, array('natural', 'boolean')))
			{
				$this->mode = $mode;
			}
		}
		
		// Query expansion
		if($this->mode == 'natural' && Configure::read('Search.withQueryExpansion') !== null)
		{
			$this->withQueryExpansion = Configure::read('Search.withQueryExpansion');
		}
		
		// Allowed chars
		if($allowedChars = Configure::read('Search.allowedChars'))
		{
			if(is_array($allowedChars))
			{
				$this->allowedChars = $allowedChars;
			}
		}
		
		parent::beforeFilter();
	}
	
	/**
	 * Search function
	 * PRG pattern : http://en.wikipedia.org/wiki/Post/Redirect/Get
	 *
	 * @param string $q Search query
	 */
	function index($q = null)
	{
		// Query
		// -----
		if(!empty($this->data))
		{
			$q = $this->data['Search']['q'];
			$q = $this->_clean($q);
			$q = urlencode($q);
			
			$this->redirect(array($q));
		}
		
		$q = urldecode($q);
		$q = $this->_clean($q);
		
		$this->set('q', $q);
		
		// Search form default value
		$this->data['Search']['q'] = $q;
		
		
		// Results
		// -------
		
		// Common paginate options
		$fields = array('model', 'model_id');
		$limit  = 10;
		
		switch($this->mode)
		{
			case 'boolean':
				$statement  = $this->_prepareForBooleanMode($q);
				
				$conditions = "MATCH(Search.data) AGAINST('{$statement}' IN BOOLEAN MODE)";
				$order      = 'modified DESC, created DESC';
				break;
				
			case 'natural':
				$statement  = $this->_prepareForNaturalMode($q);
				$expansion  = '';
				
				if($this->withQueryExpansion)
				{
					$expansion = ' WITH QUERY EXPANSION';
				}
				
				$conditions = "MATCH(Search.data) AGAINST('{$statement}'{$expansion})";
				$fields[]   = $conditions . ' AS score';
				$order      = 'score DESC, modified DESC, created DESC';
				break;
		}
		
		$this->paginate = compact('fields', 'conditions', 'order', 'limit');
		
		$results = $this->paginate();
		
		// Build $data with actual Models data
		$data = array();
		
		foreach($results as $row)
		{
			$data[] = ClassRegistry::init($row['Search']['model'])->read(null, $row['Search']['model_id']);
		}
		
		$this->set('data', $data);
	}
	
	/**
	 * Prepares a search query for a MySQL Fulltext search in boolean mode
	 *
	 * @param string $query Search terms
	 * @return string Returns a string ready for a MySQL Fulltext search in boolean mode (+term1 +term2 ...)
	 */
	function _prepareForBooleanMode($query)
	{
		$terms = explode(' ', $query);
		
		if(count($terms) < 2)
		{
			return $query;
		}
		
		return '+' . join(' +', $terms);
	}
	
	/**
	 * Prepares a search query for a MySQL Fulltext search in natural language.
	 *
	 * @param string $query Search terms
	 * @return string $query
	 */
	function _prepareForNaturalMode($query)
	{
		// If withQueryExpansion option is not defined
		if($this->withQueryExpansion === null)
		{
			// Force query expansion for less than 2 words
			if(count(explode(' ', $query)) < 2)
			{
				$this->withQueryExpansion = true;
			}
		}
		
		return $query;
	}
	
	/**
	 * Cleans up a string
	 *
	 * @param string $str String to clean up
	 * @return string Clean string
	 */
	function _clean($str)
	{
		App::import('Sanitize');
		
		$str = Sanitize::paranoid($str, $this->allowedChars);
		$str = preg_replace('/ +/', ' ', $str);
		$str = trim($str);
		
		return $str;
	}
}
?>