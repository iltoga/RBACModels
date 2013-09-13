<?php
/**
 * RBACDataProvider class file.
 * 
 * @version 1.0 alpha
 * @author Stefano Galassi <iltoga@hotmail.com>
 * @link http://www.webgoreng.com/
 * @copyright Copyright &copy; 2013 Stefano Galassi
 * @license BSD 2-clause license http://en.wikipedia.org/wiki/BSD_licenses
 * 
**/

/*
 * RBACDataProvider implements a modified version of data provider based on RBACActiveRecord (but it can be used with any ActiveRecord class as long as Rights module is installed).
 * Depends on Rights module (http://www.yiiframework.com/extension/rights/) and User module (http://www.yiiframework.com/extension/yii-user/)
 * Overrides methods:
 * fetchData(): filter the fetched data using access function (defined in the Model)
 * calculateTotalItemCount(): recalculates the total number of items according to what we have fetched in the above function
 *
*/
class RBACDataProvider extends CActiveDataProvider
{
	/**
	 * Fetches the data from the persistent data storage.
	 * @return array list of data items
	 */
        /* @var $data RBACActiveRecord */
	protected function fetchData()
	{
		$criteria=clone $this->getCriteria();

		if(($pagination=$this->getPagination())!==false)
		{
			$pagination->setItemCount($this->getTotalItemCount(true));
			$pagination->applyLimit($criteria);
		}

		$baseCriteria=$this->model->getDbCriteria(false);

		if(($sort=$this->getSort())!==false)
		{
			// set model criteria so that CSort can use its table alias setting
			if($baseCriteria!==null)
			{
				$c=clone $baseCriteria;
				$c->mergeWith($criteria);
				$this->model->setDbCriteria($c);
			}
			else
				$this->model->setDbCriteria($criteria);
			$sort->applyOrder($criteria);
		}

		$this->model->setDbCriteria($baseCriteria!==null ? clone $baseCriteria : null);
                
                // hack the normal pagination with one compatible with RBAC filter
                if ($pagination){
                    $offset = $criteria->offset;
                    $criteria->offset = -1; // start from first record
                    $criteria->limit = -1; // unlimited
                    //if(isset($_GET["$sort->sortVar"]) || isset($_GET["$this->modelClass"])){
                    //    Yii::app()->cache->delete($this->model->getChacheKey(TRUE, $position = '', $id = '', $page = $pagination->currentPage));
                    //}
                    //if ($cache = $this->model->getCachedValue(TRUE, $position = '', $id = '', $page = $pagination->currentPage)){
                    //    //$performRBAC=NULL, $position = '', $id = '', $page = '', $sort = ''
                    //    $data = $cache;
                    //} else {
                        //$dependency = $this->model->getCacheDependency($criteria);
                        //$data=$this->model->cache(1000, $dependency)->findAll($criteria);
                        $data=$this->model->findAll($criteria);
                        $pageSize = $pagination->pageSize;
                        $tmpData = array();
                        for ($index = $offset; $index < ($offset+$pageSize); $index++) {
                            if(isset($data[$index])){
                                array_push($tmpData, $data[$index]);
                            } else {
                                break;
                            }
                        }
                        $data = $tmpData;
                //        Yii::app()->cache->set($this->model->getChacheKey(TRUE, $position = '', $id = '', $page = $pagination->currentPage), $data, 1000, $dependency);
                    //}
                //} else {
                //    if(isset($_GET["$sort->sortVar"])){
                //        Yii::app()->cache->delete($this->model->getChacheKey(TRUE, $position = '', $id = ''));
                //    }
                //    if ($cache = $this->model->getCachedValue(TRUE, $position = '', $id = '')){
                //        $data = $cache;
                //    } else {
                //        $data=$this->model->cache(1000, $dependency)->findAll($criteria);
                //        Yii::app()->cache->set($this->model->getChacheKey(TRUE, $position = '', $id = ''), $data, 1000, $dependency);
                //    }
                }
                
                // filter data according to RBAC rules
                //$data = $this->RBACFilter($data);
		$this->model->setDbCriteria($baseCriteria);  // restore original criteria
               
		return $data;
	}

        
	/**
	 * Calculates the total number of data items.
	 * @return integer the total number of data items.
	 */
	protected function calculateTotalItemCount()
	{
		$baseCriteria=$this->model->getDbCriteria(false);
		if($baseCriteria!==null)
			$baseCriteria=clone $baseCriteria;
                // filter data according to RBAC rules
                if ($cache = $this->model->getCachedValue(TRUE, $position = '', $id = 'recordcount')){
                    $count = $cache;
                } else {                
                    $criteria = $this->getCriteria();
                    $data=$this->model->findAll($criteria);
                    
                    //$dependency = $this->model->getCacheDependency($criteria);
                    //$data=$this->model->cache(1000, $dependency)->findAll($criteria);
                    
                    // if current model is an instance of RBACActiveRecord there's no need to perform RBAC filtering again (it's already done in findAll)
                    if (!$this instanceof RBACActiveRecord) {
                        $data = $this->RBACFilter($data);
                    }
                    $count=count($data);
                    //Yii::app()->cache->set($this->model->getChacheKey(TRUE, $position = '', $id = 'recordcount'), $count, 1000, $dependency);
                }
                
		$this->model->setDbCriteria($baseCriteria);
		return $count;
	}
        
        /**
         * Filter data according to RBAC rules defined at Model's level
         * note: to work properly just override the getAccess() virtual attribute with the model's specific Access Rules
         * @param mixed $data Object Model or array of Models
         * @return mixed Object Model or array of Models (filtered by access rules defined in getAccess() function)
         */
        public function RBACFilter($data){
                if(!is_null($data)){
                    if (!is_array($data)){ // if is not an array is a Object Model
                       if (!$data->access){
                           $data = NULL;
                       }
                   } else {
                       $resAry = array();
                       foreach ($data as $rec) {
                           if($rec->access){
                               array_push($resAry, $rec);
                           }
                       }
                       $data = $resAry;
                   }                   
                }
                return $data;
        } 
}

