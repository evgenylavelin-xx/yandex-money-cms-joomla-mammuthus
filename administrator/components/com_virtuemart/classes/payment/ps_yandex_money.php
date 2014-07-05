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

	var $PHPSHOP_ADMIN_CFG_YM_PC = "Оплата Яндекс.Деньгами";
	var $PHPSHOP_ADMIN_CFG_YM_PC_EXPLAIN = "Оплата электронной валютой Яндекс.Деньги через систему Яндекс.Деньги.";

	var $PHPSHOP_ADMIN_CFG_YM_AC = "Оплата Банковской картой";
	var $PHPSHOP_ADMIN_CFG_YM_AC_EXPLAIN = "Оплата банковскими картами через систему Яндекс.Деньги.";

	var $PHPSHOP_ADMIN_CFG_YM_GP = "Оплата через Терминалы";
	var $PHPSHOP_ADMIN_CFG_YM_GP_EXPLAIN = "Оплата наличными по коду платежа через систему Яндекс.Деньги. В терминалах и кассах партнеров.";

	var $PHPSHOP_ADMIN_CFG_YM_MC = "Оплата при помощи мобильного телефона";
	var $PHPSHOP_ADMIN_CFG_YM_MC_EXPLAIN = "Оплата при помощи мобильного телефона через систему Яндекс.Деньги.";

	var $PHPSHOP_ADMIN_CFG_YM_NV = "Оплата через WebMoney";
	var $PHPSHOP_ADMIN_CFG_YM_NV_EXPLAIN = "Оплата электронной валютой WenMoney через систему Яндекс.Деньги."

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
			$db->record[$db->row]->payment_extrainfo = '<?
// подключение класс для оплаты системой Яндекс.Деньги

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
	$paymentTypes["AC"] = "Оплатить банковской картой";
}
if ( intval(YM_PC) )
{
	$paymentTypes["PC"] = "Оплатить Яндекс.Деньгами";
}
if ( intval(YM_GP) )
{
	$paymentTypes["GP"] = "Оплатить по коду в терминале";
}
if ( intval(YM_MC) )
{
	$paymentTypes["MC"] = "Оплатить c телефона";
}
if ( intval(YM_NV) )
{
	$paymentTypes["WM"] = "Оплатить при помощи WebMoney";
}
?>

<style type="text/css">
div.payments_methods img {border: none; width: 109px; height: 69px; margin: 0 0 0 0;}
div.payments_methods button {
	cursor:pointer;
	display:inline;
	height:69px;
	width:109px;
	line-height:69px;
	text-align:center;
	background-repeat:no-repeat;
	border: none;
	margin: 0 6px 0 6px;
}
div.payments_methods #btnAC {background-image:url(\'http://<? echo $host; ?>/images/yamoney/ym_card.png\')}
div.payments_methods #btnPC {background-image:url(\'http://<? echo $host; ?>/images/yamoney/ym_yandex_money.png\')}
div.payments_methods #btnGP {background-image:url(\'http://<? echo $host; ?>/images/yamoney/ym_terminal.png\')}
div.payments_methods #btnMC {background-image:url(\'http://<? echo $host; ?>/images/yamoney/ym_mobile.png\')}
div.payments_methods #btnNV {background-image:url(\'http://<? echo $host; ?>/images/yamoney/ym_webmoney.png\')}

h4.span.txt_h4 {font-weight: normal;}
</style>
<div style="width: 100%; text-align: left">
<h4>Номер заказа: <span class="txt_h4"><? echo $orderNumber; ?></span></h4>
<h4>Ваш логин в ...: <span class="txt_h4"><? echo $customerNumber; ?></span></h4>
<h4>Сумма к оплате: <span class="txt_h4"><? echo number_format($out_sum, 2, ",", " ")." ".$currency; ?></span></h4>
</div>
<form method="POST" action="https://<? echo $ym_action_host; ?>/eshop.xml">

<?
$ym = new ps_yandex_money();
echo $ym->get_ym_params_block($host, $out_sum, $customerNumber, $orderNumber, array() );
?>

<div class="payments_methods">
<? foreach( $paymentTypes as $ptKey => $ptName ) { ?>
<button name="paymentType" value="<? echo $ptKey; ?>" type="submit" id="btn<? echo $ptKey; ?>" title="<? echo $ptName; ?>"></button>
<? } ?>
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
				<input type="text" name="YM_SHOPID" class="inputbox" value="<? if(YM_SHOPID != '') echo intval(YM_SHOPID); ?>" />
			</td>
		
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_SHOPID_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_SCID; ?>:</strong></td>
			<td>
				<input type="text" name="YM_SCID" class="inputbox" value="<? if(YM_SCID != '') echo intval(YM_SCID); ?>" />
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_SCID_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_SHOPPASSWORD; ?>:</strong></td>
			<td>
				<input type="text" name="YM_SHOPPASSWORD" class="inputbox" value="<? if(YM_SHOPPASSWORD != '') echo YM_SHOPPASSWORD; ?>" />
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_SHOPPASSWORD_EXPLAIN; ?></td>
		</tr>

		<tr>
			<td colspan=2><h3><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_PAYMENTTYPES; ?></h3></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_PC; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_PC" class="checkbox" value="1" <? if( intval(YM_PC) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_PC_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_AC; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_AC" class="checkbox" value="1" <? if( intval(YM_AC) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_AC_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_GP; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_GP" class="checkbox" value="1" <? if( intval(YM_GP) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_GP_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_MC; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_MC" class="checkbox" value="1" <? if( intval(YM_MC) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_MC_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_NV; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_NV" class="checkbox" value="1" <? if( intval(YM_NV) ) echo "checked=\"checked\" "; ?>/>
			</td>
			<td><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_NV_EXPLAIN; ?></td>
		</tr>
		<tr>
			<td colspan=2><hr /></td>
		</tr>
		<tr>
			<td><strong><?php echo $VM_LANG->PHPSHOP_ADMIN_CFG_YM_DEBUG; ?>:</strong></td>
			<td>
				<input type="checkbox" name="YM_DEBUG" class="checkbox" value="1" <? if( intval(YM_DEBUG) ) echo "checked=\"checked\" "; ?>/>
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
			if(!$d['YM_SHOPID']) 
			{
				$my_config_array['YM_SHOPID'] = '0';
			} else {
				$my_config_array['YM_SHOPID'] = $d['YM_SHOPID'];
			}

			if(!$d['YM_SCID']) 
			{
				$my_config_array['YM_SCID'] = '0';
			} else {
				$my_config_array['YM_SCID'] = $d['YM_SCID'];
			}

			if(!$d['YM_SHOPPASSWORD']) 
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

			if(!isset($d['YM_NV']) || !$d['YM_NV']) 
			{
				$my_config_array['YM_NV'] = '0';
			} else {
				$my_config_array['YM_NV'] ='1';
			}

			if ($d['YM_WAIT_STATUS']) 
			{
				$my_config_array ['YM_WAIT_STATUS'] = $d['YM_WAIT_STATUS'];
			} else {
				$my_config_array ['YM_WAIT_STATUS'] = 'P';
			}

			if ($d['YM_CHECK_STATUS'])
			{
				$my_config_array ['YM_CHECK_STATUS'] = $d['YM_CHECK_STATUS'];
			} else {
				$my_config_array ['YM_CHECK_STATUS'] = 'W';
			}

			if ($d['YM_PAYMENT_STATUS']) 
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

