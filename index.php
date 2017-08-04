<?php
/**
 * Created by PhpStorm.
 * User: 1002571
 * Date: 2017/8/2
 * Time: 15:51
 */

 require_once "./vendor/autoload.php";

echo "QUERY_STRING:".$_SERVER['QUERY_STRING'];
echo "<br>";
echo "REQUEST_URI:".$_SERVER['REQUEST_URI'];
echo "<br>";
echo "PHP_SELF:".$_SERVER['PHP_SELF'];
echo "<br>";
echo "SCRIPT_NAME:".$_SERVER['SCRIPT_NAME'];
echo "<br>";
echo "host:".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING'];
