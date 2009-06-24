<?php
class SearchableBehavior extends ModelBehavior
{
	/**
	 * Behavior settings
	 *
	 * @var array
	 */
	var $settings = array();
	
	/**
	 * Search Model handler
	 *
	 * @var object
	 */
	var $Search = null;
	
	/**
	 * Behavior setup
	 *
	 * @param object $model
	 * @param array $settings Fields to be indexed. Defaults to model's displayField
	 */
	function setup(&$model, $settings)
	{
		// Settings
		if(!isset($this->settings[$model->alias]))
		{
			$this->settings[$model->alias] = array(
				'fields' => array($model->displayField),
			);
		}
	
		$this->settings[$model->alias] = array_merge($this->settings[$model->alias], (array)$settings);
		
		// Search Model init
		$this->Search = ClassRegistry::init('Search.Search');
	}
	
	/**
	 * Saves the new search index data for this record
	 *
	 * @param object $model Model
	 */
	function afterSave(&$model)
	{
		if(!$model->id or !$data = $this->buildIndex($model))
		{
			return;
		}
		
		$this->Search->saveIndex($model->alias, $model->id, $data);
	}
	
	/**
	 * Deletes the search index data for this record
	 *
	 * @param object $model
	 * @return boolean True if success, false if failure
	 */
	function beforeDelete(&$model)
	{
		if(!$model->id)
		{
			return false;
		}
		
		$conditions = array(
			'model'    => $model->alias,
			'model_id' => $model->id,
		);
		
		$this->Search->deleteAll($conditions, false, true);
		
		return true;
	}
	
	/**
	 * Build index field value, to be saved as a string in the search_index table.
	 *
	 * @param object $model
	 * @return mixed Returns false if fields to be indexed are not in $model->data, 
	 * or returns a string ready to be saved in the search_index table. 
	 */
	function buildIndex(&$model)
	{
		// $model->data must be set
		if(!$data = $model->data[$model->alias])
		{
			return false;
		}
		
		$fields = $this->settings[$model->alias]['fields'];
		
		if(!is_array($fields))
		{
			$fields = array($fields);
		}

		// All fields must be in $model->data
		foreach($fields as $field)
		{
			if(!isset($data[$field]))
			{
				return false;
			}
		}
		
		$chunks = array();
		
		foreach($data as $field => $value)
		{
			if(in_array($field, $fields))
			{
				$chunks[] = $value;
			}
		}
		
		$index = join(' ', $chunks);
		
		// Cleaning
		$index = html_entity_decode($index, ENT_COMPAT, 'UTF-8');
		$index = strip_tags($index);
		
		return $index;
	}
}
?>