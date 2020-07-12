<?php

// rename/copy this file into (dirname).php


if( ! defined( 'XOOPS_ROOT_PATH' ) ) exit ;

require_once XOOPS_TRUST_PATH.'/modules/pico/include/common_functions.php' ;
require_once XOOPS_TRUST_PATH.'/modules/pico/include/main_functions.php' ;
require_once XOOPS_TRUST_PATH.'/modules/pico/class/XmobilePlugin.class.php' ;

$mydirname = substr( basename( __FILE__ ) , 0 , -4 ) ;
$Mydirname = ucfirst( $mydirname ) ;

eval('
class Xmobile'.$Mydirname.'Plugin extends XmobilePicoPluginAbstract
{
	function Xmobile'.$Mydirname.'Plugin()
	{
		$this->__construct( "'.$mydirname.'" ) ;
	}
}

class Xmobile'.$Mydirname.'PluginHandler extends XmobilePicoPluginHandlerAbstract
{
	function Xmobile'.$Mydirname.'PluginHandler( $db )
	{
		$this->__construct( "'.$mydirname.'" , $db ) ;
	}
}
' ) ;


?>