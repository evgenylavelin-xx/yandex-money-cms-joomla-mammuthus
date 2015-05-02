<?php if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die('Direct Access to this location is not allowed.');
function randString( $length ) {
    $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";  
    $size = strlen($chars);
    for( $i = 0; $i < $length; $i++ ) {
        $str .= $chars[ rand( 0, $size - 1 ) ];
    }
    return $str;
}

define ('YM_SHOPID', '');
define ('YM_SCID', '');
define ('YM_SHOPPASSWORD', randString(10));
define ('YM_WAIT_STATUS', 'P');
define ('YM_CHECK_STATUS', 'W');
define ('YM_PAYMENT_STATUS', 'O');
define ('YM_DEBUG', '1');
define ('YM_PC', '1');
define ('YM_AC', '1');
define ('YM_GP', '1');
define ('YM_MC', '1');
define ('YM_WM', '1');
define ('YM_AB', '1');
define ('YM_SB', '1');
define ('YM_PB', '1');
define ('YM_MA', '1');
?>