<?php
//wye , never-ever.info  //NE+
//ver 2.1 , 2007-12-21
/* ---------------------------------------------------------------------------*/
if( !defined('XOOPS_ROOT_PATH') ) exit();

function xmobile_option($conf_name) {
  $module_handler = & xoops_gethandler('module');
  $module = $module_handler->getByDirname(basename(dirname(dirname(__FILE__))));
  $mid = $module->getVar('mid');
  $xmobileConfig = & xoops_gethandler('config');
  $records = & $xmobileConfig->getConfigList($mid);
  $value = $records[$conf_name];
  return ($value);
}


// SHOW ------------------------------------------
function b_xmobile_qr_show( $options )
{
  global $xoopsDB , $xoopsUser , $xoopsModule ;

  $qr_num = empty( $options[0] ) ? 0 : intval( $options[0] ) ;
  $qrimg_dir = empty( $options[1] ) ? "" : htmlspecialchars( $options[1] , ENT_QUOTES ) ;

  $url = XOOPS_URL ."/modules/".basename(dirname(dirname(__FILE__)));
  $block = array() ;
  if( $qr_num == 0 && $qrimg_dir!="" && file_exists( XOOPS_ROOT_PATH."/modules/$qrimg_dir/qrcode_image.php" ) ){
    //CREATE qr code strings(URL) 
    $url = '';
    //ROOT files
    $scriptname = str_replace( array('/','.php') , '' , strtolower($_SERVER['SCRIPT_NAME']) );
    if( in_array($scriptname,array('user','lostpass','register','userinfo','viewpmsg')) ){
      if( $scriptname == 'user' ) $scriptname = 'login' ;
      if( $scriptname == 'viewpm' ) $scriptname = 'pmessage' ;
      $scriptname = ( $scriptname == 'register' && xmobile_option('allow_register') ) ? $scriptname : '' ;
      if( $scriptname == 'userinfo' && is_object($xoopsUser) ){
        $uid = $xoopsUser->getVar('uid');
        $url = XOOPS_URL . "/modules/".basename(dirname(dirname(__FILE__)))."/?act=userinfo&uid={$uid}";
      } elseif( !empty($scriptname) ) {
        $url = XOOPS_URL . "/modules/".basename(dirname(dirname(__FILE__)))."/?act=". htmlspecialchars($scriptname,ENT_QUOTES) ;
      }
    }
    //module check
    if( empty($url) ){
      $fDirname = '' ;
      if(is_object($xoopsModule)) $fDirname = $xoopsModule->getVar('dirname') ;
      $usemodule = xmobile_option('modules_can_use');
      if( !empty($fDirname) && in_array($fDirname,$usemodule) ){
        $url = XOOPS_URL . "/modules/".basename(dirname(dirname(__FILE__)))."/?act=plugin&plg=". htmlspecialchars($fDirname,ENT_QUOTES);
        //detail ID
        $detailUrl = '';
        if( file_exists(XOOPS_ROOT_PATH."/modules/".basename(dirname(dirname(__FILE__)))."/plugins/{$fDirname}.php") ){
          require_once XOOPS_ROOT_PATH."/modules/".basename(dirname(dirname(__FILE__)))."/class/Plugin.class.php" ;
          require_once XOOPS_ROOT_PATH."/modules/".basename(dirname(dirname(__FILE__)))."/plugins/{$fDirname}.php" ;
          $pluginClassName = 'Xmobile'. ucfirst($fDirname) .'PluginHandler';
          if( class_exists($pluginClassName) ){
            $hl =& new $pluginClassName($xoopsDB);
            $item_id_fld  = $hl->item_id_fld ;
            $item_cid_fld = $hl->item_cid_fld ;
            if( !isset($item_cid_fld) ) $item_cid_fld = $hl->category_id_fld ;
            if( isset($item_id_fld) && isset($_GET[$item_id_fld]) ){
              $detailUrl = "&view=detail&". $item_id_fld ."=". intval($_GET[$item_id_fld]);
              $url .= $detailUrl ;
            }
            //category ID
            if( isset($item_cid_fld) ){
              if( isset($_GET[$item_cid_fld]) ){
                if( empty($detailUrl) ){
                  $url .= "&view=list&" . $item_cid_fld ."=". intval($_GET[$item_cid_fld]);
                }else{
                  $url .= "&". $item_cid_fld ."=". intval($_GET[$item_cid_fld]);
                }
              }elseif( isset($_GET[$item_id_fld]) ){
                //get category ID
                $hl->item_criteria =& new CriteriaCompo();
                $hl->item_criteria->add(new Criteria( $item_id_fld , intval($_GET[$item_id_fld]) ));
                $hl->tableName = $xoopsDB->prefix($hl->itemTableName);
                $itemObjectArray = $hl->getObjects($hl->item_criteria);
                if( isset($itemObjectArray) ){
                  $cid = $itemObjectArray[0]->getVar( $item_cid_fld );
                  if( isset($cid) ){
                    if( empty($detailUrl) ){
                      $url .= "&view=list&" . $item_cid_fld ."=". $cid ;
                    }else{
                      $url .= "&". $item_cid_fld ."=". $cid ;
                    }
                  }
                }
              }
            }//END if( isset($item_cid_fld) )
          }//END if( class_exists($pluginClassName) )
        }//END if( file_exists(XOOPS_ROOT_PATH."/modules/".basename(dirname(dirname(__FILE__)))."/plugins/{$fDirname}.php") )
      }//END if( !empty($fDirname) && in_array($fDirname,$usemodule) )
    }
    //default , xmobile TOP
    if( empty($url) ) $url = XOOPS_URL . "/modules/".basename(dirname(dirname(__FILE__)))."/";

    include_once XOOPS_ROOT_PATH . "/modules/$qrimg_dir/include/functions.php";
    $myts =& MyTextSanitizer::getInstance();
    if ( strtolower(_LANGCODE) == "ja" ){
      $urle = qrcode_convert_encoding( $url , 'SJIS' , _CHARSET );
    }
    $urle = rawurlencode( $urle );
    $urle = ereg_replace( "%20" , "+" , $urle );
    $block['qrimg'] = "<img src='".XOOPS_URL."/modules/$qrimg_dir/qrcode_image.php?d=$urle&amp;e=M&amp;s=3&amp;v=0&amp;t=P&amp;rgb=000000' alt='qrcode' />\n";
  } elseif ( $qr_num > 0 ) {
    require_once XOOPS_ROOT_PATH.'/class/xoopslists.php';
    $qrimg_array = XoopsLists :: getImgListAsArray( XOOPS_ROOT_PATH . '/modules/'.basename(dirname(dirname(__FILE__))).'/images/qr/' );
    $images = array();
    $i = 1;
    foreach( $qrimg_array as $v ) {
      $images[ $i++ ] = $v;
    }
    $block['qrimg'] = "<img src='". XOOPS_URL ."/modules/".basename(dirname(dirname(__FILE__)))."/images/qr/". htmlspecialchars( $images[$qr_num] , ENT_QUOTES ) ."' alt='qrcode' />\n";
  }

  $access_terminal = xmobile_option('access_terminal');  //アクセスを許可する端末 0:ホスト名から、1:エージェントから、2:全許可
  $block['msg'] = ( $access_terminal==2 ) ? '<a href="'.str_replace('&','&amp;',$url).'" target="_blank">'._BLOCK_XMOBILE_QR_MSG.'</a>' : _BLOCK_XMOBILE_QR_MSG ;

  return $block ;
}



// EDIT ------------------------------------------
function b_xmobile_qr_edit( $options )
{
  $qrimage = empty( $options[0] ) ? 0 : intval( $options[0] ) ;
  $qr_mod_dir = empty( $options[1] ) ? "" : htmlspecialchars( $options[1] , ENT_QUOTES ) ;

  require_once XOOPS_ROOT_PATH.'/class/xoopslists.php';
  $qrimg_array = XoopsLists :: getImgListAsArray( XOOPS_ROOT_PATH . '/modules/'.basename(dirname(dirname(__FILE__))).'/images/qr/' );

  $i = 0;
  $qr  = "<select name='options[0]'>";
  $qr .= "<option value='". $i ."' ".($qrimage==$i?"selected='selected'":"").">---</option>";
  foreach( $qrimg_array as $v ) {
    $qr .= "<option value='". ++$i ."' ".($qrimage==$i?"selected='selected'":"").">".htmlspecialchars( $v , ENT_QUOTES )."</option>";
  }
  $qr .= "</select>";

  $form = _BLOCK_XMOBILE_QR_CODE . "&nbsp;" . $qr . "&nbsp;" . _BLOCK_XMOBILE_QR_CODE_DESC ."<br />\n" .
      _BLOCK_XMOBILE_QR_MOD_DIR . "<input type='text' name='options[1]' value='". $qr_mod_dir ."'>";

  return $form ;
}
?>