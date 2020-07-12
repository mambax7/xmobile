<?php
define('_MI_XMOBILE_NAME','Xmobile');
define('_MI_XMOBILE_DESC','mobile support module for XOOPS');

define('_MI_XMOBILE_ACCESS_LEVEL','Access Permission');
define('_MI_XMOBILE_ACCESS_LEVEL_DESC','Select groups that are allowed to access mobile site.');
define('_MI_XMOBILE_ALLOW_GUEST','Only Guests');
define('_MI_XMOBILE_ALLOW_USER','Only registered users');
define('_MI_XMOBILE_ALLOW_ALL','All users');
define('_MI_XMOBILE_ACCESS_TERM','Terminal Permission');
define('_MI_XMOBILE_ACCESS_TERM_DESC','Setting of permission that access to Xmobile. Only the mobile terminal(identify by the USER_AGENT) is recommended. Please grant "All"  permission when access it to confirm the operation.');
define('_MI_XMOBILE_ALLOW_MOBILE_H','mobile(Identify by HOST)');
define('_MI_XMOBILE_ALLOW_MOBILE_A','mobile(Identify by USER_AGENT)');
define('_MI_XMOBILE_ALLOW_ALL_TERM','All');
define('_MI_XMOBILE_LOGIN_TERM','Login and Register Permission');
define('_MI_XMOBILE_LOGIN_TERM_DESC','Setting of permission acquisition of login and registration, and individual identification number. Only the mobile terminal(identify by the Host name) is recommended. Please grant "All" permission when access it to confirm the operation.');
define('_MI_XMOBILE_ALLOW_REGIST','Allow new user registration?');
define('_MI_XMOBILE_ALLOW_REGIST_DESC','Select "Yes" to accept new user registration from mobile.');
define('_MI_XMOBILE_CHK_IPADDRESS','When the session is restored, the value of IP address is collated.');
define('_MI_XMOBILE_CHK_IPADDRESS_DESC','Make it to a good getting when trouble that collates the value of the first 9 digits of IP address to which the state of log in cannot be maintained in docomo etc.');
define('_MI_XMOBILE_USE_EZLOGIN','Use EASY LOGIN?');
define('_MI_XMOBILE_USE_EZLOGIN_DESC','Easy login by using Individual Identification Numer.');
define('_MI_XMOBILE_EZLOGIN_LIMIT','Validition term of EASY LOGIN Authentication');
define('_MI_XMOBILE_EZLOGIN_LIMIT_DESC','It is a period when the individual identification number for the EASY LOGIN authentication is preserved.');
define('_MI_XMOBILE_DEBUG_MODE','Debug mode?');
define('_MI_XMOBILE_DEBUG_MODE_DESC','Several debug options. A running website should be turned off.');
define('_MI_XMOBILE_LOGO','Site logo image');
define('_MI_XMOBILE_LOGO_DESC','Logo image URL for mobile. It should be clear when no images.');
define('_MI_XMOBILE_SITE_NAME','Site name');
define('_MI_XMOBILE_SITE_NAME_DESC','Mobile Site Name');
define('_MI_XMOBILE_MAX_DATA_SIZE','Max size of 1 page(bytes)');
define('_MI_XMOBILE_MAX_DATA_SIZE_DESC','Max size of the text data that can be displayed per 1 page(not contain the images).');
define('_MI_XMOBILE_SESSION_LIMIT','Session Expiration');
define('_MI_XMOBILE_SESSION_LIMIT_DESC','Max duration of session idle time in minutes.');
define('_MI_XMOBILE_USE_ACCESSKEY','Use Easy Aaccess key?');
define('_MI_XMOBILE_USE_ACCESSKEY_DESC','Shortcut key by numeric, alphabets.');
define('_MI_XMOBILE_MAX_TITLE_ROW','Max length of Comments');
define('_MI_XMOBILE_MAX_TITLE_ROW_DESC','Max display the article lists per 1 page.');
define('_MI_XMOBILE_MAX_TITLE_L','Max length of Title');
define('_MI_XMOBILE_MAX_TITLE_L_DESC','Max characters of the title.');

define('_MI_XMOBILE_TITLE_SORT','Comments display order');
define('_MI_XMOBILE_TITLE_SORT_DESC','Default comments display order, ascending or descending');
define('_MI_XMOBILE_SORT_ASC','Ascending');
define('_MI_XMOBILE_SORT_DESC','Descending');

define('_MI_XMOBILE_CAT_TYPE','How to display categories?');
define('_MI_XMOBILE_CAT_TYPE_DESC','Display method categories 2 ways.');
define('_MI_XMOBILE_TYPE_LIST','Listing');
define('_MI_XMOBILE_TYPE_SELECT','Select box');
define('_MI_XMOBILE_SHOW_COUNT','Show the count of items?');
define('_MI_XMOBILE_SHOW_COUNT_DESC','appear counts near the title in category list');
define('_MI_XMOBILE_SHOW_RECENT','Recent titles');
define('_MI_XMOBILE_SHOW_RECENT_DESC','Recent title lists, display or not');
define('_MI_XMOBILE_RECENTTITLE_R','Max recent titles');
define('_MI_XMOBILE_RECENTTITLE_R_DESC','Max recent titles displayed in 1 page');
define('_MI_XMOBILE_SEARCH_R','Max search results');
define('_MI_XMOBILE_SEARCH_R_DESC','Max search results displayed in 1 page');
define('_MI_XMOBILE_COMMENT_R','Max comments');
define('_MI_XMOBILE_COMMENT_R_DESC','Max comments displayed in 1 page');
define('_MI_XMOBILE_PM_R','Max Private Messages');
define('_MI_XMOBILE_PM_R_DESC','Max messages displayed  per 1page.');
define('_MI_XMOBILE_TAREA_ROWS','Height of Textarea');
define('_MI_XMOBILE_TAREA_ROWS_DESC','Rows of textarea column');
define('_MI_XMOBILE_TAREA_COLS','Width of Textarea');
define('_MI_XMOBILE_TAREA_COLS_DESC','Cols of textarea column');
define('_MI_XMOBILE_REPLACE_LINK','Converts links?');
define('_MI_XMOBILE_REPLACE_LINK_DESC','Xmobile converts links in site as much as possible.');
define('_MI_XMOBILE_USE_THUMBNAIL','Show the thumbnails?');
define('_MI_XMOBILE_USE_THUMBNAIL_DESC','Show the thumbnails for mobile');
define('_MI_XMOBILE_THUMBNAIL','Width of the thumbnails');
define('_MI_XMOBILE_THUMBNAIL_DESC','Width size of the thumbnails(pixel)');
define('_MI_XMOBILE_THUMBPASS','Path to thumbnails');
define('_MI_XMOBILE_THUMBPASS_DESC',"Directory that exists thumbnails at the XOOPS installed(first '/' is necessary and last '/' is not).<br />set write permission to this directory.");
define('_MI_XMOBILE_MODULES','Available modules');
define('_MI_XMOBILE_MODULES_DESC','accessable modules from mobile');

//NE+
define('_MI_XMOBILE_BLOCK_QR','QR code Block');
define('_MI_XMOBILE_BLOCK_QR_DESC','This block displays QR code image for mobile');
define('_MI_XMOBILE_BLOCK_REDIRECT','Redirect block');
define('_MI_XMOBILE_BLOCK_REDIRECT_DESC','This block redirects access by mobile to Xmobile');

?>
