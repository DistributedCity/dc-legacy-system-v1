<?php


class session {

   var $SID;

   function session() {

      $this->touch();

   }



   function set_comment_mode($change_mode="") {

      // Get/Set Comment Display Mode
      if ($change_mode == 'none') {
         $this->comment_mode = 'none';
      }
      else if ($change_mode == 'flat') {
         $this->comment_mode = 'flat';
      }
      else if ($change_mode == 'flat_asc') {
         $this->comment_mode = 'flat_asc';
      }
      else if ($change_mode == 'flat_desc') {
         $this->comment_mode = 'flat_desc';
      }
      else if ($change_mode == 'nested') {
         $this->comment_mode = 'nested';
      }
      else if (empty($this->comment_mode)) {
         $this->comment_mode = 'nested'; // default
      }
   }



   function get_comment_mode_selected_state() {

      $selected_state = array("selected_none"      => $this->comment_mode == 'none' ? 'selected' : '',
                              "selected_flat"      => $this->comment_mode == 'flat' ? 'selected' : '',
                              'selected_flat_asc'  => $this->comment_mode == 'flat_asc' ? 'selected' : '',
                              'selected_flat_desc' => $this->comment_mode == 'flat_desc' ? 'selected' : '',
                              "selected_nested"    => $this->comment_mode == 'nested' ? 'selected' : '',
                              "selected_threaded"  => $this->comment_mode == 'threaded' ? 'selected' : '',
                             );

      return $selected_state;
   }


   function touch() {
      $this->timeout = time() + $GLOBALS[config][session][timeout] * 60;
   }

   function is_alive() {
      if ($this->timeout > time()) {
         return true;
      }
      else {
         return false;
      }

   }

   function is_stale( $max_fresh = 7200 ) {  // 60 * 60 * 2 = 7200 = 2 hours
       return time() - $this->timeout > $max_fresh ? true : false;
   }

   function check() {
      if ($this->is_alive()) {
         $this->touch();
         return true;
      }
      else {
         $this->destroy();
         return false;
      }
   }

   function destroy() {
       session_destroy();
   }


}

?>