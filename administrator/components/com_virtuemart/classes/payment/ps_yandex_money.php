<?php
// Модуль ps_yandex_money для оплаты через платежную систему Яндекс.Деньги
// Используется с схема с передачей номера заказа в поле orderNumber
// Компонент ps_yandex_money.php, реализующий настройку подключения по протоколу Яндекс.Деньги 3.0(ЕПР)
// 
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );

class yandex_money_language 
{
	var $PHPSHOP_ADMIN_CFG_YM_CURRENCY = "руб.";

	var $PHPSHOP_ADMIN_CFG_YM_SETTINGS = "Настройки подключения";
	var $PHPSHOP_ADMIN_CFG_YM_PAYMENTTYPES = "Методы оплаты";

	var $PHPSHOP_ADMIN_CFG_YM_DEBUG = "Демо-режим";
	var $PHPSHOP_ADMIN_CFG_YM_DEBUG_EXPLAIN = "Демо-режим. Режим тестирования подключения к платежной системе Яндекс.Деньги.";

	var $PHPSHOP_ADMIN_CFG_YM_SHOPID = "Идентификатор магазина";
	var $PHPSHOP_ADMIN_CFG_YM_SHOPID_EXPLAIN = "shopID системе <a href=https://start.money.yandex.ru/><u>Яндекс.Деньги</u></a>";

	var $PHPSHOP_ADMIN_CFG_YM_SCID = "Номер витрины";
	var $PHPSHOP_ADMIN_CFG_YM_SCID_EXPLAIN = "SCID системе <a href=https://start.money.yandex.ru/><u>Яндекс.Деньги</u></a>, выдается после заполнения технической анкеты<br /><b>Важно!</b> Номер витрины в демо- и в реальном режиме НЕ СОВПАДАЮТ!";

	var $PHPSHOP_ADMIN_CFG_YM_SHOPPASSWORD = "Секретный пароль";
	var $PHPSHOP_ADMIN_CFG_YM_SHOPPASSWORD_EXPLAIN = "Заполняется в технической анкете при подключении к системе <a href=https://start.money.yandex.ru/><u>Яндекс.Деньги</u></a>";

	var $PHPSHOP_ADMIN_CFG_YM_PC = "Кошелек Яндекс.Деньги";
	var $PHPSHOP_ADMIN_CFG_YM_PC_EXPLAIN = "Оплата из кошелька в Яндекс.Деньгах.";

	var $PHPSHOP_ADMIN_CFG_YM_AC = "Банковская карта";
	var $PHPSHOP_ADMIN_CFG_YM_AC_EXPLAIN = "Оплата с произвольной банковской карты.";

	var $PHPSHOP_ADMIN_CFG_YM_GP = "Наличными через кассы и терминалы";
	var $PHPSHOP_ADMIN_CFG_YM_GP_EXPLAIN = "Оплата наличными через кассы и терминалы.";

	var $PHPSHOP_ADMIN_CFG_YM_MC = "Счет мобильного телефона";
	var $PHPSHOP_ADMIN_CFG_YM_MC_EXPLAIN = "Платеж со счета мобильного телефона.";

	var $PHPSHOP_ADMIN_CFG_YM_WM = "Кошелек WebMoney";
	var $PHPSHOP_ADMIN_CFG_YM_WM_EXPLAIN = "Оплата из кошелька в системе WebMoney.";
	
	var $PHPSHOP_ADMIN_CFG_YM_SB = "Сбербанк: оплата по SMS или Сбербанк Онлайн";
	var $PHPSHOP_ADMIN_CFG_YM_SB_EXPLAIN = "Оплата через Сбербанк: оплата по SMS или Сбербанк Онлайн.";

	var $PHPSHOP_ADMIN_CFG_YM_AB = "Альфа-Клик";
	var $PHPSHOP_ADMIN_CFG_YM_AB_EXPLAIN = "Оплата через Альфа-Клик.";
	
	var $PHPSHOP_ADMIN_CFG_YM_MA = "MasterPass";
	var $PHPSHOP_ADMIN_CFG_YM_MA_EXPLAIN = "Оплата через MasterPass.";
	
	var $PHPSHOP_ADMIN_CFG_YM_PB = "Интернет-банк Промсвязьбанка";
	var $PHPSHOP_ADMIN_CFG_YM_PB_EXPLAIN = "Оплата через интернет-банк Промсвязьбанка.";
		

	var $PHPSHOP_YM_ORDER_STATUS_WAIT_SET = "Пользователь сделал заказ, но ещё не оплатил. Заказу присвоен статус &laquo;в обработке&raquo;.";
	var $PHPSHOP_ADMIN_CFG_YM_WAIT_STATUS = "Статус готов к оплате";
	var $PHPSHOP_ADMIN_CFG_YM_WAIT_STATUS_EXPLAIN = "Статус, при котором заказ <i>разрешено</i> оплатить.";

	var $PHPSHOP_YM_ORDER_STATUS_CHECK_SET = "Пользователь начал оплату заказа, но ещё не оплатил. Заказу присвоен статус &laquo;пользоваль подтвердил чек&raquo;.";
	var $PHPSHOP_ADMIN_CFG_YM_CHECK_STATUS = "Статус пользователь подтвердил чек";
	var $PHPSHOP_ADMIN_CFG_YM_CHECK_STATUS_EXPLAIN = "Статус, при котором заказ <i>оплачивается пользователем</i>.";

	var $PHPSHOP_YM_ORDER_STATUS_PAYMENT_SET = "Пользователь успешно оплатил счёт. Заказу присвоен статус &laquo;оплачен&raquo;.";
	var $PHPSHOP_ADMIN_CFG_YM_STATUS_PAYMENT = "Статус успешной оплаты";
	var $PHPSHOP_ADMIN_CFG_YM_STATUS_PAYMENT_EXPLAIN = "Выбранный статус будет выставлен заказу, который был успешно оплачен.";
}
 
class ps_yandex_money 
{
	var $classname = "ps_yandex_money";
	var $payment_code = "YMP";

	/**
	* Conctructor, that merge our language varibles to VM_LANG
	*/
	function ps_yandex_money() 
	{
		global $VM_LANG;
		$status = $VM_LANG->merge('yandex_money_language');
	}

	/**
	* Отображение параметров конфигурации этого модуля оплаты
	* @returns boolean False when the Payment method has no configration
	*/
	function show_configuration() 
	{
		global $db, $VM_LANG;
		if(htmlspecialchars( $db->sf("payment_extrainfo")) == '' ) 
		{
			$db->record[$db->row]->payment_extrainfo = '<?php
// Класс для оплаты через сервис Яндекс.Касса
// Модуль версии 1.1.0
// Лицензионный договор.
// Любое использование Вами программы означает полное и безоговорочное принятие Вами условий лицензионного договора, размещенного по адресу https://money.yandex.ru/doc.xml?id=527132 (далее – «Лицензионный договор»). Если Вы не принимаете условия Лицензионного договора в полном объёме, Вы не имеете права использовать программу в каких-либо целях.

require_once(CLASSPATH."payment/ps_yandex_money.php");
$host = getenv("HTTP_HOST");
// сервер Яндекс.Денег для отправки данных платежной формы
$ym_action_host = ( intval(YM_DEBUG) ? "demomoney.yandex.ru" : "money.yandex.ru" );

// номер заказа
// number of order
$orderNumber = $db->f("order_id");

// сумма заказа
// sum of order
$out_sum = $db->f("order_total");

// валюта
// currency
$currency = $VM_LANG->PHPSHOP_ADMIN_CFG_YM_CURRENCY;

// плательщик
// customerNumber
$user =& JFactory::getUser();
$customerNumber = $user->username;

// способы оплаты
// payment types
$paymentTypes = array();
if ( intval(YM_AC) )
{
	$paymentTypes["AC"] = "Оплатить с произвольной банковской карты";
}
if ( intval(YM_PC) )
{
	$paymentTypes["PC"] = "Оплатить из кошелька в Яндекс.Деньгах";
}
if ( intval(YM_GP) )
{
	$paymentTypes["GP"] = "Оплатить наличными через кассы и терминалы";
}
if ( intval(YM_MC) )
{
	$paymentTypes["MC"] = "Оплатить со счета мобильного телефона";
}
if ( intval(YM_WM) )
{
	$paymentTypes["WM"] = "Оплатить из кошелька в системе WebMoney";
}
if ( intval(YM_AB) )
{
	$paymentTypes["AB"] = "Оплатить через Альфа-Клик";
}
if ( intval(YM_SB) )
{
	$paymentTypes["SB"] = "Оплатить через Сбербанк: оплата по SMS или Сбербанк Онлайн";
}
if ( intval(YM_PB) )
{
	$paymentTypes["PB"] = "Оплатить через интернет-банк Промсвязьбанка";
}
if ( intval(YM_MA) )
{
	$paymentTypes["MA"] = "Оплатить через MasterPass.";
}
?>

<style type="text/css">
div.payments_methods img {border: none; width: 32px; height: 32px; margin: 0 0 0 0;}
div.payments_methods button {
	cursor:pointer;
	display:inline;
	height:32px;
	width:32px;
	line-height:32px;
	text-align:center;
	background-repeat:no-repeat;
	border: none;
	margin: 0 6px 0 6px;
}
div.payments_methods #btnAC {background-image:url(\'http://<?php echo $host; ?>/images/yamoney/ac.png\')}
div.payments_methods #btnPC {background-image:url(\'http://<?php echo $host; ?>/images/yamoney/pc.png\')}
div.payments_methods #btnGP {background-image:url(\'http://<?php echo $host; ?>/images/yamoney/gp.png\')}
div.payments_methods #btnMC {background-image:url(\'http://<?php echo $host; ?>/images/yamoney/mc.png\')}
div.payments_methods #btnWM {background-image:url(\'http://<?php echo $host; ?>/images/yamoney/wm.png\')}
div.payments_methods #btnAB {background-image:url(\'http://<?php echo $host; ?>/images/yamoney/ab.png\')}
div.payments_methods #btnSB {background-image:url(\'http://<?php echo $host; ?>/images/yamoney/sb.png\')}
div.payments_methods #btnPB {background-image:url(\'http://<?php echo $host; ?>/images/yamoney/pb.png\')}
div.payments_methods #btnMA {background-image:url(\'http://<?php echo $host; ?>/images/yamoney/ma.png\')}

h4.span.txt_h4 {font-weight: normal;}
</style>
<div style="width: 100%; text-align: left">
<h4>Номер заказа: <span class="txt_h4"><?php echo $orderNumber; ?></span></h4>
<h4>Ваш логин в ...: <span class="txt_h4"><?php echo $customerNumber; ?></span></h4>
<h4>Сумма к оплате: <span class="txt_h4"><?php echo number_format($out_sum, 2, ",", " ")." ".$currency; ?></span></h4>
</div>
<form method="POST" action="https://<?php echo $ym_action_host; ?>/eshop.xml">

<?php
$ym = new ps_yandex_money();
echo $ym->get_ym_params_block($host, $out_sum, $customerNumber, $orderNumber, array() );
?>

<div class="payments_methods">
<?php foreach( $paymentTypes as $ptKey => $ptName ) { ?>
<button name="paymentType" value="<?php echo $ptKey; ?>" type="submit" id="btn<?php echo $ptKey; ?>" title="<?php echo $ptName; ?>"></button>
<?php } ?>
</div>
</form>
';
		}

		/** Загрузка файла конфигурации ***/
		if($this->has_configuration())
			include_once(CLASSPATH ."payment/".$this->classname.".cfg.php");
		else
			return false;
?>
	<table style="text-align:left">

		<tr>
			<td colspan=2><h3><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_SETTINGS; ?></h3></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_SHOPID; ?>:</strong></td>
			<td>
				<input type="text" name="YM_SHOPID" class="inputbox" value="<?php if(YM_SHOPID != '') echo intval(YM_SHOPID); ?>" />
			</td>
		
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_SHOPID_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_SCID; ?>:</strong></td>
			<td>
				<input type="text" name="YM_SCID" class="inputbox" value="<?php if(YM_SCID != '') echo intval(YM_SCID); ?>" />
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_SCID_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_SHOPPASSWORD; ?>:</strong></td>
			<td>
				<input type="text" name="YM_SHOPPASSWORD" class="inputbox" value="<?php if(YM_SHOPPASSWORD != '') echo YM_SHOPPASSWORD; ?>" />
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_SHOPPASSWORD_EXPLAIN; ?></td>
		</tr>

		<tr>
			<td colspan=2><h3><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_PAYMENTTYPES; ?></h3></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_PC; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_PC" class="checkbox" value="1" <?php if( intval(YM_PC) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_PC_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_AC; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_AC" class="checkbox" value="1" <?php if( intval(YM_AC) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_AC_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_GP; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_GP" class="checkbox" value="1" <?php if( intval(YM_GP) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_GP_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_MC; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_MC" class="checkbox" value="1" <?php if( intval(YM_MC) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_MC_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_WM; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_WM" class="checkbox" value="1" <?php if( intval(YM_WM) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_WM_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_AB; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_AB" class="checkbox" value="1" <?php if( intval(YM_AB) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_AB_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_SB; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_SB" class="checkbox" value="1" <?php if( intval(YM_SB) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_SB_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_PB; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_PB" class="checkbox" value="1" <?php if( intval(YM_PB) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_PB_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_MA; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_MA" class="checkbox" value="1" <?php if( intval(YM_MA) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_MA_EXPLAIN; ?></td>
		</tr>
		
		<tr>
			<td colspan=2><hr /></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_DEBUG; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_DEBUG" class="checkbox" value="1" <?php if( intval(YM_DEBUG) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_DEBUG_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td colspan=2><hr /></td>
		</tr>

		<?php
		$q = "SELECT order_status_name, order_status_code FROM #__{vm}_order_status ORDER BY list_order";
		$dbs = new ps_DB;
		$dbs->query($q);
		$order_status_code = Array();
		$order_status_name = Array();

		while ($dbs->next_record()) 
		{
			$order_status_code[] = $dbs->f("order_status_code");
			$order_status_name[] =  $dbs->f("order_status_name");
		}
		?>


		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_WAIT_STATUS; ?></strong></td>
			<td>
			<select name="YM_WAIT_STATUS" class="inputbox" >
			<?php
				for ($i=0; $i < sizeof($order_status_code); $i++) 
				{
					if (YM_WAIT_STATUS == $order_status_code[$i])
						$selected = 'selected="selected"';
					else
						$selected = '';

					echo '<option '.$selected.' value="'.$order_status_code[$i].'">'.$order_status_name[$i].'</option>';
				}
			?>
			</select>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_WAIT_STATUS_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_CHECK_STATUS; ?></strong></td>
			<td>
			<select name="YM_CHECK_STATUS" class="inputbox" >
			<?php
				for ($i=0; $i < sizeof($order_status_code); $i++) 
				{
					if (YM_CHECK_STATUS == $order_status_code[$i])
						$selected = 'selected="selected"';
					else 
						$selected = '';

				echo '<option '.$selected.' value="'.$order_status_code[$i].'">'.$order_status_name[$i].'</option>';
				}
			?>
			</select>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_CHECK_STATUS_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_STATUS_PAYMENT; ?></strong></td>
			<td>
			<select name="YM_PAYMENT_STATUS" class="inputbox" >
			<?php
				for ($i=0; $i < sizeof($order_status_code); $i++) 
				{
					if (YM_PAYMENT_STATUS == $order_status_code[$i]) 
						$selected = 'selected="selected"';
					else
						$selected = '';
					
					echo '<option '.$selected.' value="'.$order_status_code[$i].'">'.$order_status_name[$i].'</option>';
				}
			?>
			</select>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_STATUS_PAYMENT_EXPLAIN; ?>
			</td>
		</tr>

	</table>
	<?php
	}

  /**
  * Returns the "has_configuration" status of the module
  * @param void
  * @returns boolean True when the configuration is, false when not
  */
	function has_configuration() 
	{
		if(file_exists( CLASSPATH."payment/".$this->classname.".cfg.php" )) 
		{
			include_once( CLASSPATH."payment/".$this->classname.".cfg.php" );
		}
		else 
		{
			if(!$this->write_configuration($d))
			{
				return false;
			}
		}
		return true;
	}

	/**
	* Returns the "is_writeable" status of the configuration file
	* @param void
	* @returns boolean True when the configuration file is writeable, false when not
	*/
	function configfile_writeable() 
	{
		return is_writeable( CLASSPATH."payment/".$this->classname.".cfg.php" );
	}

	/**
	* Returns the "is_readable" status of the configuration file
	* @param void
	* @returns boolean True when the configuration file is readable, false when not
	*/
	function configfile_readable() 
	{
		return is_readable( CLASSPATH."payment/".$this->classname.".cfg.php" );
	}

	/**
	* Writes the configuration file for this payment method
	* @param array An array of objects
	* @returns boolean True when writing was successful
	*/
	function write_configuration( &$d ) 
	{
		global $database, $mosConfig_live_site, $mosConfig_absolute_path, $mosConfig_lang, $mosConfig_locale;


		/** Check for empty values **/
		if (is_array($d)) 
		{
			if(!isset($d['YM_SHOPID']))
			{
				$my_config_array['YM_SHOPID'] = '0';
			} else {
				$my_config_array['YM_SHOPID'] = $d['YM_SHOPID'];
			}

			if(!isset($d['YM_SCID']))
			{
				$my_config_array['YM_SCID'] = '0';
			} else {
				$my_config_array['YM_SCID'] = $d['YM_SCID'];
			}

			if(!isset($d['YM_SHOPPASSWORD']))
			{
				$my_config_array['YM_SHOPPASSWORD'] = '';
			} else {
				$my_config_array['YM_SHOPPASSWORD'] = $d['YM_SHOPPASSWORD'];
			}

			if(!isset($d['YM_DEBUG']) || !$d['YM_DEBUG']) 
			{
				$my_config_array['YM_DEBUG'] = '0';
			} else {
				$my_config_array['YM_DEBUG'] ='1';
			}

			if(!isset($d['YM_PC']) || !$d['YM_PC']) 
			{
				$my_config_array['YM_PC'] = '0';
			} else {
				$my_config_array['YM_PC'] ='1';
			}

			if(!isset($d['YM_AC']) || !$d['YM_AC']) 
			{
				$my_config_array['YM_AC'] = '0';
			} else {
				$my_config_array['YM_AC'] ='1';
			}

			if(!isset($d['YM_GP']) || !$d['YM_GP']) 
			{
				$my_config_array['YM_GP'] = '0';
			} else {
				$my_config_array['YM_GP'] ='1';
			}

			if(!isset($d['YM_MC']) || !$d['YM_MC']) 
			{
				$my_config_array['YM_MC'] = '0';
			} else {
				$my_config_array['YM_MC'] ='1';
			}

			if(!isset($d['YM_WM']) || !$d['YM_WM']) 
			{
				$my_config_array['YM_WM'] = '0';
			} else {
				$my_config_array['YM_WM'] ='1';
			}

			if(!isset($d['YM_AB']) || !$d['YM_AB']) 
			{
				$my_config_array['YM_AB'] = '0';
			} else {
				$my_config_array['YM_AB'] ='1';
			}
			if(!isset($d['YM_SB']) || !$d['YM_SB']) 
			{
				$my_config_array['YM_SB'] = '0';
			} else {
				$my_config_array['YM_SB'] ='1';
			}
			if(!isset($d['YM_MA']) || !$d['YM_MA']) 
			{
				$my_config_array['YM_MA'] = '0';
			} else {
				$my_config_array['YM_MA'] ='1';
			}
			if(!isset($d['YM_PB']) || !$d['YM_PB']) 
			{
				$my_config_array['YM_PB'] = '0';
			} else {
				$my_config_array['YM_PB'] ='1';
			}
			
			if (isset($d['YM_WAIT_STATUS']))
			{
				$my_config_array ['YM_WAIT_STATUS'] = $d['YM_WAIT_STATUS'];
			} else {
				$my_config_array ['YM_WAIT_STATUS'] = 'P';
			}

			if (isset($d['YM_CHECK_STATUS']))
			{
				$my_config_array ['YM_CHECK_STATUS'] = $d['YM_CHECK_STATUS'];
			} else {
				$my_config_array ['YM_CHECK_STATUS'] = 'W';
			}

			if (isset($d['YM_PAYMENT_STATUS'])) 
			{
				$my_config_array ['YM_PAYMENT_STATUS'] = $d['YM_PAYMENT_STATUS'];
			} else {
				$my_config_array ['YM_PAYMENT_STATUS'] = 'O';
			}
		}

		$config = "<?php if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die('Direct Access to this location is not allowed.');\n\n";

		while (list($key, $value) = each($my_config_array)) 
		{
			if(substr($key, 0, 5) == 'text_') 
			{
				$config .= $value."\n";
			} else {
				$key = strtoupper($key);
				$config .= "define ('".$key."', '".$value."');\n";
			}
		}
		$config .= "?>";

		if ($fp = fopen( CLASSPATH."payment/".$this->classname.".cfg.php", "w")) 
		{
			fputs($fp, stripslashes($config));
			fclose($fp);
			new yamoney_statistics();
			return true;
		}
		else return false;
	}


	//Форма для оплаты заказа через систему Яндекс.Деньги
	//Выводится после оформления заказа. Через payment_extrainfo
	function get_ym_params_block($host, $out_sum, $customerNumber, $orderNumber, $hidden_param) 
	{
		global $db;
		
		if(file_exists( CLASSPATH."payment/".$this->classname.".cfg.php" )) 
		{
			include_once( CLASSPATH."payment/".$this->classname.".cfg.php" );
		}

		$ym_shopID=YM_SHOPID; //Ваше "shopID" (идентификатор магазина) в системе Яндекс.Деньги
		$ym_SCID=YM_SCID; //Ваш "SCID" (идентификатор витрины) в системе Яндекс.Деньги
		$ym_shopPassword=YM_SHOPPASSWORD; //Ваш "shopPassword" (секретный пароль) в системе Яндекс.Деньги

		// HTML-страница с формой
		$htmlBlock =<<<YMEOF
<input type="hidden"name="scid" value="$ym_SCID">
<input type="hidden" name="ShopID" value="$ym_shopID">
<input type="hidden" name="Sum" value="$out_sum">
<input type="hidden" name="CustomerNumber" value="$customerNumber">
YMEOF;

		if ( $orderNumber != "" )
		{
			$htmlBlock .= '<input type="hidden" name="orderNumber" value="'.$orderNumber.'">'."\n";
			$htmlBlock .= '<input type="hidden" name="shopSuccessURL" value="http://'.$host.'/ru/cart?page=account.order_details&order_id='.$orderNumber.'">';
			$htmlBlock .= '<input type="hidden" name="shopFailURL" value="http://'.$host.'/ru/cart?page=account.order_details&order_id='.$orderNumber.'">'."\n";
		}

		if ( $hidden_param && is_array($hidden_param) )
		{
			foreach($hidden_param as $k=>$v)
			{
				if ( is_scalar($k) && is_scalar($v) )
				{
					if ( strcasecmp($k, "order_id") !== 0 )
					{
						$htmlBlock .=  '<input type="hidden" name="'.$k.'" value="'.$v.'">'."\n";
					} else {
						$htmlBlock .= '<input type="hidden" name="shopSuccessURL" value="http://'.$host.'/ru/cart?page=account.order_details&order_id='.intval($v).'">';
						$htmlBlock .= '<input type="hidden" name="shopFailURL" value="http://'.$host.'/ru/cart?page=account.order_details&order_id='.intval($v).'">'."\n";
					}
				}
			}
		}

		return $htmlBlock;
	}


	//Функция округления для md5
	function to_float($sum) 
	{
		if (strpos($sum, ".")) 
		{
			$sum=round($sum,2);
		} else {
			$sum=$sum.".0";
		}
		return $sum;
	}

	/**************************************************************************
	** name: process_payment()
	** returns:
	***************************************************************************/
	function process_payment($order_number, $order_total, &$d) 
	{
		return true;
	}
}
class yamoney_statistics {
	public function __construct(){
		$this->send();
	}

	private function send()
	{
		$headers = array();
		$headers[] = 'Content-Type: application/x-www-form-urlencoded';
		$user = JFactory::getUser();
		$array = array(
			'url' => JURI::base(),
			'cms' => 'joomla',
			'version' => JVERSION,
			'ver_mod' => '1.1.0',
			'yacms' => false,
			'email' => $user->email,
			'shopid' => YM_SHOPID,
			'settings' => array(
				'kassa' => true
			)
		);

		$key_crypt = gethostbyname($_SERVER['HTTP_HOST']);
		$array_crypt = $this->crypt_encrypt($array, $key_crypt);

		$url = 'https://statcms.yamoney.ru/';
		$curlOpt = array(
			CURLOPT_HEADER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLINFO_HEADER_OUT => true,
			CURLOPT_POST => true,
		);

		$curlOpt[CURLOPT_HTTPHEADER] = $headers;
		$curlOpt[CURLOPT_POSTFIELDS] = http_build_query(array('data' => $array_crypt));

		$curl = curl_init($url);
		curl_setopt_array($curl, $curlOpt);
		$rbody = curl_exec($curl);
		$errno = curl_errno($curl);
		$error = curl_error($curl);
		$rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
	}
	
	private function crypt_encrypt($data, $key)
	{
		$key = hash('sha256', $key, true);
		$data = serialize($data);
		$init_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
		$init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);
		$str = $this->randomString(strlen($key)).$init_vect.mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_MODE_CBC, $init_vect);
		return base64_encode($str);
	}

	private function randomString($len)
	{
		$str = '';
		$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$pool_len = strlen($pool);
		for ($i = 0; $i < $len; $i++) {
			$str .= substr($pool, mt_rand(0, $pool_len - 1), 1);
		}
		return $str;
	}
}