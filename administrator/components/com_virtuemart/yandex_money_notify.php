<?php
// Модуль ps_yandex_money для оплаты через платежную систему Яндекс.Деньги
// Используется с схема с передачей номера заказа в поле orderNumber
// Компонент yandex_money_notify.php, реализующий ответы по протоколу Яндекс.Деньги 3.0(ЕПР)
// 
// & JFactory::getApplication( 'site' ) необходимая для инициализации базы дает warning
// потому отключим warning'и, для корректного XML ответа
error_reporting(0);

$messages = Array();
function debug_msg( $msg )
{
	global $messages;
}

//Функция выдает ответ для платежной системы Яндекс.Деньги в формате XML
function answer($action,$shopID,$invoiceId,$code) 
{
	switch ($action)
	{
		case 'checkOrder':
			return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".'<checkOrderResponse performedDatetime ="'.date(DATE_ATOM).'" code="'.(int)$code.'" invoiceId="'.$invoiceId.'" shopId="'.(int)$shopID.'"/>';
		case 'paymentAviso':
			return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".'<paymentAvisoResponse performedDatetime ="'.date(DATE_ATOM).'" code="'.(int)$code.'" invoiceId="'.$invoiceId.'" shopId="'.(int)$shopID.'"/>';
		default:
			return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".'<'.$action.'Response performedDatetime ="'.date(DATE_ATOM).'" code="'.(int)$code.'" invoiceId="'.$invoiceId.'" shopId="'.(int)$shopID.'"/>';
	}
}

if(isset($_REQUEST['action']))
{	
	global $mosConfig_absolute_path, $mosConfig_live_site, $mosConfig_lang, $database,
	$mosConfig_mailfrom, $mosConfig_fromname;

	/*** access Joomla's configuration file ***/
	$my_path = dirname(__FILE__);

	if( file_exists($my_path."/../../../configuration.php")) 
	{
		$absolute_path = dirname( $my_path."/../../../configuration.php" );
		require_once($my_path."/../../../configuration.php");
	} elseif( file_exists($my_path."/../../configuration.php")) {
		$absolute_path = dirname( $my_path."/../../configuration.php" );
		require_once($my_path."/../../configuration.php");
	} elseif( file_exists($my_path."/configuration.php")) {
		$absolute_path = dirname( $my_path."/configuration.php" );
		require_once( $my_path."/configuration.php" );
	} else {
		die( "Joomla Configuration File not found!" );
	}

	// способ инициализации 
	$absolute_path = realpath( $absolute_path );
	if( class_exists( 'jconfig' ) ) 
	{
		define( '_JEXEC', 1 );
		define( 'JPATH_BASE', $absolute_path );
		define( 'DS', DIRECTORY_SEPARATOR );

		// Load the framework
		require_once ( JPATH_BASE . DS . 'includes' . DS . 'defines.php' );
		require_once ( JPATH_BASE . DS . 'includes' . DS . 'framework.php' );

		// create the mainframe object
		$mainframe = & JFactory::getApplication( 'site' );

		// Initialize the framework
		$mainframe->initialise();
		
		// load system plugin group
		JPluginHelper::importPlugin( 'system' );

		// trigger the onBeforeStart events
		$mainframe->triggerEvent( 'onBeforeStart' );
		$lang =& JFactory::getLanguage();
		$mosConfig_lang = $GLOBALS['mosConfig_lang']          = strtolower( $lang->getBackwardLang() );
		// Adjust the live site path

		$mosConfig_live_site = str_replace('/administrator/components/com_virtuemart', '', JURI::base());
		$mosConfig_absolute_path = JPATH_BASE;
	} else {
		define('_VALID_MOS', '1');
		require_once($mosConfig_absolute_path. '/includes/joomla.php');
		require_once($mosConfig_absolute_path. '/includes/database.php');

		$database = new database( $host, $user, $password, $db, $dbprefix );
		$mainframe = new mosMainFrame($database, 'com_virtuemart', $mosConfig_absolute_path );
	}

	$my_path = dirname($_SERVER['SCRIPT_FILENAME']);
	$mambo_path = str_replace("administrator/components/com_virtuemart", "", $my_path);

	$mosConfig_absolute_path = $mambo_path;
	
	/*** Начало части VirtueMart ***/
	require_once($mosConfig_absolute_path.'/administrator/components/com_virtuemart/virtuemart.cfg.php');
	require_once( CLASSPATH. 'ps_main.php');
	require_once( CLASSPATH. 'language.class.php' );
	require_once( $mosConfig_absolute_path . '/includes/phpmailer/class.phpmailer.php');
	
	
	$mail = new PHPMailer();
	$mail->PluginDir = $mosConfig_absolute_path . '/includes/phpmailer/';
	$mail->SetLanguage("en", $mosConfig_absolute_path . '/includes/phpmailer/language/');

	/* Загрузка файла класса базы данных VirtueMart */
	require_once( CLASSPATH. 'ps_database.php' );

	/*** END части VirtueMart ***/

	/* Загрузка файла конфигурации Яндекс.Деньги */
	require_once( CLASSPATH. 'payment/ps_yandex_money.cfg.php' );

	$ym_shopID=YM_SHOPID;				//Ваш "shopID" (идентификатор магазина) в системе Яндекс.Деньги
	$ym_SCID=YM_SCID; 					//Ваш "SCID" (идентификатор витрины) в системе Яндекс.Деньги
	$ym_shopPassword=YM_SHOPPASSWORD;	//Ваш "shopPassword" (секретный пароль) в системе Яндекс.Деньги

	$ym_action = ( isset($_REQUEST['action']) ? $_REQUEST['action'] : 'testAction' ); // Тип запроса

	$rezult='';

	$error=1; // Взводим код ошибки

	// Контрольные данные о заказе:
	// Идентификатор запроса
	$order_invoice=( isset($_REQUEST['invoiceId']) ? $_REQUEST['invoiceId'] : 0 );
	// Сумма заказа
	$order_amount=( isset($_REQUEST['orderSumAmount']) ? $_REQUEST['orderSumAmount'] : 0.0 );
	// Код валюты для суммы заказа
	$order_currency=( isset($_REQUEST['orderSumCurrencyPaycash']) ? intval($_REQUEST['orderSumCurrencyPaycash']) : 0 );
	// Код процессингового центра Оператора для суммы заказа
	$order_bank=( isset($_REQUEST['orderSumBankPaycash']) ? intval($_REQUEST['orderSumBankPaycash']) : 0 );
	// Идентификатор плательщика (присланный в платежной форме)
	$order_customer=( isset($_REQUEST['customerNumber']) ? $_REQUEST['customerNumber'] : 0 );
	// Контрольный MD5-хеш
	$md5=( isset($_REQUEST['md5']) ? $_REQUEST['md5'] : md5("Yandex.Money demo mode") );
	// Номер заказа в БД магазина (присланный в платежной форме)
	$order_id=$_REQUEST['orderNumber'];
	
	$sum=floatval($order_amount);
	$qv = "";

	//Ответ на запрос checkOrder от Яндекс.Денег (проверка параметров заказа в базе данных)
	if($_REQUEST['action']=='checkOrder' && $order_id ) 
	{ 
		//Проверка кода и переводимой за него суммы
		$qv = "	SELECT `order_id`,
						`order_total`,
						`order_status` 
				FROM #__{vm}_orders 
				WHERE 
						`order_id`='".$order_id."' 
						AND `order_total`>='".$sum."' 
						AND `order_status` in ('".YM_WAIT_STATUS."','".YM_CHECK_STATUS."')";
		$error=0;
	}

	//Ответ на запрос paymentAviso от Яндекс.Денег (прием оплаты)
	if($_REQUEST['action']=='paymentAviso' && $order_id ) 
	{ 
		//Проверка кода и переводимой за него суммы
		$qv = "	SELECT `order_id`,
						`order_total`,
						`order_status` 
				FROM #__{vm}_orders 
				WHERE 
						`order_id`='".$order_id."' 
						AND `order_total`>='".$sum."' 
						AND `order_status` in ('".YM_CHECK_STATUS."','".YM_PAYMENT_STATUS."')";
		$error=0;
	}

	// Если не определили тип запроса - это ошибка
	if( $error )
	{
		//Отвечаем серверу Яндекс.Денег, кодом 200 - ИС Контрагента не в состоянии разобрать запрос. Оператор считает ошибку окончательной и не будет осуществлять перевод.
		$rezult=answer($ym_action,$ym_shopID,$order_invoice,200); 
		$error=1;
	} elseif ( $qv ) {
		//Запрос в к базе данных о заказе
        	$dbbt = new ps_DB;
        	$dbbt->query($qv);
        	$dbbt->next_record();

		//Если в базе данных найдена строка с соответсвующим номером заказа и статусом "в обработке", то отвечаем ОК
		if ( $dbbt->num_rows() == 1 )
		{
			if ( strcasecmp(md5("$ym_action;$order_amount;$order_currency;$order_bank;$ym_shopID;$order_invoice;$order_customer;$ym_shopPassword"), $md5) === 0 )
			{
				$time=time();
				$d = array();
				$d['order_id'] = $order_id;	//Идентификатор записи заказа
				$is_repeat_request = false;	//Повторный запрос от Яндекс.Денег с тем же invoiceId

				//Ответ на первый запрос paymentAviso от Яндекс.Денег (прием оплаты)
				if($_REQUEST['action']=='paymentAviso' && $dbbt->f('order_status') == YM_CHECK_STATUS) 
				{
					//Изменяем статус заказа на ОПЛАЧЕН
					$d['current_order_status'] = YM_CHECK_STATUS;	//Текущийщй статус заказа должен быть "Пользоваль подтвердил чек" W
					$d['order_status'] = YM_PAYMENT_STATUS;  		//Новый статус заказа - ОПЛАЧЕН O
					$d['notify_customer'] = 'Y'; 					//Уведомлять заказчика о смене статуса
				// Ответ на первый запрос checkOrder от Яндекс.Денег (подтверждение заказа на оплату)
				} elseif ($dbbt->f('order_status') == YM_WAIT_STATUS) {
					//Изменяем статус заказа на ПОЛЬЗОВАТЕЛЬ ПОДТВЕРДИЛ ЧЕК
					$d['current_order_status'] = YM_WAIT_STATUS; 	//Текущийщй статус заказа должен быть "В обработке" P
					$d['order_status'] = YM_CHECK_STATUS;  			//Новый статус заказа - ПОЛЬЗОВАТЕЛЬ ПОДТВЕРДИЛ ЧЕК W
					$d['notify_customer'] = 'N'; 					//Не уведомлять заказчика о смене статуса
				} else {
					$is_repeat_request = true;
				}
					
				if ( !$is_repeat_request )
				{
					// Обновление состояния заказа
					require_once ( CLASSPATH . 'ps_order.php' );
					$ps_order= new ps_order;

					//Отвечаем серверу Яндекс.Денег, что все хорошо, можно принимать деньги
					$rezult=answer($ym_action,$ym_shopID,$order_invoice,0);

                    //Есть ли таблица запросов от Яндекс.Денег?
                    $qv = "SHOW TABLES in `#__{vm}_orders_yandex_money_invoices`";
                    $dbbt->query($qv);
                    $dbbt->next_record();

					//Если таблицы нет - создаем ёё
                    if ( $dbbt->num_rows() != 1 )
                    {
						$qv = "CREATE TABLE IF NOT EXISTS  `#__{vm}_orders_yandex_money_invoices` (
                        				invoice_id BIGINT NOT NULL,
                                    	order_id VARCHAR(128) NOT NULL DEFAULT '',
                                    	order_total FLOAT(10,2) NOT NULL DEFAULT 0.0,
                                    	order_status CHAR(2) NOT NULL DEFAULT '',
                                    PRIMARY KEY(invoice_id)
						)";
                        $dbbt->query($qv);
					}

					//Создаем запись о заказе с указаным invoiceId или обновляем статус заказа
                    $qv = "INSERT INTO `#__{vm}_orders_yandex_money_invoices` 
								VALUES (
                                	'".$order_invoice."',
                                    '".$order_id."',
                                    '".$sum."',
                                    '".( $ym_action=='paymentAviso' ? YM_PAYMENT_STATUS : YM_CHECK_STATUS )."'
								)
								ON DUPLICATE KEY UPDATE `order_status`='".( $ym_action=='paymentAviso' ? YM_PAYMENT_STATUS : YM_CHECK_STATUS )."'
					";
					$dbbt->query($qv);
 	
					$ps_order->order_status_update($d);
				} else {
					$qv = "SELECT `order_id`, `invoice_id`, `order_status`
								FROM `#__{vm}_orders_yandex_money_invoices`
								WHERE 
									`invoice_id`='".$order_invoice."'
									AND `order_id`='".$order_id."'
									AND `order_total`>='".$sum."'
									AND `order_status`='".( $ym_action=='paymentAviso' ? YM_PAYMENT_STATUS : YM_CHECK_STATUS )."'
					";
					$dbbt->query($qv);
					$dbbt->next_record();
					if ( $dbbt->num_rows() == 1 )
					{
						//Отвечаем серверу Яндекс.Денег, что все хорошо, такой заказ существует и в нужном статусе
						$rezult=answer($ym_action,$ym_shopID,$order_invoice,0);
					} else {
						//Отвечаем серверу Яндекс.Денег, кодом 100 - Отказ в приеме перевода с заданными параметрами. Оператор считает ошибку окончательной и не будет осуществлять перевод.
						$rezult=answer($ym_action,$ym_shopID,$order_invoice,100);
					}
				}

			} else {
				//Отвечаем серверу Яндекс.Денег, кодом 1 - Несовпадение подписи (или хеша), неверный ключ подписи. Оператор считает ошибку окончательной и не будет осуществлять перевод.
				$rezult=answer($ym_action,$ym_shopID,$order_invoice,1);
				$error=1;
			}
		} else {
			//Отвечаем серверу Яндекс.Денег, кодом 666 - Да, такой ошибки скорее всего нет, ну и ладно. Платеж то все-равно не прошел. Ну и хрен с ним.
			$rezult=answer($ym_action,$ym_shopID,$order_invoice,666); 
			$error=1;
		}
	}

	echo $rezult;
}
