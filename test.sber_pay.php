<?php
@include_once('sber_pay.php');

$params['ApiUrl'] = 'https://3dsec.sberbank.ru';
$params['userName'] = '';
$params['password'] = '';

$SBERPAY = new sber_pay($params);

echo '<pre>'.str_replace('<', '&lt;', print_r($_REQUEST, true)).'</pre>';

$do = $_REQUEST['do'];

if ($do == 'gotopay') {
    /* Регистрируем оплату */


    list($thisUrl, $get) = explode('?', 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

    /* Параметры для регистрации оплаты */
    $params= [
        'orderNumber' => $_REQUEST['orderNumber'], /* Номер заказа в Интернет магазине */
        'amount' =>  round($_REQUEST['amount'] * 100), /* Сумма в копейках */
        'returnUrl' => $thisUrl.'?do=result&orderNumber='.$_REQUEST['orderNumber'], /* ссылка страницы при успешной оплате */
        'failUrl' => $thisUrl.'?do=fail', /* ссылка страницы при НЕуспешной оплате */
    ];

    $sbHeader = $SBERPAY->orderRegister($params);

    $sbResult = json_decode($sbHeader['content'], true);

    if($sbResult['errorCode'] > 0) {
        // Если возникла ошибка, то выводим её
        echo '<div>ERROR CODE: '.$sbResult['errorCode'].'</div>';
        echo '<div>ERROR TEXT: '.$sbResult['errorMessage'].'</div>';
    } else {
        /* сохраним ID оплаты */
        file_put_contents('pays.txt', $sbResult['orderId']."\n", FILE_APPEND);
        echo '<script>window.location = "'.$sbResult['formUrl'].'"</script>';
    }


} elseif ($do == 'result') {
    /* Страница успешной оплаты */
    $isok = true;

    /* проверим статус заказа с Сбере */
    $params = [
        'orderId' => $_REQUEST['orderId']
    ];
    $status = $SBERPAY->getOrderStatus($params);

    $status_text = array(
        0 => 'Заказ зарегистрирован, но не оплачен',
        1 => 'Предавторизованная сумма захолдирована (для двухстадийных платежей)',
        2 => 'Проведена полная авторизация суммы заказа',
        3 => 'Авторизация отменена',
        4 => 'По транзакции была проведена операция возврата',
        5 => 'Инициирована авторизация через ACS банка-эмитента',
        6 => 'Авторизация отклонена'
    );

    if ($status['orderStatus'] < 2) {
        $isok = false;
        $error = '<p>Ожидается ответ от банка ... </p>';
        $error .= '<script>setTimeout(function() {location.reload(true)}, 3000)</script>';
        $error .= '<p>'.$status['orderStatus'].'</p>';
        $error .=  $status_text[$status['orderStatus']];;
    }
    if ($status['orderStatus'] > 2) {
        $isok = false;
        $error = $status_text[$status['orderStatus']];
    }

    if ($isok) {
        /* Оплата прошла успешно */
        echo '<h1>Оплата заказа №'.$_REQUEST['orderNumber'].' прошла успешно</h1>';
    } else {
        /* К нам пришла оплата которая не зарегистрирована в системе */
        echo $error;
    }
} elseif ($do == 'fail') {
    /* Страница НЕуспешной оплаты */
    echo '<h1>Оплата не прошла</h1>';
}
if ($do == '') {
    /* Форма оплаты */
    ?>
    <form >
        <input type="hidden" name="do" value="gotopay">
        <input type="text" name="orderNumber" placeholder="Номер заказа">
                <input type="text" name="amount" placeholder="Сумма">
        <input type="submit">
    </form>
    <?
}
?>