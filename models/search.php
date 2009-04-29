<?php
class Search extends SearchAppModel
{
	var $name = 'Search';
	
	var $useTable = 'search_index';
	
	/**
	 * Saves search index data
	 *
	 * @param string $model Model name
	 * @param int $model_id Model id
	 * @param string $data Search index data
	 * @return boolean True on success, false on error
	 */
	function saveIndex($model, $model_id, $data = '')
	{
		if(empty($model) or empty($model_id))
		{
			return false;
		}
		
		// Index exists ?
		$conditions = compact('model', 'model_id');
		
		if($index = $this->find('first', compact('conditions')))
		{
			$this->id = $index['Search']['id'];
			$this->saveField('data', $data);
		}
		else
		{
			$this->create(array('Search' => compact('model', 'model_id', 'data')));
			$this->save();
		}
	}
}
?>