<?php
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997, 1998, 1999, 2000, 2001 The PHP Group             |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Ulf Wendel <ulf.wendel@phpdoc.de>                           |
// |          Sebastian Bergmann <sb@sebastian-bergmann.de>               |
// +----------------------------------------------------------------------+
//
// $Id: file.php,v 1.1.1.1 2002/06/19 00:15:29 gente_libre Exp $

require_once 'Cache/Container.php';

/**
* Stores cache contents in a file.
*
* @author   Ulf Wendel  <ulf.wendel@phpdoc.de>
* @version  $Id: file.php,v 1.1.1.1 2002/06/19 00:15:29 gente_libre Exp $
*/
class Cache_Container_file extends Cache_Container {

    /**
    * Directory where to put the cache files.
    *
    * @var  string  Make sure to add a trailing slash
    */
    var $cache_dir = "";

    /**
    * Filename prefix for cache files.
    *
    * You can use the filename prefix to implement a "domain" based cache or just
    * to give the files a more descriptive name. The word "domain" is borroed from
    * a user authentification system. One user id (cached dataset with the ID x)
    * may exists in different domains (different filename prefix). You might want
    * to use this to have different cache values for a production, development and
    * quality assurance system. If you want the production cache not to be influenced
    * by the quality assurance activities, use different filename prefixes for them.
    *
    * I personally don't think that you'll never need this, but 640kb happend to be
    * not enough, so... you know what I mean. If you find a useful application of the
    * feature please update this inline doc.
    *
    * @var  string
    */
    var $filename_prefix = "";

    /**
    * Creates the cache directory if neccessary
    *
    * @param    array   Config options: ["cache_dir" => ..., "filename_prefix" => ...]
    */
    function Cache_Container_file($options = "") {
        if (is_array($options))
            $this->setOptions($options, array("cache_dir", "filename_prefix"));

        clearstatcache();

        //make relative paths absolute for use in deconstructor.
        // it looks like the deconstructor has problems with relative paths
        if (preg_match("/\.+/",$this->cache_dir))
            $this->cache_dir=realpath(getcwd()."/".$this->cache_dir)."/";

        if (!file_exists($this->cache_dir) || !is_dir($this->cache_dir))
            mkdir($this->cache_dir, 0755);
    } // end func contructor

    function fetch($id, $group) {
        $file = $this->getFilename($id, $group);
        if (!file_exists($file))
            return array(NULL, NULL, NULL);

        // retrive the content
        if (!($fh = @fopen($file, "rb")))
            return new Cache_Error("Can't access cache file '$file'. Check access rights and path.", __FILE__, __LINE__);

        // file format:
        // 1st line: expiration date
        // 2nd line: user data
        // 3rd+ lines: cache data
        $expire = trim(fgets($fh, 11));
        $userdata = trim(fgets($fh, 257));
        $cachedata = $this->decode(fread($fh, filesize($file)));
        fclose($fh);

        return array($expire, $cachedata, $userdata);
    } // end func fetch

    /**
    * Stores a dataset.
    *
    * WARNING: If you supply userdata it must not contain any linebreaks,
    * otherwise it will break the filestructure.
    */
    function save($id, $cachedata, $expires, $group, $userdata) {
        $this->flushPreload($id, $group);

        $file = $this->getFilename($id, $group);
        if (!($fh = @fopen($file, "wb")))
            return new Cache_Error("Can't access '$file' to store cache data. Check access rights and path.", __FILE__, __LINE__);

        // file format:
        // 1st line: expiration date
        // 2nd line: user data
        // 3rd+ lines: cache data
        $expires = $this->getExpiresAbsolute($expires);
        fwrite($fh, $expires . "\n");
        fwrite($fh, $userdata . "\n");
        fwrite($fh, $this->encode($cachedata));

        fclose($fh);

        // I'm not sure if we need this
        touch($file);

        return true;
    } // end func save

    function delete($id, $group) {
        $this->flushPreload($id, $group);

        $file = $this->getFilename($id, $group);
        if (file_exists($file)) {

            $ok = unlink($file);
            clearstatcache();

            return $ok;
        }

        return false;
    } // end func delete

    function flush($group) {
        $this->flushPreload();
        $dir = ($group) ? $this->cache_dir . $group . "/" : $this->cache_dir;

        $num_removed = $this->deleteDir($dir);
        clearstatcache();

        return $num_removed;
    } // end func flush

    function idExists($id, $group) {
        return file_exists($this->getFilename($id, $group));

    } // end func idExists

    /**
    * Deletes all expired files.
    *
    * Garbage collection for files is a rather "expensive", "long time"
    * operation. All files in the cache directory have to be examined which
    * means that they must be opened for reading, the expiration date has to be
    * read from them and if neccessary they have to be unlinked (removed).
    * If you have a user comment for a good default gc probability please add it to
    * to the inline docs.
    *
    * @param    string  directory to examine - don't sets this parameter, it's used for a
    *                   recursive function call!
    */
    function garbageCollection($dir = "") {
        $this->flushPreload();

        if (!$dir)
            $dir = $this->cache_dir;

        if (!($dh = opendir($dir)))
            return new Cache_Error("Can't access cache directory '$dir'. Check permissions and path.", __FILE__, __LINE__);

        while ($file = readdir($dh)) {
            if ("." == $file || ".." == $file)
                continue;

            $file = $dir . $file;
            if (is_dir($file)) {
                $this->garbageCollection($file . "/");
                continue;
            }

            // skip trouble makers but inform the user
            if (!($fh = @fopen($file, "rb"))) {
                new Cache_Error("Can't access cache file '$file', skipping it. Check permissions and path.", __FILE__, __LINE__);
                continue;
            }

            $expire = fgets($fh, 11);
            fclose($fh);

            // remove if expired
            if ($expire && $expire <= time() && !unlink($file))
                new Cache_Error("Can't unlink cache file '$file', skipping. Check permissions and path.", __FILE__, __LINE__);
        }

        closedir($dh);

        // flush the disk state cache
        clearstatcache();
    } // end func garbageCollection

    /**
    * Returns the filename for the specified id.
    *
    * @param    string  dataset ID
    * @param    string  cache group
    * @return   string  full filename with the path
    * @access   public
    */
    function getFilename($id, $group) {
        static $group_dirs = array();

        if (isset($group_dirs[$group]))
            return $group_dirs[$group] . $this->filename_prefix . $id;

        $dir = $this->cache_dir . $group . "/";
        if (!file_exists($dir)) {
            mkdir($dir, 0755);
            clearstatcache();
        }

        $group_dirs[$group] = $dir;

        return $dir . $this->filename_prefix . $id;
    } // end func getFilename

    /**
    * Deletes a directory and all files in it.
    *
    * @param    string  directory
    * @return   integer number of removed files
    * @throws   Cache_Error
    */
    function deleteDir($dir) {
        if (!($dh = opendir($dir)))
            return new Cache_Error("Can't remove directory '$dir'. Check permissions and path.", __FILE__, __LINE__);

        $num_removed = 0;

        while ($file = readdir($dh)) {
            if ("." == $file || ".." == $file)
                continue;

            $file = $dir . $file;
            if (is_dir($file)) {
                $file .= "/";
                $num = $this->deleteDir($file . "/");
                if (is_int($num))
                    $num_removed += $num;
            } else {
                if (unlink($file))
                    $num_removed++;
            }
        }
        // according to php-manual the following is needed for windows installations.
        closedir($dh);
        unset( $dh);
        if ($dir != $this->cache_dir) {  //delete the sub-dir entries  itself also, but not the cache-dir.
            rmDir($dir);
            $num_removed++;
        }

        return $num_removed;
    } // end func deleteDir
} // end class file
?>
