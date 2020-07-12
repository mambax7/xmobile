<?php
/**
* ¥·¥¹¥Æ¥àÌ¾    ¡§·ÈÂÓ³¨Ê¸»ú¼«Æ°ÊÑ´¹
* ¥×¥í¥°¥é¥àÌ¾  ¡§MobileClass
*
* :    :    :    :    :    :    :    :    :    :    :    :    :    :    :    :    :
* [¥×¥í¥°¥é¥à³µÍ×]
* DoCoMo¸þ¤±¤ËÆþÎÏ¤·¤¿³¨Ê¸»ú¤ò¡¢¥¢¥¯¥»¥¹¤·¤Æ¤­¤¿¥­¥ã¥ê¥¢¤Ë¹ç¤ï¤»¤Æ
* ¼«Æ°Åª¤Ë¸ß´¹¤¹¤ë³¨Ê¸»ú(¥³¡¼¥É)¤ËÃÖ´¹¤·¤Þ¤¹¡£
* DoCoMo³¨Ê¸»ú¤ÎÆþÎÏ¤Ï¡¢´Ø¿ô¤Î°ú¿ô¤Ë³¨Ê¸»úÆþÎÏ¥½¥Õ¥È¤ò»È¤Ã¤ÆÄ¾ÀÜÆþÎÏ¤¹¤ë¤«¡¢
* 16¿ÊË¡¤ò°ú¿ô¤ËÍ¿¤¨¤ë»ö¤Ë¤è¤ê¼Â¸½¤·¤Þ¤¹(¿ä¾©¤Ï16¿ÊË¡¤Ç¤¹)
*
* [¸Æ½Ð¸µ]
* Nothing
*
* [¸Æ½ÐÀè]
* Nothing
*
* [¥Ñ¥é¥á¡¼¥¿]
* Nothing
* :    :    :    :    :    :    :    :    :    :    :    :    :    :    :    :    :
*
* @since            2006/11/20
* @auther           T.Kotaka
*
* @version          1.2.0
*
* [²þÈÇÍúÎò]
* 000001    2007/01/22    16¿ÊË¡¤Ë¤è¤ëÆþÎÏ¤ò¥µ¥Ý¡¼¥È¤·¤Þ¤·¤¿¡£
* 000002    2007/01/22    ¥æ¡¼¥¶¡¼¥¨¡¼¥¸¥§¥ó¥È¤¬¡ÖSoftBank¡×¤ÎºÝ¤Ë¡¢³¨Ê¸»úÊÑ´¹¤µ¤ì¤Ê¤¤ÉÔ¶ñ¹ç¤ò½¤Àµ¤·¤Þ¤·¤¿¡£
* 000003    2007/01/23    EzWeb¤Ë¤ª¤¤¤Æ¡¢³¨Ê¸»ú¤ÎÂåÂØÊ¸»ú¤¬½ÐÎÏ½ÐÍè¤Ê¤¤ÉÔ¶ñ¹ç¤ò½¤Àµ¤·¤Þ¤·¤¿¡£
*/

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ¾åµ­¤ò¸«ËÜ¤Ëxmbile¸þ¤±¤Ë²þÂ¤
class XmobileEmoji
{
	var $EMOJI         = array();     // ³¨Ê¸»ú¥Æ¡¼¥Ö¥ë
	var $InputMode     = 0;           // 0 Or 1 ¡Ê0¡§¥Ð¥¤¥Ê¥êÆþÎÏ¡¢1¡§³¨Ê¸»úÄ¾ÀÜÆþÎÏ¡Ë

	var $docomo_char = array();
	var $au_char = array();
	var $softbank_char = array();
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ¥³¥ó¥¹¥È¥é¥¯¥¿
	function XmobileEmoji()
	{
		$this->__construct();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ¥³¥ó¥¹¥È¥é¥¯¥¿(PHP5ÂÐ±þ)
	function __construct()
	{
		// ³¨Ê¸»ú¥Æ¡¼¥Ö¥ë¥»¥Ã¥È
		$this->_EmojiTable();
		// ¥æ¡¼¥¶¡¼¥¨¡¼¥¸¥§¥ó¥È¥»¥Ã¥È
//		$this->getUserAgent();
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// °ú¿ô¤Ç»ØÄê¤µ¤ì¤¿Ê¸»úÎó¤ò¡¢¥¨¡¼¥¸¥§¥ó¥È¤Ë¤¢¤ï¤»¤Æ³¨Ê¸»úÊÑ´¹
	function convertStr($str_input='', $carrier=0)
	{
		$before_str = array();
		$after_str  = array();

		foreach ($this->EMOJI as $code=>$conv_array)
		{
			$before_str[] = '[%'.$code.'%]';
			$after_str[] = $this->convert($code, $carrier);
		}

		return str_replace($before_str, $after_str, $str_input);
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// °ú¿ô¤Ç»ØÄê¤µ¤ì¤¿Ê¸»úÎó¤ò¡¢¥¨¡¼¥¸¥§¥ó¥È¤Ë¤¢¤ï¤»¤Æ³¨Ê¸»úÊÑ´¹
	function convert($InputEmoji='', $carrier=0)
	{
		switch ($this->InputMode)
		{
			case 0:
				$InputEmoji = strtoupper($InputEmoji);
				break;
			case 1:
				$InputEmoji = strtoupper(bin2hex($InputEmoji));
				break;
			default:
				break;
		}

		switch ($carrier)
		{
			case 1:
				// DoCoMo
				$InputEmoji = pack("H*",$InputEmoji);
				break;
			case 2:
				// EzWeb
				$InputEmoji = is_numeric($this->EMOJI[$InputEmoji]['EzWeb'])?"<img localsrc=" . $this->EMOJI[$InputEmoji]['EzWeb'] . ">":$this->EMOJI[$InputEmoji]['EzWeb'];
				break;
			case 3:
				// SoftBank
				$InputEmoji = $this->EMOJI[$InputEmoji]['SB'];
				break;
			default:
				// PC
				$InputEmoji = "<img src='./images/emoji/" . $InputEmoji . ".gif'>";
				break;
		}

		return $InputEmoji;
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function _EmojiTable()
	{
		$this->EMOJI['F89F'] = array('TIT' => 'À²¤ì', 'EzWeb' => '44', 'SB' => '$Gj');
		$this->EMOJI['F8A0'] = array('TIT' => 'ÆÞ¤ê', 'EzWeb' => '107', 'SB' => '$Gi');
		$this->EMOJI['F8A1'] = array('TIT' => '±«', 'EzWeb' => '95', 'SB' => '$Gk');
		$this->EMOJI['F8A2'] = array('TIT' => 'Àã', 'EzWeb' => '191', 'SB' => '$Gh');
		$this->EMOJI['F8A3'] = array('TIT' => 'Íë', 'EzWeb' => '16', 'SB' => '$E]');
		$this->EMOJI['F8A4'] = array('TIT' => 'ÂæÉ÷', 'EzWeb' => '190', 'SB' => '$Pc');
		$this->EMOJI['F8A5'] = array('TIT' => 'Ì¸', 'EzWeb' => '305', 'SB' => '[Ì¸]');
		$this->EMOJI['F8A6'] = array('TIT' => '¾®±«', 'EzWeb' => '481', 'SB' => '$P\');
		$this->EMOJI['F8A7'] = array('TIT' => '²´ÍÓºÂ', 'EzWeb' => '192', 'SB' => '$F_');
		$this->EMOJI['F8A8'] = array('TIT' => '²´µíºÂ', 'EzWeb' => '193', 'SB' => '$F`');
		$this->EMOJI['F8A9'] = array('TIT' => 'ÁÐ»ÒºÂ', 'EzWeb' => '194', 'SB' => '$Fa');
		$this->EMOJI['F8AA'] = array('TIT' => '³ªºÂ', 'EzWeb' => '195', 'SB' => '$Fb');
		$this->EMOJI['F8AB'] = array('TIT' => '»â»ÒºÂ', 'EzWeb' => '196', 'SB' => '$Fc');
		$this->EMOJI['F8AC'] = array('TIT' => '²µ½÷ºÂ', 'EzWeb' => '197', 'SB' => '$Fd');
		$this->EMOJI['F8AD'] = array('TIT' => 'Å·ÇéºÂ', 'EzWeb' => '198', 'SB' => '$Fe');
		$this->EMOJI['F8AE'] = array('TIT' => 'ê¸ºÂ', 'EzWeb' => '199', 'SB' => '$Ff');
		$this->EMOJI['F8AF'] = array('TIT' => '¼Í¼êºÂ', 'EzWeb' => '200', 'SB' => '$Fg');
		$this->EMOJI['F8B0'] = array('TIT' => '»³ÍÓºÂ', 'EzWeb' => '201', 'SB' => '$Fh');
		$this->EMOJI['F8B1'] = array('TIT' => '¿åÉÓºÂ', 'EzWeb' => '202', 'SB' => '$Fi');
		$this->EMOJI['F8B2'] = array('TIT' => 'µûºÂ', 'EzWeb' => '203', 'SB' => '$Fj');
		$this->EMOJI['F8B3'] = array('TIT' => '¥¹¥Ý¡¼¥Ä', 'EzWeb' => '-', 'SB' => '-');
		$this->EMOJI['F8B4'] = array('TIT' => 'Ìîµå', 'EzWeb' => '45', 'SB' => '$G6');
		$this->EMOJI['F8B5'] = array('TIT' => '¥´¥ë¥Õ', 'EzWeb' => '306', 'SB' => '$G4');
		$this->EMOJI['F8B6'] = array('TIT' => '¥Æ¥Ë¥¹', 'EzWeb' => '220', 'SB' => '$G5');
		$this->EMOJI['F8B7'] = array('TIT' => '¥µ¥Ã¥«¡¼', 'EzWeb' => '219', 'SB' => '$G8');
		$this->EMOJI['F8B8'] = array('TIT' => '¥¹¥­¡¼', 'EzWeb' => '421', 'SB' => '$G3');
		$this->EMOJI['F8B9'] = array('TIT' => '¥Ð¥¹¥±¥Ã¥È¥Ü¡¼¥ë', 'EzWeb' => '307', 'SB' => '$PJ');
		$this->EMOJI['F8BA'] = array('TIT' => '¥â¡¼¥¿¡¼¥¹¥Ý¡¼¥Ä', 'EzWeb' => '222', 'SB' => '$ER');
		$this->EMOJI['F8BB'] = array('TIT' => '¥Ý¥±¥Ã¥È¥Ù¥ë', 'EzWeb' => '308', 'SB' => '[PB]');
		$this->EMOJI['F8BC'] = array('TIT' => 'ÅÅ¼Ö', 'EzWeb' => '172', 'SB' => '$G>');
		$this->EMOJI['F8BD'] = array('TIT' => 'ÃÏ²¼Å´', 'EzWeb' => '341', 'SB' => '$PT');
		$this->EMOJI['F8BE'] = array('TIT' => '¿·´´Àþ', 'EzWeb' => '217', 'SB' => '$PU');
		$this->EMOJI['F8BF'] = array('TIT' => '¼Ö¡Ê¥»¥À¥ó¡Ë', 'EzWeb' => '125', 'SB' => '$G;');
		$this->EMOJI['F8C0'] = array('TIT' => '¼Ö¡Ê£Ò£Ö¡Ë', 'EzWeb' => '125', 'SB' => '$PN');
		$this->EMOJI['F8C1'] = array('TIT' => '¥Ð¥¹', 'EzWeb' => '216', 'SB' => '$Ey');
		$this->EMOJI['F8C2'] = array('TIT' => 'Á¥', 'EzWeb' => '379', 'SB' => '$F"');
		$this->EMOJI['F8C3'] = array('TIT' => 'Èô¹Ôµ¡', 'EzWeb' => '168', 'SB' => '$G=');
		$this->EMOJI['F8C4'] = array('TIT' => '²È', 'EzWeb' => '112', 'SB' => '$GV');
		$this->EMOJI['F8C5'] = array('TIT' => '¥Ó¥ë', 'EzWeb' => '156', 'SB' => '$GX');
		$this->EMOJI['F8C6'] = array('TIT' => 'Í¹ÊØ¶É', 'EzWeb' => '375', 'SB' => '$Es');
		$this->EMOJI['F8C7'] = array('TIT' => 'ÉÂ±¡', 'EzWeb' => '376', 'SB' => '$Eu');
		$this->EMOJI['F8C8'] = array('TIT' => '¶ä¹Ô', 'EzWeb' => '212', 'SB' => '$Em');
		$this->EMOJI['F8C9'] = array('TIT' => '£Á£Ô£Í', 'EzWeb' => '205', 'SB' => '$Et');
		$this->EMOJI['F8CA'] = array('TIT' => '¥Û¥Æ¥ë', 'EzWeb' => '378', 'SB' => '$Ex');
		$this->EMOJI['F8CB'] = array('TIT' => '¥³¥ó¥Ó¥Ë', 'EzWeb' => '206', 'SB' => '$Ev');
		$this->EMOJI['F8CC'] = array('TIT' => '¥¬¥½¥ê¥ó¥¹¥¿¥ó¥É', 'EzWeb' => '213', 'SB' => '$GZ');
		$this->EMOJI['F8CD'] = array('TIT' => 'Ãó¼Ö¾ì', 'EzWeb' => '208', 'SB' => '$Eo');
		$this->EMOJI['F8CE'] = array('TIT' => '¿®¹æ', 'EzWeb' => '99', 'SB' => '$En');
		$this->EMOJI['F8CF'] = array('TIT' => '¥È¥¤¥ì', 'EzWeb' => '207', 'SB' => '$Eq');
		$this->EMOJI['F8D0'] = array('TIT' => '¥ì¥¹¥È¥é¥ó', 'EzWeb' => '146', 'SB' => '$Gc');
		$this->EMOJI['F8D1'] = array('TIT' => 'µÊÃãÅ¹', 'EzWeb' => '93', 'SB' => '$Ge');
		$this->EMOJI['F8D2'] = array('TIT' => '¥Ð¡¼', 'EzWeb' => '52', 'SB' => '$Gd');
		$this->EMOJI['F8D3'] = array('TIT' => '¥Ó¡¼¥ë', 'EzWeb' => '65', 'SB' => '$Gg');
		$this->EMOJI['F8D4'] = array('TIT' => '¥Õ¥¡¡¼¥¹¥È¥Õ¡¼¥É', 'EzWeb' => '245', 'SB' => '$E@');
		$this->EMOJI['F8D5'] = array('TIT' => '¥Ö¥Æ¥£¥Ã¥¯', 'EzWeb' => '124', 'SB' => '$E^');
		$this->EMOJI['F8D6'] = array('TIT' => 'ÈþÍÆ±¡', 'EzWeb' => '104', 'SB' => '$O3');
		$this->EMOJI['F8D7'] = array('TIT' => '¥«¥é¥ª¥±', 'EzWeb' => '289', 'SB' => '$G\');
		$this->EMOJI['F8D8'] = array('TIT' => '±Ç²è', 'EzWeb' => '110', 'SB' => '$G]');
		$this->EMOJI['F8D9'] = array('TIT' => '±¦¼Ð¤á¾å', 'EzWeb' => '70', 'SB' => '$FV');
		$this->EMOJI['F8DA'] = array('TIT' => 'Í·±àÃÏ', 'EzWeb' => '-', 'SB' => '-');
		$this->EMOJI['F8DB'] = array('TIT' => '²»³Ú', 'EzWeb' => '294', 'SB' => '$O*');
		$this->EMOJI['F8DC'] = array('TIT' => '¥¢¡¼¥È', 'EzWeb' => '309', 'SB' => '$Q"');
		$this->EMOJI['F8DD'] = array('TIT' => '±é·à', 'EzWeb' => '494', 'SB' => '$Q#');
		$this->EMOJI['F8DE'] = array('TIT' => '¥¤¥Ù¥ó¥È', 'EzWeb' => '311', 'SB' => '-');
		$this->EMOJI['F8DF'] = array('TIT' => '¥Á¥±¥Ã¥È', 'EzWeb' => '106', 'SB' => '$EE');
		$this->EMOJI['F8E0'] = array('TIT' => 'µÊ±ì', 'EzWeb' => '176', 'SB' => '$O.');
		$this->EMOJI['F8E1'] = array('TIT' => '¶Ø±ì', 'EzWeb' => '177', 'SB' => '$F(');
		$this->EMOJI['F8E2'] = array('TIT' => '¥«¥á¥é', 'EzWeb' => '94', 'SB' => '$G(');
		$this->EMOJI['F8E3'] = array('TIT' => '¥«¥Ð¥ó', 'EzWeb' => '83', 'SB' => '$OC');
		$this->EMOJI['F8E4'] = array('TIT' => 'ËÜ', 'EzWeb' => '122', 'SB' => '$Eh');
		$this->EMOJI['F8E5'] = array('TIT' => '¥ê¥Ü¥ó', 'EzWeb' => '312', 'SB' => '$O4');
		$this->EMOJI['F8E6'] = array('TIT' => '¥×¥ì¥¼¥ó¥È', 'EzWeb' => '144', 'SB' => '$E2');
		$this->EMOJI['F8E7'] = array('TIT' => '¥Ð¡¼¥¹¥Ç¡¼', 'EzWeb' => '313', 'SB' => '$Ok');
		$this->EMOJI['F8E8'] = array('TIT' => 'ÅÅÏÃ', 'EzWeb' => '85', 'SB' => '$G)');
		$this->EMOJI['F8E9'] = array('TIT' => '·ÈÂÓÅÅÏÃ', 'EzWeb' => '161', 'SB' => '$G*');
		$this->EMOJI['F8EA'] = array('TIT' => '¥á¥â', 'EzWeb' => '395', 'SB' => '$O!');
		$this->EMOJI['F8EB'] = array('TIT' => '£Ô£Ö', 'EzWeb' => '288', 'SB' => '$EJ');
		$this->EMOJI['F8EC'] = array('TIT' => '¥²¡¼¥à', 'EzWeb' => '232', 'SB' => '[¥²¡¼¥à]');
		$this->EMOJI['F8ED'] = array('TIT' => '£Ã£Ä', 'EzWeb' => '300', 'SB' => '$EF');
		$this->EMOJI['F8EE'] = array('TIT' => '¥Ï¡¼¥È', 'EzWeb' => '414', 'SB' => '$F,');
		$this->EMOJI['F8EF'] = array('TIT' => '¥¹¥Ú¡¼¥É', 'EzWeb' => '314', 'SB' => '$F.');
		$this->EMOJI['F8F0'] = array('TIT' => '¥À¥¤¥ä', 'EzWeb' => '315', 'SB' => '$F-');
		$this->EMOJI['F8F1'] = array('TIT' => '¥¯¥é¥Ö', 'EzWeb' => '316', 'SB' => '$F/');
		$this->EMOJI['F8F2'] = array('TIT' => 'ÌÜ', 'EzWeb' => '317', 'SB' => '$P9');
		$this->EMOJI['F8F3'] = array('TIT' => '¼ª', 'EzWeb' => '318', 'SB' => '$P;');
		$this->EMOJI['F8F4'] = array('TIT' => '¼ê¡Ê¥°¡¼¡Ë', 'EzWeb' => '817', 'SB' => '$G0');
		$this->EMOJI['F8F5'] = array('TIT' => '¼ê¡Ê¥Á¥ç¥­¡Ë', 'EzWeb' => '319', 'SB' => '$G1');
		$this->EMOJI['F8F6'] = array('TIT' => '¼ê¡Ê¥Ñ¡¼¡Ë', 'EzWeb' => '320', 'SB' => '$G2');
		$this->EMOJI['F8F7'] = array('TIT' => '±¦¼Ð¤á²¼', 'EzWeb' => '43', 'SB' => '$FX');
		$this->EMOJI['F8F8'] = array('TIT' => 'º¸¼Ð¤á¾å', 'EzWeb' => '42', 'SB' => '$FW');
		$this->EMOJI['F8F9'] = array('TIT' => 'Â­', 'EzWeb' => '728', 'SB' => '$QV');
		$this->EMOJI['F8FA'] = array('TIT' => '¤¯¤Ä', 'EzWeb' => '729', 'SB' => '$G\'');
		$this->EMOJI['F8FB'] = array('TIT' => '´ã¶À', 'EzWeb' => '116', 'SB' => '[¥á¥¬¥Í]');
		$this->EMOJI['F8FC'] = array('TIT' => '¼Ö°Ø»Ò', 'EzWeb' => '178', 'SB' => '$F*');
		$this->EMOJI['F940'] = array('TIT' => '¿··î', 'EzWeb' => '321', 'SB' => '¡ü');
		$this->EMOJI['F941'] = array('TIT' => '¤ä¤ä·ç¤±·î', 'EzWeb' => '322', 'SB' => '$Gl');
		$this->EMOJI['F942'] = array('TIT' => 'È¾·î', 'EzWeb' => '323', 'SB' => '$Gl');
		$this->EMOJI['F943'] = array('TIT' => '»°Æü·î', 'EzWeb' => '15', 'SB' => '$Gl');
		$this->EMOJI['F944'] = array('TIT' => 'Ëþ·î', 'EzWeb' => '¡û', 'SB' => '¡û');
		$this->EMOJI['F945'] = array('TIT' => '¸¤', 'EzWeb' => '134', 'SB' => '$Gr');
		$this->EMOJI['F946'] = array('TIT' => 'Ç­', 'EzWeb' => '251', 'SB' => '$Go');
		$this->EMOJI['F947'] = array('TIT' => '¥ê¥¾¡¼¥È', 'EzWeb' => '169', 'SB' => '$G<');
		$this->EMOJI['F948'] = array('TIT' => '¥¯¥ê¥¹¥Þ¥¹', 'EzWeb' => '234', 'SB' => '$GS');
		$this->EMOJI['F949'] = array('TIT' => 'º¸¼Ð¤á²¼', 'EzWeb' => '71', 'SB' => '$FY');
		$this->EMOJI['F950'] = array('TIT' => '¥«¥Á¥ó¥³', 'EzWeb' => '226', 'SB' => '$OD');
		$this->EMOJI['F951'] = array('TIT' => '¤Õ¤¯¤í', 'EzWeb' => '[¤Õ¤¯¤í]', 'SB' => '[¤Õ¤¯¤í]');
		$this->EMOJI['F952'] = array('TIT' => '¥Ú¥ó', 'EzWeb' => '508', 'SB' => '¡Î¥Ú¥ó¡Ï');
		$this->EMOJI['F955'] = array('TIT' => '¿Í±Æ', 'EzWeb' => '-', 'SB' => '-');
		$this->EMOJI['F956'] = array('TIT' => '¤¤¤¹', 'EzWeb' => '[¤¤¤¹]', 'SB' => '$E?');
		$this->EMOJI['F957'] = array('TIT' => 'Ìë', 'EzWeb' => '490', 'SB' => '$Pk');
		$this->EMOJI['F95E'] = array('TIT' => '»þ·×', 'EzWeb' => '46', 'SB' => '$GM');
		$this->EMOJI['F972'] = array('TIT' => 'phone to', 'EzWeb' => '513', 'SB' => '$E$');
		$this->EMOJI['F973'] = array('TIT' => 'mail to', 'EzWeb' => '784', 'SB' => '$E#');
		$this->EMOJI['F974'] = array('TIT' => 'fax to', 'EzWeb' => '166', 'SB' => '$G+');
		$this->EMOJI['F975'] = array('TIT' => 'i¥â¡¼¥É', 'EzWeb' => '[i¥â¡¼¥É]', 'SB' => '[i¥â¡¼¥É]');
		$this->EMOJI['F976'] = array('TIT' => 'i¥â¡¼¥É¡ÊÏÈÉÕ¤­¡Ë', 'EzWeb' => '[i¥â¡¼¥É]', 'SB' => '[i¥â¡¼¥É]');
		$this->EMOJI['F977'] = array('TIT' => '¥á¡¼¥ë', 'EzWeb' => '108', 'SB' => '$E#');
		$this->EMOJI['F978'] = array('TIT' => '¥É¥³¥âÄó¶¡', 'EzWeb' => '[¥É¥³¥â]', 'SB' => '[¥É¥³¥â]');
		$this->EMOJI['F979'] = array('TIT' => '¥É¥³¥â¥Ý¥¤¥ó¥È', 'EzWeb' => '[¥É¥³¥â¥Ý¥¤¥ó¥È]', 'SB' => '[¥É¥³¥â¥Ý¥¤¥ó¥È]');
		$this->EMOJI['F97A'] = array('TIT' => 'Í­ÎÁ', 'EzWeb' => '109', 'SB' => '¡ï');
		$this->EMOJI['F97B'] = array('TIT' => 'ÌµÎÁ', 'EzWeb' => '299', 'SB' => '¡Î£Æ£Ò£Å£Å¡Ï');
		$this->EMOJI['F97D'] = array('TIT' => '¥Ñ¥¹¥ï¡¼¥É', 'EzWeb' => '120', 'SB' => '$G_');
		$this->EMOJI['F97E'] = array('TIT' => '¼¡¹àÍ­', 'EzWeb' => '118', 'SB' => '-');
		$this->EMOJI['F980'] = array('TIT' => '¥¯¥ê¥¢', 'EzWeb' => '324', 'SB' => '[CL]');
		$this->EMOJI['F981'] = array('TIT' => '¥µ¡¼¥Á¡ÊÄ´¤Ù¤ë¡Ë', 'EzWeb' => '119', 'SB' => '$E4');
		$this->EMOJI['F982'] = array('TIT' => '£Î£Å£×', 'EzWeb' => '334', 'SB' => '$F2');
		$this->EMOJI['F983'] = array('TIT' => '°ÌÃÖ¾ðÊó', 'EzWeb' => '730', 'SB' => '-');
		$this->EMOJI['F984'] = array('TIT' => '¥Õ¥ê¡¼¥À¥¤¥ä¥ë', 'EzWeb' => '¡Ö¥Õ¥ê¡¼¥À¥¤¥ä¥ë]', 'SB' => '$F1');
		$this->EMOJI['F985'] = array('TIT' => '¥·¥ã¡¼¥×¥À¥¤¥ä¥ë', 'EzWeb' => '818', 'SB' => '$F0');
		$this->EMOJI['F986'] = array('TIT' => '¥â¥Ð£Ñ', 'EzWeb' => '4', 'SB' => '[Q]');
		$this->EMOJI['F987'] = array('TIT' => '1', 'EzWeb' => '180', 'SB' => '$F<');
		$this->EMOJI['F988'] = array('TIT' => '2', 'EzWeb' => '181', 'SB' => '$F=');
		$this->EMOJI['F989'] = array('TIT' => '3', 'EzWeb' => '182', 'SB' => '$F>');
		$this->EMOJI['F98A'] = array('TIT' => '4', 'EzWeb' => '183', 'SB' => '$F?');
		$this->EMOJI['F98B'] = array('TIT' => '5', 'EzWeb' => '184', 'SB' => '$F@');
		$this->EMOJI['F98C'] = array('TIT' => '6', 'EzWeb' => '185', 'SB' => '$FA');
		$this->EMOJI['F98D'] = array('TIT' => '7', 'EzWeb' => '186', 'SB' => '$FB');
		$this->EMOJI['F98E'] = array('TIT' => '8', 'EzWeb' => '187', 'SB' => '$FC');
		$this->EMOJI['F98F'] = array('TIT' => '9', 'EzWeb' => '188', 'SB' => '$FD');
		$this->EMOJI['F990'] = array('TIT' => '0', 'EzWeb' => '325', 'SB' => '$FE');
		$this->EMOJI['F9B0'] = array('TIT' => '·èÄê', 'EzWeb' => '326', 'SB' => '$Fm');
		$this->EMOJI['F991'] = array('TIT' => '¹õ¥Ï¡¼¥È', 'EzWeb' => '51', 'SB' => '$GB');
		$this->EMOJI['F993'] = array('TIT' => '¼ºÎø', 'EzWeb' => '265', 'SB' => '$GC');
		$this->EMOJI['F994'] = array('TIT' => '¥Ï¡¼¥È¤¿¤Á¡ÊÊ£¿ô¥Ï¡¼¥È¡Ë', 'EzWeb' => '266', 'SB' => '$OG');
		$this->EMOJI['F995'] = array('TIT' => '¤ï¡¼¤¤¡Ê´ò¤·¤¤´é¡Ë', 'EzWeb' => '257', 'SB' => '$Gw');
		$this->EMOJI['F996'] = array('TIT' => '¤Á¤Ã¡ÊÅÜ¤Ã¤¿´é¡Ë', 'EzWeb' => '258', 'SB' => '$Gy');
		$this->EMOJI['F997'] = array('TIT' => '¤¬¤¯¡Á¡ÊÍîÃÀ¤·¤¿´é¡Ë', 'EzWeb' => '441', 'SB' => '$Gx');
		$this->EMOJI['F998'] = array('TIT' => '¤â¤¦¤ä¤À¡Á¡ÊÈá¤·¤¤´é¡Ë', 'EzWeb' => '444', 'SB' => '$P\'');
		$this->EMOJI['F999'] = array('TIT' => '¤Õ¤é¤Õ¤é', 'EzWeb' => '327', 'SB' => '$P&');
		$this->EMOJI['F99A'] = array('TIT' => '¥°¥Ã¥É¡Ê¾å¸þ¤­Ìð°õ¡Ë', 'EzWeb' => '731', 'SB' => '$FV');
		$this->EMOJI['F99B'] = array('TIT' => '¤ë¤ó¤ë¤ó', 'EzWeb' => '343', 'SB' => '$G^');
		$this->EMOJI['F99C'] = array('TIT' => '¤¤¤¤µ¤Ê¬¡Ê²¹Àô¡Ë', 'EzWeb' => '224', 'SB' => '$EC');
		$this->EMOJI['F99D'] = array('TIT' => '¤«¤ï¤¤¤¤', 'EzWeb' => '-', 'SB' => '-');
		$this->EMOJI['F99E'] = array('TIT' => '¥­¥¹¥Þ¡¼¥¯', 'EzWeb' => '273', 'SB' => '$G#');
		$this->EMOJI['F99F'] = array('TIT' => '¤Ô¤«¤Ô¤«¡Ê¿·¤·¤¤¡Ë', 'EzWeb' => '420', 'SB' => '$ON');
		$this->EMOJI['F9A0'] = array('TIT' => '¤Ò¤é¤á¤­', 'EzWeb' => '77', 'SB' => '$E/');
		$this->EMOJI['F9A1'] = array('TIT' => '¤à¤«¤Ã¡ÊÅÜ¤ê¡Ë', 'EzWeb' => '262', 'SB' => '$OT');
		$this->EMOJI['F9A2'] = array('TIT' => '¥Ñ¥ó¥Á', 'EzWeb' => '281', 'SB' => '$G-');
		$this->EMOJI['F9A3'] = array('TIT' => 'ÇúÃÆ', 'EzWeb' => '268', 'SB' => '$O1');
		$this->EMOJI['F9A4'] = array('TIT' => '¥à¡¼¥É', 'EzWeb' => '291', 'SB' => '$OF');
		$this->EMOJI['F9A5'] = array('TIT' => '¥Ð¥Ã¥É¡Ê²¼¸þ¤­Ìð°õ¡Ë', 'EzWeb' => '732', 'SB' => '$FX');
		$this->EMOJI['F9A6'] = array('TIT' => 'Ì²¤¤(¿çÌ²)', 'EzWeb' => '261', 'SB' => '$E\');
		$this->EMOJI['F9A7'] = array('TIT' => 'exclamation', 'EzWeb' => '2', 'SB' => '$GA');
		$this->EMOJI['F9A8'] = array('TIT' => 'exclamation&question', 'EzWeb' => '733', 'SB' => '¡ª¡©');
		$this->EMOJI['F9A9'] = array('TIT' => 'exclamation¡ß2', 'EzWeb' => '734', 'SB' => '¡ª¡ª');
		$this->EMOJI['F9AA'] = array('TIT' => '¤É¤ó¤Ã¡Ê¾×·â¡Ë', 'EzWeb' => '329', 'SB' => '-');
		$this->EMOJI['F9AB'] = array('TIT' => '¤¢¤»¤¢¤»¡ÊÈô¤Ó»¶¤ë´À¡Ë', 'EzWeb' => '330', 'SB' => '$OQ');
		$this->EMOJI['F9AC'] = array('TIT' => '¤¿¤é¡¼¤Ã¡Ê´À¡Ë', 'EzWeb' => '263', 'SB' => '$OQ');
		$this->EMOJI['F9AD'] = array('TIT' => '¥À¥Ã¥·¥å¡ÊÁö¤ê½Ð¤¹¤µ¤Þ¡Ë', 'EzWeb' => '282', 'SB' => '$OP');
		$this->EMOJI['F9AE'] = array('TIT' => '¡¼¡ÊÄ¹²»µ­¹æ£±¡Ë', 'EzWeb' => '-', 'SB' => '-');
		$this->EMOJI['F9AF'] = array('TIT' => '¡¼¡ÊÄ¹²»µ­¹æ£²¡Ë', 'EzWeb' => '735', 'SB' => '-');
		$this->EMOJI['F9B1'] = array('TIT' => 'i¥¢¥×¥ê', 'EzWeb' => '[£é¥¢¥×¥ê]', 'SB' => '[£é¥¢¥×¥ê]');
		$this->EMOJI['F9B2'] = array('TIT' => 'i¥¢¥×¥ê¡ÊÏÈÉÕ¤­¡Ë', 'EzWeb' => '[£é¥¢¥×¥ê]', 'SB' => '[£é¥¢¥×¥ê]');
		$this->EMOJI['F9B3'] = array('TIT' => 'T¥·¥ã¥Ä¡Ê¥Ü¡¼¥À¡¼¡Ë', 'EzWeb' => '335', 'SB' => '$G&');
		$this->EMOJI['F9B4'] = array('TIT' => '¤¬¤Þ¸ýºâÉÛ', 'EzWeb' => '290', 'SB' => '[ºâÉÛ]');
		$this->EMOJI['F9B5'] = array('TIT' => '²½¾Ñ', 'EzWeb' => '295', 'SB' => '$O<');
		$this->EMOJI['F9B6'] = array('TIT' => '¥¸¡¼¥ó¥º', 'EzWeb' => '805', 'SB' => '[¥¸¡¼¥ó¥º]');
		$this->EMOJI['F9B7'] = array('TIT' => '¥¹¥Î¥Ü', 'EzWeb' => '221', 'SB' => '[¥¹¥Î¥Ü]');
		$this->EMOJI['F9B8'] = array('TIT' => '¥Á¥ã¥Ú¥ë', 'EzWeb' => '48', 'SB' => '$OE');
		$this->EMOJI['F9B9'] = array('TIT' => '¥É¥¢', 'EzWeb' => '[¥É¥¢]', 'SB' => '[¥É¥¢]');
		$this->EMOJI['F9BA'] = array('TIT' => '¥É¥ëÂÞ', 'EzWeb' => '233', 'SB' => '$EO');
		$this->EMOJI['F9BB'] = array('TIT' => '¥Ñ¥½¥³¥ó', 'EzWeb' => '337', 'SB' => '$G,');
		$this->EMOJI['F9BC'] = array('TIT' => '¥é¥Ö¥ì¥¿¡¼', 'EzWeb' => '806', 'SB' => '$E#');
		$this->EMOJI['F9BD'] = array('TIT' => '¥ì¥ó¥Á', 'EzWeb' => '152', 'SB' => '[¥ì¥ó¥Á]');
		$this->EMOJI['F9BE'] = array('TIT' => '±ôÉ®', 'EzWeb' => '149', 'SB' => '$O!');
		$this->EMOJI['F9BF'] = array('TIT' => '²¦´§', 'EzWeb' => '354', 'SB' => '$E.');
		$this->EMOJI['F9C0'] = array('TIT' => '»ØÎØ', 'EzWeb' => '72', 'SB' => '$GT');
		$this->EMOJI['F9C1'] = array('TIT' => 'º½»þ·×', 'EzWeb' => '58', 'SB' => '[º½»þ·×]');
		$this->EMOJI['F9C2'] = array('TIT' => '¼«Å¾¼Ö', 'EzWeb' => '215', 'SB' => '$EV');
		$this->EMOJI['F9C3'] = array('TIT' => 'Åò¤Î¤ß', 'EzWeb' => '423', 'SB' => '$OX');
		$this->EMOJI['F9C4'] = array('TIT' => 'ÏÓ»þ·×', 'EzWeb' => '25', 'SB' => '[ÏÓ»þ·×]');
		$this->EMOJI['F9C5'] = array('TIT' => '¹Í¤¨¤Æ¤ë´é', 'EzWeb' => '441', 'SB' => '$P#');
		$this->EMOJI['F9C6'] = array('TIT' => '¤Û¤Ã¤È¤·¤¿´é', 'EzWeb' => '446', 'SB' => '$P*');
		$this->EMOJI['F9C7'] = array('TIT' => 'Îä¤ä´À', 'EzWeb' => '257', 'SB' => '$P5');
		$this->EMOJI['F9C8'] = array('TIT' => 'Îä¤ä´À2', 'EzWeb' => '351', 'SB' => '$E(');
		$this->EMOJI['F9C9'] = array('TIT' => '¤×¤Ã¤¯¤Ã¤¯¤Ê´é', 'EzWeb' => '779', 'SB' => '$P6');
		$this->EMOJI['F9CA'] = array('TIT' => '¥Ü¥±¡¼¤Ã¤È¤·¤¿´é', 'EzWeb' => '450', 'SB' => '$P.');
		$this->EMOJI['F9CB'] = array('TIT' => 'ÌÜ¤¬¥Ï¡¼¥È', 'EzWeb' => '349', 'SB' => '$E&');
		$this->EMOJI['F9CC'] = array('TIT' => '»Ø¤ÇOK', 'EzWeb' => '287', 'SB' => '$G.');
		$this->EMOJI['F9CD'] = array('TIT' => '¤¢¤Ã¤«¤ó¤Ù¡¼', 'EzWeb' => '264', 'SB' => '$E%');
		$this->EMOJI['F9CE'] = array('TIT' => '¥¦¥£¥ó¥¯', 'EzWeb' => '348', 'SB' => '$P%');
		$this->EMOJI['F9CF'] = array('TIT' => '¤¦¤ì¤·¤¤´é', 'EzWeb' => '446', 'SB' => '$P*');
		$this->EMOJI['F9D0'] = array('TIT' => '¤¬¤Þ¤ó´é', 'EzWeb' => '443', 'SB' => '$P&');
		$this->EMOJI['F9D1'] = array('TIT' => 'Ç­2', 'EzWeb' => '440', 'SB' => '$P"');
		$this->EMOJI['F9D2'] = array('TIT' => 'µã¤­´é', 'EzWeb' => '259', 'SB' => '$P1');
		$this->EMOJI['F9D3'] = array('TIT' => 'ÎÞ', 'EzWeb' => '791', 'SB' => '$P3');
		$this->EMOJI['F9D4'] = array('TIT' => 'NG', 'EzWeb' => '[£Î£Ç]', 'SB' => '[£Î£Ç]');
		$this->EMOJI['F9D5'] = array('TIT' => '¥¯¥ê¥Ã¥×', 'EzWeb' => '143', 'SB' => '[¥¯¥ê¥Ã¥×]');
		$this->EMOJI['F9D6'] = array('TIT' => '¥³¥Ô¡¼¥é¥¤¥È', 'EzWeb' => '81', 'SB' => '$Fn');
		$this->EMOJI['F9D7'] = array('TIT' => '¥È¥ì¡¼¥É¥Þ¡¼¥¯', 'EzWeb' => '54', 'SB' => '$QW');
		$this->EMOJI['F9D8'] = array('TIT' => 'Áö¤ë¿Í', 'EzWeb' => '218', 'SB' => '$E5');
		$this->EMOJI['F9D9'] = array('TIT' => '¥Þ¥ëÈë', 'EzWeb' => '279', 'SB' => '$O5');
		$this->EMOJI['F9DA'] = array('TIT' => '¥ê¥µ¥¤¥¯¥ë', 'EzWeb' => '807', 'SB' => '-');
		$this->EMOJI['F9DB'] = array('TIT' => '¥ì¥¸¥¹¥¿¡¼¥É¥È¥ì¡¼¥É¥Þ¡¼¥¯', 'EzWeb' => '82', 'SB' => '$Fo');
		$this->EMOJI['F9DC'] = array('TIT' => '´í¸±¡¦·Ù¹ð', 'EzWeb' => '1', 'SB' => '$Fr');
		$this->EMOJI['F9DD'] = array('TIT' => '¶Ø»ß', 'EzWeb' => '[¶Ø]', 'SB' => '[¶Ø]');
		$this->EMOJI['F9DE'] = array('TIT' => '¶õ¼¼¡¦¶õÀÊ¡¦¶õ¼Ö', 'EzWeb' => '387', 'SB' => '$FK');
		$this->EMOJI['F9DF'] = array('TIT' => '¹ç³Ê¥Þ¡¼¥¯', 'EzWeb' => '[¹ç]', 'SB' => '[¹ç]');
		$this->EMOJI['F9E0'] = array('TIT' => 'Ëþ¼¼¡¦ËþÀÊ¡¦Ëþ¼Ö', 'EzWeb' => '386', 'SB' => '$FJ');
		$this->EMOJI['F9E1'] = array('TIT' => 'Ìð°õº¸±¦', 'EzWeb' => '808', 'SB' => '¢Î');
		$this->EMOJI['F9E2'] = array('TIT' => 'Ìð°õ¾å²¼', 'EzWeb' => '809', 'SB' => '-');
		$this->EMOJI['F9E3'] = array('TIT' => '³Ø¹»', 'EzWeb' => '377', 'SB' => '$Ew');
		$this->EMOJI['F9E4'] = array('TIT' => 'ÇÈ', 'EzWeb' => '810', 'SB' => '$P^');
		$this->EMOJI['F9E5'] = array('TIT' => 'ÉÙ»Î»³', 'EzWeb' => '342', 'SB' => '$G[');
		$this->EMOJI['F9E6'] = array('TIT' => '¥¯¥í¡¼¥Ð¡¼', 'EzWeb' => '53', 'SB' => '$E0');
		$this->EMOJI['F9E7'] = array('TIT' => '¤µ¤¯¤é¤ó¤Ü', 'EzWeb' => '241', 'SB' => '[¥Á¥§¥ê¡¼]');
		$this->EMOJI['F9E8'] = array('TIT' => '¥Á¥å¡¼¥ê¥Ã¥×', 'EzWeb' => '113', 'SB' => '$O$');
		$this->EMOJI['F9E9'] = array('TIT' => '¥Ð¥Ê¥Ê', 'EzWeb' => '739', 'SB' => '[¥Ð¥Ê¥Ê]');
		$this->EMOJI['F9EA'] = array('TIT' => '¤ê¤ó¤´', 'EzWeb' => '434', 'SB' => '$Oe');
		$this->EMOJI['F9EB'] = array('TIT' => '²ê', 'EzWeb' => '811', 'SB' => '$E0');
		$this->EMOJI['F9EC'] = array('TIT' => '¤â¤ß¤¸', 'EzWeb' => '133', 'SB' => '$E8');
		$this->EMOJI['F9ED'] = array('TIT' => 'ºù', 'EzWeb' => '235', 'SB' => '$GP');
		$this->EMOJI['F9EE'] = array('TIT' => '¤ª¤Ë¤®¤ê', 'EzWeb' => '244', 'SB' => '$Ob');
		$this->EMOJI['F9EF'] = array('TIT' => '¥·¥ç¡¼¥È¥±¡¼¥­', 'EzWeb' => '239', 'SB' => '$Gf');
		$this->EMOJI['F9F0'] = array('TIT' => '¤È¤Ã¤¯¤ê¡Ê¤ª¤Á¤ç¤³ÉÕ¤­¡Ë', 'EzWeb' => '400', 'SB' => '$O+');
		$this->EMOJI['F9F1'] = array('TIT' => '¤É¤ó¤Ö¤ê', 'EzWeb' => '333', 'SB' => '$O`');
		$this->EMOJI['F9F2'] = array('TIT' => '¥Ñ¥ó', 'EzWeb' => '424', 'SB' => '$OY');
		$this->EMOJI['F9F3'] = array('TIT' => '¤«¤¿¤Ä¤à¤ê', 'EzWeb' => '812', 'SB' => '[¥«¥¿¥Ä¥à¥ê]');
		$this->EMOJI['F9F4'] = array('TIT' => '¤Ò¤è¤³', 'EzWeb' => '78', 'SB' => '$QC');
		$this->EMOJI['F9F5'] = array('TIT' => '¥Ú¥ó¥®¥ó', 'EzWeb' => '252', 'SB' => '$Gu');
		$this->EMOJI['F9F6'] = array('TIT' => 'µû', 'EzWeb' => '203', 'SB' => '$G9');
		$this->EMOJI['F9F7'] = array('TIT' => '¤¦¤Þ¤¤¡ª', 'EzWeb' => '454', 'SB' => '$Gv');
		$this->EMOJI['F9F8'] = array('TIT' => '¥¦¥Ã¥·¥Ã¥·', 'EzWeb' => '814', 'SB' => '$P$');
		$this->EMOJI['F9F9'] = array('TIT' => '¥¦¥Þ', 'EzWeb' => '248', 'SB' => '$G:');
		$this->EMOJI['F9FA'] = array('TIT' => '¥Ö¥¿', 'EzWeb' => '254', 'SB' => '$E+');
		$this->EMOJI['F9FB'] = array('TIT' => '¥ï¥¤¥ó¥°¥é¥¹', 'EzWeb' => '12', 'SB' => '$Gd');
		$this->EMOJI['F9FC'] = array('TIT' => '¤²¤Ã¤½¤ê', 'EzWeb' => '350', 'SB' => '$E\'');
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}//end of class
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
