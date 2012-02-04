<?php


class cache{

  var $user_id;
  var $dbfile;
  var $disable_disk_cache;
  var $data;

  function cache($user_id, $disable_disk_cache = true){
    if($disable_disk_cache || !function_exists('dba_open')) {
       $this->disable_disk_cache = true;
    }

    $this->user_id = $user_id;

    if( !$this->disable_disk_cache ) {
       $this->dbfile = $GLOBALS[config][cache_db];

       // Init the cache if it does not exist
       if( !toolbox::dba_set($this->dbfile, 'description', 'Distributed City HTML Cache') ) {
         die("cannot create cache file in: ". $this->dbfile);
       }
    }
  }


  function put($key, $value){
     if(!$this->disable_disk_cache) {
        toolbox::dba_set( $this->dbfile, $key, $value );
     }
     else {
        $this->data[$key] = $value;
     }
  }


  // Putlocal - adds an entry for a key custom to a particular user
  function put_local($key, $value){
     $this->put(md5($this->user_id . $key, $value));
  }

  // Getlocal - gets an entry for a key custom to a particular user
  function get_local($key, $value){
    $this->get(md5($this->user_id . $key));
  }



  function get($key){
    $value = null;
    if(!$this->disable_disk_cache) {
       return toolbox::dba_get($this->dbfile, $key);
    }
    else {
       return $this->data[$key];
    }
    return $value;
  }


  function exists($key){
    if(!$this->disable_disk_cache) {
       return toolbox::dba_exists($this->dbfile, $key);
    }
    else {
       return isset($this->data[$key] );
    }
  }


  // Delete
  function clear($key){
    $result = true;
    if(!$this->disable_disk_cache) {
       return toolbox::dba_delete($this->dbfile, $key);
    }
    else {
       unset( $this->data[$key] );
    }
    return $result;
  }

}

?>