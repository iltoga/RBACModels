<?php
/**
 * RBACActiveRecord class file.
 * 
 * @version 1.0 alpha
 * @author Stefano Galassi <iltoga@hotmail.com>
 * @link http://www.webgoreng.com/
 * @copyright Copyright &copy; 2013 Stefano Galassi
 * @license BSD 2-clause license http://en.wikipedia.org/wiki/BSD_licenses
 * 
 * @todo hack count functions to user RBAC too (as they use aggregation SQL functions now seems not possible)
**/

/*
 * This class extends CActiveRecord model to include RBAC (Role Based Access Control) in findAll, findAllbyPk and findAllByAttributes functions
 * Depends on Rights module (http://www.yiiframework.com/extension/rights/) and User module (http://www.yiiframework.com/extension/yii-user/)
 * 
 */
class RBACActiveRecord extends CActiveRecord
{
        public $performRBAC = FALSE; // override this var and turn it on (TRUE) if you want RBAC record filtering on this model (note that it defaults to the param defined in config.php)
	
	/**
	 * Constants
	 */

	const CONFIG_PARAMS='RBACActiveRecord'; //Define the key of the Yii params for the config array        

	/**
	 * Constructor.
         * @param bool $performRBAC if false, disables RBAC for this model
	 * @param string $scenario scenario name. See {@link CModel::scenario} for more details about this parameter.
	 */
	public function __construct($scenario='insert',$performRBAC=NULL)
	{
		//initialize config (if config params are not found, just use the defaults as defined in this model)
		if(isset(Yii::app()->params[self::CONFIG_PARAMS])){
                    $config=Yii::app()->params[self::CONFIG_PARAMS];
                    $this->performRBAC = $config['performRBAC'];
                }
                if($performRBAC){
                    $this->performRBAC = $performRBAC;
                }
                parent::__construct($scenario);
	}
        
	/**
	 * Finds all active records satisfying the specified condition.
	 * See {@link find()} for detailed explanation about $condition and $params.
	 * @param mixed $condition query condition or criteria.
	 * @param array $params parameters to be bound to an SQL statement.
	 * @param bool $performRBAC if false, disables RBAC for this query
	 * @param bool $cache true to turn on cache for this query (default FALSE)
	 * @param bool $cacheTimeout timeout in seconds fro the cahced query to be flushed (default 5 seconds)
	 * @return array list of active records satisfying the specified condition. An empty array is returned if none is found.
	 */
	public function findAll($condition='',$params=array(), $performRBAC=NULL, $cache=FALSE, $cacheTimeout=5)
	{
                if(is_null($performRBAC)){
                    $performRBAC = $this->performRBAC; // if not set we use the global model param
                }
                
                if ($cache){
                    //cache 10 seconds of queries
                    $varClass=  get_class($this);
                    //Yii::app()->cache->flush();
                    $varSort=  $varClass."_sort";
                    if(Yii::app()->request->getParam($varSort) || Yii::app()->request->getParam($varClass)){
                        Yii::app()->cache->delete($this->getChacheKey($performRBAC));
                    }
                    $cache = $this->getCachedValue($performRBAC);
                    if ($cache && !(Yii::app() instanceof CConsoleApplication)){
                        Yii::trace(get_class($this).'.findAll() "cached"','system.db.ar.CActiveRecord');
                        $model = $cache;
                    } else {
                        Yii::trace(get_class($this).'.findAll()','system.db.ar.CActiveRecord');
                        $criteria=$this->getCommandBuilder()->createCriteria($condition,$params);
                        $dependency = $this->getCacheDependency($criteria);                    
                        $model = $this->query($criteria,true);
                        if(!empty($model) && $performRBAC){
                            $model = $this->RBACFilter($model);
                        }
                        Yii::app()->cache->set($this->getChacheKey($performRBAC), $model, $cacheTimeout, $dependency);
                    }
                } else {
                        $model = parent::findAll($condition, $params);
                        if(!empty($model) && $performRBAC){
                            $model = $this->RBACFilter($model);
                        }
                }
                return $model;
	}

	/**
	 * Finds all active records with the specified primary keys.
	 * See {@link find()} for detailed explanation about $condition and $params.
	 * @param mixed $pk primary key value(s). Use array for multiple primary keys. For composite key, each key value must be an array (column name=>column value).
	 * @param mixed $condition query condition or criteria.
	 * @param array $params parameters to be bound to an SQL statement.
	 * @param bool $performRBAC if false, disables RBAC for this query
	 * @param bool $cache true to turn on cache for this query (default FALSE)
	 * @param bool $cacheTimeout timeout in seconds fro the cahced query to be flushed (default 5 seconds)
         * @return array the records found. An empty array is returned if none is found.
	 */
	public function findAllByPk($pk,$condition='',$params=array(), $performRBAC=NULL, $cache=FALSE, $cacheTimeout=5)
	{
                if(is_null($performRBAC)){
                    $performRBAC = $this->performRBAC; // if not set we use the global model param
                }
                
                if ($cache){
                    //cache 10 seconds of queries
                    $varClass=  get_class($this);
                    $varSort=  $varClass."_sort";
                    if(Yii::app()->request->getParam($varSort) || Yii::app()->request->getParam($varClass)){
                        Yii::app()->cache->delete($this->getChacheKey($performRBAC));
                    }
                    $cache = $this->getCachedValue($performRBAC);
                    if ($cache && !(Yii::app() instanceof CConsoleApplication)){
                        Yii::trace(get_class($this).'.findAllByPk() "cached"','system.db.ar.CActiveRecord');
                        $model = $cache;
                    } else {
                        Yii::trace(get_class($this).'.findAllByPk()','system.db.ar.CActiveRecord');
                        $prefix=$this->getTableAlias(true).'.';
                        $criteria=$this->getCommandBuilder()->createPkCriteria($this->getTableSchema(),$pk,$condition,$params,$prefix);
                        $dependency = $this->getCacheDependency($criteria);                    
                        $model = $this->query($criteria,true);
                        if(!empty($model) && $performRBAC){
                            $model = $this->RBACFilter($model);
                        }
                        Yii::app()->cache->set($this->getChacheKey($performRBAC), $model, $cacheTimeout, $dependency);
                    }
                } else {
                        $model = parent::findAllByPk($pk,$condition='',$params=array());
                        if(!empty($model) && $performRBAC){
                            $model = $this->RBACFilter($model);
                        }
                }
                return $model;            
	}

	/**
	 * Finds all active records that have the specified attribute values.
	 * See {@link find()} for detailed explanation about $condition and $params.
	 * @param array $attributes list of attribute values (indexed by attribute names) that the active records should match.
	 * An attribute value can be an array which will be used to generate an IN condition.
	 * @param mixed $condition query condition or criteria.
	 * @param array $params parameters to be bound to an SQL statement.
	 * @param bool $performRBAC if false, disables RBAC for this query
	 * @param bool $cache true to turn on cache for this query (default FALSE)
	 * @param bool $cacheTimeout timeout in seconds fro the cahced query to be flushed (default 5 seconds)
	 * @return array the records found. An empty array is returned if none is found.
	 */
	public function findAllByAttributes($attributes,$condition='',$params=array(), $performRBAC=NULL, $performRBAC=NULL, $cache=FALSE, $cacheTimeout=5)
	{
                if(is_null($performRBAC)){
                    $performRBAC = $this->performRBAC; // if not set we use the global model param
                }
                
                if ($cache){
                    //cache 10 seconds of queries
                    $varClass=  get_class($this);
                    $varSort=  $varClass."_sort";
                    if(Yii::app()->request->getParam($varSort) || Yii::app()->request->getParam($varClass)){
                        Yii::app()->cache->delete($this->getChacheKey($performRBAC));
                    }
                    $cache = $this->getCachedValue($performRBAC);
                    if ($cache && !(Yii::app() instanceof CConsoleApplication)){
                        Yii::trace(get_class($this).'.findAllByAttributes() "cached"','system.db.ar.CActiveRecord');
                        $model = $cache;
                    } else {
                        Yii::trace(get_class($this).'.findAllByAttributes()','system.db.ar.CActiveRecord');
                        $prefix=$this->getTableAlias(true).'.';
                        $criteria=$this->getCommandBuilder()->createColumnCriteria($this->getTableSchema(),$attributes,$condition,$params,$prefix);
                        $dependency = $this->getCacheDependency($criteria);                    
                        $model = $this->query($criteria,true);
                        if(!empty($model) && $performRBAC){
                            $model = $this->RBACFilter($model);
                        }
                        Yii::app()->cache->set($this->getChacheKey($performRBAC), $model, $cacheTimeout, $dependency);
                    }
                } else {
                        $model = parent::findAllByAttributes($attributes,$condition='');
                        if(!empty($model) && $performRBAC){
                            $model = $this->RBACFilter($model);
                        }
                }
                return $model;            
	}
        
	/**
	 * Returns the related record(s).
	 * This method will return the related record(s) of the current record.
	 * If the relation is HAS_ONE or BELONGS_TO, it will return a single object
	 * or null if the object does not exist.
	 * If the relation is HAS_MANY or MANY_MANY, it will return an array of objects
	 * or an empty array.
	 * @param string $name the relation name (see {@link relations})
	 * @param boolean $refresh whether to reload the related objects from database. Defaults to false.
	 * @param mixed $params array or CDbCriteria object with additional parameters that customize the query conditions as specified in the relation declaration.
	 * @param bool $performRBAC if false, disables RBAC for this query (overrides defaults)
	 * @return mixed the related object(s).
	 * @throws CDbException if the relation is not specified in {@link relations}.
	 */
	public function getRelated($name,$refresh=false,$params=array(), $performRBAC=NULL)
	{
                if(is_null($performRBAC)){
                    $performRBAC = $this->performRBAC; // if not set we use the global model param
                }
                
                $relModel = parent::getRelated($name, $refresh, $params);
                if(!is_null($relModel) && !empty($relModel) && $performRBAC){
                    if(is_array($relModel)){
                        $refModel = $relModel[0];
                        if ($refModel instanceof RBACActiveRecord && $refModel->performRBAC){
                            $relModel = $refModel->RBACFilter($relModel);
                        }
                    } else {
                        if ($relModel instanceof RBACActiveRecord && $relModel->performRBAC){
                            if (!$relModel->access){
                                $relModel = array();
                            }
                        }
                    }
                }
                return $relModel;
	}
        
        /**
         * CUSTOM FUNCTIONS
         */
        
        /**
         * Gets user access for this model
         * @return boolean true if user access is granted
         * NOTE: OVERRIDE THIS FUNCTION AND PUT YOUR OWN ACCESS RULES, EG.
         * 
            if (Yii::app()->user->checkAccess('store', array('store_id' => $this->id)){
                $access = TRUE;
            } else {
                $access = FALSE;
            }
            return $access;
         * 
         * NOTE 2: an access rule 
         */
        public function getAccess(){
            return TRUE;
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
        
        public function getCachedValue($performRBAC=NULL, $position = '', $id = '', $page = '', $sort = '') {
                return Yii::app()->cache->get($this->getChacheKey($performRBAC, $position, $id, $page, $sort));
        }
        
        public function getChacheKey($performRBAC = NULL, $position = '', $id = '', $page = '', $sort = '') {
                if(is_null($performRBAC)){
                    $performRBAC = $this->performRBAC; // if not set we use the global model param
                }
                if(isset(Yii::app()->controller->action)){
                    $controller=Yii::app()->controller->action->id;
                } else {
                    $controller=Yii::app()->controller->id;
                }
                if(isset(Yii::app()->user->id)){
                    $user=Yii::app()->user->id;
                } else {
                    $user=guest;
                }
                if(isset($this->primaryKey)){
                    $id = $this->primaryKey;
                }
                $cacheService = new RECacheService(get_class($this), $controller , Yii::app()->user->id, $performRBAC, $position, $id, $page, $sort);
                return $cacheService->createKey();
        }
        
        public function getCacheDependency($criteria) {
            $command = Yii::app()->db->commandBuilder->createFindCommand($this->tableName(), $criteria);
            // then get sql statement text
            $sql = $command->text;
            // then set the dependency
            $dependency = new CDbCacheDependency($sql);
            // if we have params in the criteria, set the params for dependency
            $dependency->params = $criteria->params;
        }
}
