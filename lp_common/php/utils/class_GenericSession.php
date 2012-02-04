<?php

class GenericSession {

/* Public Methods
 */

   function GenericSession() {
      $this->resume();
   }

   // start a session
   function start() {
      session_start();
   }

   // resume an existing session
   function resume() {
      $this->start();

      /* Security Measure:
       *  When exposing SID in url, it is possible that user could send URL to
       *  another user or click to another website and leave SID in site's logs.
       *  Thus, we check here that the HTTP referer is our current website.
       *  If not, then user is logged out.  This is not a failsafe measure, as
       *  an informed attacker with a valid SID could fake the REFERER header,
       *  but this solves the casual case where user sends url to a friend
       *  inadvertently. Note also that this means the site is unuseable if client
       *  browser does not send referer header.
       *
       *  Note that the above is not necessary if the session uses cookies instead
       *  of SID var, thus we check that first.
       */
      if( !$this->is_cookie_session() && 
          !strstr($_SERVER[HTTP_REFERER], $_SERVER[HTTP_HOST])) {
        $this->end();
        $this->start();
      }
   }

   // end a session, wipe data
   function end() {
      session_destroy();
   }

   // determine if session is using cookie support or not
   function is_cookie_session() {
      if( ini_get('session.use_cookies') ) {
         $name = session_name();
         if( $_COOKIE[$name] ) {
            return true;
         }
      }
      return false;
   }

   // set session attr
   function setAttr($name, $value) {
      $name = "gs_$name";
      $_SESSION[$name] = $value;
      return true;
   }

   // get session attr
   function& getAttr($name) {
      $name = "gs_$name";
      return $_SESSION[$name];
   }


/* Pure Virtual (Protected) Methods
 */

};

?>
