Yii RBACModels
==========

extends CActiveRecord model to include RBAC (Role Based Access Control) in find functions

Extends CActiveRecord model to include RBAC (Role Based Access Control) in find functions

This is a try to develop an activerecord class capable of automat ically filter records, on all multiple find functions (findAll, findAllByAttributes, findAllBySQL).

Its use is pretty simple: just install it, change your ACRecord model class with RBACActiveRecord and create the virtual attribute "access" (public function getAccess()) in model, as described below. Every multiple find function will return only records accessible by the logged in user.

*NOTE: - this class is in alpha state and has not been optimized and on large datasets it could have some performance issue. Any contribution in its development and comments are much appreciated. - this extension pack also contains a modified version of CActiveDataProvider that should be be used together with RBACActiveRecord, because recalculates the total number of items according to RBAC filtering.

TO INSTALL THIS EXTENSION
git  clone into extension folder
add:
~~~
[php]
 'ext.RBACModels.components.*', // RBACActiveRecord and RBACDataProvider
~~~
to protected/main.cfg 'import' array

(optional) add:
~~~
[php]
'RBACActiveRecord' => array(
   'performRBAC' => TRUE, // RBAC record filtering enabled by default
)
~~~
to protected/main.cfg 'params' array

##Requirements

Yii 1.1 or above (testet with Yii 1.1.13)

##Usage
In your ActiveRecord models (the one you want to filter using Rule Based Access Control):
change the model class to RBACActiveRecord
add the "access" virtual attribute as follows 
~~~
[php]
public function getAccess(){
    return "your access rule";
}
~~~
example (as described in RBACActiveRecord class):
~~~
[php]
public function getAccess(){
  // using an access rule containing a with bizule
  if (Yii::app()->user->checkAccess('storeaccess', array('store_id' => $this->id)){
     $access = TRUE;
  } else {
     $access = FALSE;
  }
  return $access;
}
~~~
