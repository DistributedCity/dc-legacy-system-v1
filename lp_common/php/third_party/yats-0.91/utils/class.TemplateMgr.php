<?php
/*********************************************************************************
   Copyright 2000 Epinions, Inc. 
   
   Subject to the following 3 conditions, Epinions, Inc. permits you, free of 
   charge, to (a) use, copy, distribute, modify, perform and display this 
   software and associated documentation files (the "Software"), and (b) permit
   others to whom the Software is furnished to do so as well. 
   
   1) The above copyright notice and this permission notice shall be included 
   without modification in all copies or substantial portions of the Software. 
   
   2) THE SOFTWARE IS PROVIDED "AS IS", WITHOUT ANY WARRANTY OR CONDITION OF ANY
   KIND, EXPRESS, IMPLIED OR STATUTORY, INCLUDING WITHOUT LIMITATION ANY IMPLIED
   WARRANTIES OF ACCURACY, MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR
   NONINFRINGEMENT. 
   
   3) IN NO EVENT SHALL EPINIONS, INC. BE LIABLE FOR ANY DIRECT, INDIRECT, SPECIAL,
   INCIDENTAL OR CONSEQUENTIAL DAMAGES OR LOST PROFITS ARISING OUT OF OR IN
   CONNECTION WITH THE SOFTWARE (HOWEVER ARISING, INCLUDING NEGLIGENCE), EVEN IF
   EPINIONS, INC. IS AWARE OF THE POSSIBILITY OF SUCH DAMAGES. 
*********************************************************************************/

/**
 * The TemplateMgr class is based on the YATS template system extension 
 * to PHP by Dan Libby.
 *
 * This class provides better control over multiple templates, and an 
 * easier way to retrieve template information (such as template handle 
 * names, template variable names, and template variable values) than is
 * available in the YATS implementation.
 *
 * The TemplateMgr class has been designed to be used as a global object,
 * providing one place for all template activities on a page request.  This
 * can be achieved in a number of ways, the easiest of which is to simply 
 * initialize a global TemplateMgr object at the beginning of a page
 * load.
 *
 * Here is an example of how one might go about building a page with 
 * YATS and class.TemplateMgr.php:
 *
 * File: /htdocs/www/phpinit.php
 *     <?php
 *         include("/htdocs/www/classes/class.TemplateMgr.php");
 *         $GLOBALS[gTplMgr] = new TemplateMgr("/htdocs/www/templates/");
 *     ?>
 *
 * File: /htdocs/www/templates/index.tpl
 *     <html>
 *     <head>
 *     <title>{{PAGE_TITLE}}</title>
 *     </head>
 *     <body bgcolor="{{PAGE_BGCOLOR}}">
 *     {{MSG_HELLO_WORLD}}
 *     </body>
 *     </html>
 *
 * File: /htdocs/www/index.html
 *     <?php
 *         include("/htdocs/www/phpinit.php");
 *         // Define the template to use.
 *         // NOTE: We are supplying only the file name since we already defined
 *         //       the default template location when we created the
 *         //       $GLOBALS[gTplMgr] object.
 *         $GLOBALS[gTplMgr]->define("page", "index.tpl");
 *         
 *         // You can assign template variables like this ...
 *         $GLOBALS[gTplMgr]->assign("page", array("PAGE_TITLE" => "YATS Rules!"));
 *
 *         // Or you could do it in a more readable way.
 *         $tplVars["PAGE_BGCOLOR"] = "#FFFFFF";
 *         $tplVars["MSG_HELLO_WORLD"] = "Hi there, big bad world.";
 *         $GLOBALS[gTplMgr]->assign("page", $tplVars);
 *
 *         // Print the page to screen.
 *         print $GLOBALS[gTplMgr]->parse_to_string("page");
 *     ?>
 *
 * If the benefits should imediately obvious.  As you can see the HTML of the page
 * has been entirely removed from the php code.  This separation of display from 
 * application logic greatly simplifies developement and maintenance of the HTML
 * and PHP on a site.
 *
 * @author     Joseph Rosenblum
 * @author     James W. Berry
 * @version    1.01
 */
class TemplateMgr {

    var $mPath;                           // Path to templates
    var $mBlockHandles        = array();  // Array of template 'handles'
    var $mBlockKeys           = array();  // Array of template keys and vals indexed to handles.
    var $mGlobalAssignArray   = array();  // Array of Global Key/Val pairs
    var $mGlobalHideSectionArray = array(); // Array of global sections to hide.

// PUBLIC METHODS 

    /**
     * Constructor
     * Initializes the root path of the template.
     *
     * @param path      A string.
     *                  An optional path to all template files.
     *
     * @see #set_root
     */
    function TemplateMgr($path = "")
    {
        $this->set_root($path);
    }

    function get_root() {
       return $this->mPath;
    }
    
    /**
     * Define a template and pass in the global styles
     *
     * @param handle    A string.
     *                  The name of the handle to assign a template to.
     * @param template  A string.
     *                  The path to a template file.  Optionally this path can be
     *                  relative to the root path specified in the constructor.
     */
    function define($handle, $template)
    {
        $this->mBlockHandles[$handle] = yats_define($this->mPath . $template);

        yats_assign($this->mBlockHandles[$handle], $this->mGlobalAssignArray);

        foreach($this->mGlobalHideSectionArray as $key => $hidden) {
           yats_hide($this->mBlockHandles[$handle], $key, $hidden);
        }

        $this->mBlockKeys = array($handle => array());
    }

    /**
     * UnDefine a previously defined template
     *
     * @param handle    A string.
     *                  The name of the handle a previously defined template.
     */
    function undefine($handle)
    {
        unset($this->mBlockHandles[$handle]);
    }

    /**
     * UnDefine all previously defined templates, keys, and assigns
     *
     * This can be useful when an error condition is encountered late
     * in the application, and it is necessary to display an error
     * page instead of the previously defined templates.
     */
    function reset()
    {
        $this->mBlockHandles        = array();
        $this->mBlockKeys           = array();
        $this->mGlobalAssignArray   = array();
    }

    /**
     * This method assigns values to the substitution variables in templates, 
     * that are in the form {{SUBST_VAR}}. To assign the value "10 pickles" to
     * {{SUBST_VAR}}, in the template with the handle "page", you would call:
     * $GLOBALS[gTplMgr]->assign("page", array("SUBST_VAR" => "10 Pickles"));
     *
     * For implicit looping: you can pass an array in the form: 
     *   $key_val_array = array($key => array($var1 => $val1,
     *                                        $var2 => $val2
     *                                       )
     *                         );
     *
     * Implicit looping is when you assign multiple values to one or more 
     * variables in a section. The parser knows to loop, like in this example:
     *
     * <table>
     * {{section:row}}
     * <tr>
     * <td>{{FOO}}</td><td>{{BAR}}</td>
     * </tr>
     * {{/section:row}}
     * </table>
     *
     * If you pass in three values for FOO and three values for BAR, it will
     * 'loop' three times.
     *
     * @param handle          A string.
     *                        The name of the template handle to assign values to.
     * @param key_val_array   An array.
     *                        The set of template variables and their corresponding 
     *                        values to assign.
     *
     * @return A boolean value of TRUE for success and FALSE for failure.
     */
    function assign($handle, $key_val_array)
    {
        if (is_array($key_val_array)) {
            foreach ($key_val_array as $key => $val) {
                $this->mBlockKeys[$handle][$key] = $val;
            }
            return yats_assign($this->mBlockHandles[$handle], $key_val_array);
        } else {
           return FALSE;
        }
    }

    /**
     * This method will assign the values in the key/val array to all instances of 
     * the substitution variables given as keys -- throughout all templates.
     * This is a good way to implement global styles, by passing a key val array 
     * of global styles to global_assign(), giving us use of substitution variables
     * like {{DEFAULT_FONT}} throughout all templates on the site.
     *
     * @param key_val_array   An array.
     *                        The set of template variables and their corresponding 
     *                        values to assign globally.
     */
    function global_assign($key_val_array)
    {
        // Add elements to $this->mGlobalAssignArray
        foreach ($key_val_array as $key => $val) {
            $this->mGlobalAssignArray[$key] = $val;
        }

        // Propagate elements through all templates
        foreach ($this->mBlockHandles as $key => $val) {
            yats_assign($val, $key_val_array);
        }
    }

    /**
     * Get a value assigned to a template.
     *
     * @param handle    A string.
     *                  The name of the template handle to assign values to.
     * @param key       A string.
     *                  The key of a key / value pair assigned to a template.
     *
     * @return The assigned value of key.
     */
    function get_assigned($handle, $key)
    {
        return $this->mBlockKeys[$handle][$key];
    }
    
    /**
     * Get a value globally assigned.
     *
     * @param key    A string.
     *               The key of a key / value pair globally assigned.
     *
     * @return The globally assigned value.               
     */
    function get_global_assigned($key)
    {
        return $this->mGlobalAssignArray[$key];
    }

    /**
     * This method will take a section of HTML in a template file, defined by 
     * the template language delimiters
     *
     *     {{section:foo}}<html blah blah blah>{{/section:foo}}
     *
     * and explicitly show it. Since "show" is the default behavior for a 
     * section, this is only really useful when used with "hide".
     *
     * @param handle    A string.
     *                  The name of the template handle to assign values to.
     * @param section   A string.
     *                  The name of the section to hide.
     * @param rows      An array of int or a scalar int.
     *                  Particular rows of the parent section for which to show this section. (1 based)
     *
     * @return A boolean value of TRUE for success and FALSE for failure.
     */
    function show_section($handle, $section, $rows=null)
    {
       return $this->show_section_worker($handle, $section, $rows, 1);
    }

    function global_show_section($section, $rows=null, $ignored_array=null ) 
    {
       foreach($this->mBlockHandles as $key => $handle) {
          if(!is_array($ignored_array) || !in_array($key, $ignored_array)) {
             $this->show_section_worker($key, $section, $rows, 1);
          }
       }
       $this->mGlobalHideSectionArray[$section] = false;
    }

    /**
     * This method will take a section of HTML in a template file, defined by 
     * the template language delimiters
     *
     *     {{section:foo}}<html blah blah blah>{{/section:foo}}
     *
     * and explicitly hide it.
     *
     * @param handle    A string.
     *                  The name of the template handle to assign values to.
     * @param section   A string.
     *                  The name of the section to hide.
     * @param rows      An array of int or a scalar int.
     *                  Particular rows of the parent section for which to hide this section. (1 based)
     *                  
     *
     * @return A boolean value of TRUE for success and FALSE for failure.
     */
    function hide_section($handle, $section, $rows=null)
    {
       return $this->show_section_worker($handle, $section, $rows, 0);
    }

    function global_hide_section($section, $rows=null, $ignored_array=null )
    {
       foreach($this->mBlockHandles as $key => $handle) {
          if(!is_array($ignored_array) || !in_array($key, $ignored_array)) {
             $this->show_section_worker($key, $section, $rows, 0);
          }
       }
       $this->mGlobalHideSectionArray[$section] = true;
    }

    /**
     * This method will take a template file defined in $handle, and do all of the 
     * variable substitutions that it can (as well as any shows and hides), and then
     * it will assign that to $tplvar in the template file defined by the handle $parent.
     *
     * @param handle    A string.
     *                  The name of the template handle to assign values to.
     * @param tplvar    A string.
     *                  The name of a template variable in the parent handle.
     * @param parent    A string.
     *                  The name of the template handle to parse into.
     */
    function parse($handle, $tplvar, $parent) {
        yats_assign($this->mBlockHandles[$parent], $tplvar, yats_getbuf($this->mBlockHandles[$handle]));
    }

    /**
     * Substitute all of the variables with their values in a template ($handle)
     * and then globally assign the resulting HTML to the variable $tplvar.
     *
     * @param handle    A string.
     *                  The name of the template handle parse.
     * @param tplvar    A string.
     *                  The name of a template variable to parse into..
     */
    function global_parse($handle, $tplvar)
    {
        $this->global_assign(array($tplvar => yats_getbuf($this->mBlockHandles[$handle])));
    }

    /**
     * This method (used like this: $foo = $GLOBALS[gTplMgr]->parse_to_string($handle);) will quite 
     * literally return the contents of $handle with all completed substitutions, shows, 
     * hides, etc.. that it can complete.
     *
     * This might be useful, for example, if you were using the implicit looping
     * feature of the template language, and you wanted to assign various parsed
     * templates to an array that could then be passed to a template.
     *
     * php:
     *
     *     for ($i = 0; $i < $num_products; $i++) {
     *         $prod_array[] = $t->parse_to_string($handle . $i);
     *     }
     *     $t->assign("page", array("PRODUCTS" => $prod_array));
     *
     * html:
     *
     *    <table>
     *    {{section:products}}
     *    <tr>
     *    <td>{{PRODUCTS}}</td>
     *    </tr>
     *    {{/section:products}}
     *    </table>
     *
     * @param handle    A string.
     *                  The name of the template handle parse.
     *
     * @return The string of HTML produced by parsing $handle.
     */
    function parse_to_string($handle)
    {
        return yats_getbuf($this->mBlockHandles[$handle]);
    }

    /**
     * like parse_to_string, but iterates over all defined templates.
     *
     * @param ignored_array    An array
     *                  list of defined templates to ignore
     */
    function global_parse_to_string($ignored_array=null) {
       $buf = '';
       foreach($this->mBlockHandles as $key => $handle) {
          if(!is_array($ignored_array) || !in_array($key, $ignored_array)) {
             $buf .= yats_getbuf($handle);
          }
       }
       return $buf;
    }

    /**
     * Count the number of assigned key/val pairs for a template
     *
     * @return An integer count of the assigned values.
     */
    function count_vars($handle)
    {
        if ($var_array = yats_getvars($this->mBlockHandles[$handle])) {
            foreach ($var_array as $key => $val) {
                $i++;
            }
            return $i;
        } else {
            return FALSE;
        }
    }
      
    /**
     * Count the number of globally assigned key/val pairs for a template.
     *
     * @return An integer count of globally assigned values. 
     */
    function globals_count()
    {
        return count($this->mGlobalAssignArray);
    }

// PRIVATE METHODS 

    /**
     * Private function for terminating paths in '/'
     *
     * @param root      A string.
     *                  A file path.
     */
    function set_root ($root)
    {
        $trailer = substr($root,-1);
        
        if ((ord($trailer)) != 47) {
            $root = "$root". chr(47);
        }
        
        if (is_dir($root)) {
            $this->mPath = $root;
        } else {
            $this->mPath = "";
        }
    }

    /* private method. interface may change */
    function show_section_worker($handle, $section, $rows, $show) {
       if(is_array($rows)) {
          foreach($rows as $row) {
             yats_hide($this->mBlockHandles[$handle], $section, !$show, $row);
          }
          return true;
       }
       else if($rows) {
          return yats_hide($this->mBlockHandles[$handle], $section, !$show, $rows);
       }
       else {
          return yats_hide($this->mBlockHandles[$handle], $section, !$show);
       }
    }

}

?>
