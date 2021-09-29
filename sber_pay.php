<?
class sber_pay {

    public $SBERBANK_LOGIN_API = '';
    public $SBERBANK_PASSWORD = '';
    public $SBERBANK_REST_URL = 'https://securepayments.sberbank.ru';

    /**
     * Конструктор класса
     * @param $params - массив параметров
     * @param $params['ApiUrl'] = Базовый урл для запросов в Сбербанк
     * @param $params['userName'] = Login
     * @param $params['password'] = Password
     */
    function __construct($params) {
        if ($params['ApiUrl'] != '') {
            $this->SBERBANK_REST_URL = trim($params['ApiUrl'], '/').'';
        }
        $this->SBERBANK_LOGIN_API = $params['userName'];
        $this->SBERBANK_PASSWORD = $params['password'];
    }

    /**
     * Регистрирует заказ в системе СБЕРБАНКА
     * @param int $params['orderNumber'] - Id заказа на стороне ИМ
     * @param int $params['amount'] - Сумма платежа в копейках
     * @param string $params['returnUrl'] - Адрес, на который требуется перенаправить пользователя в случае успешной оплаты
     * @param string $params['failUrl '] - Адрес, на который требуется перенаправить пользователя в случае НЕуспешной оплаты
     * @return array
     */
    function orderRegister($params)
    {
        $url = $this->SBERBANK_REST_URL . '/payment/rest/register.do';
        $result = $this->getWebPageByCURL($url, $params);
        return $result;
    }

    /**
     * Получает статус заказа в системе СБЕРБАНКА
     * @param string $params['orderId'] - Номер заказа в платежной системе Сбера
     * @param string $params['orderNumber'] - Номер заказа в Интернет магазине
     * @return array
     */
    function getOrderStatus($params)
    {
        $url = $this->SBERBANK_REST_URL . '/payment/rest/getOrderStatusExtended.do';
        $result = $this->getWebPageByCURL($url, $params);
        return $result;
    }

    /**
     * Собирает запрос для обращения на указанный url по работе со СБЕРБАНКОМ.
     * Используется cURL + протокол TLS v1.2
     * @param string $url
     * @param array $params
     * @return array
     */
    function getWebPageByCURL($url, $params)
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_HEADER => false, // don't return headers
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false, // Disabled SSL Cert checks
            CURLOPT_SSLVERSION => 6 //Integer NOT string TLS v1.2
        );

        $params['userName'] = $this->SBERBANK_LOGIN_API;
        $params['password'] = $this->SBERBANK_PASSWORD;

        $url = $url . '?' . http_build_query($params);


        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);

        $header['url'] = $url;
        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;
        return $header;
    }
}
