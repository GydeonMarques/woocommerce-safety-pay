<?php

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class SafetyPay_API
{

    private $environment;
    private $sandbox_api_key;
    private $production_api_key;

    private static $SANDBOX = "sandbox";
    private static $PRODUCTION = "production";
    private static $API_VERSION = "20200803";

    public static $EXPRESS_APPLICATION_ID = '1';
    public static $DIRECT_APPLICATION_ID = '7';

    private static $PRODUCTION_API_URL = 'https://mws.safetypay.com/mpi/api/v1/';

    private static $SANDBOX_API_URL = 'https://sandbox-mws.safetypay.com/mpi/api/v1/';

    /**
     * Constructor
     * @throws Exception
     */
    public function __construct($environment = null, $sandbox_api_key = null, $production_api_key = null)
    {
        if (is_null($environment)) {
            throw new Exception("Missing environment!");

        } else if (is_null($sandbox_api_key)) {
            throw new Exception("Missing sandbox api key!");

        } else if (is_null($production_api_key)) {
            throw new Exception("Missing production api key!");

        } else if ($environment !== self::$SANDBOX && $environment !== self::$PRODUCTION) {
            throw new Exception("Invalid environment");
        }

        $this->environment = $environment;
        $this->sandbox_api_key = $sandbox_api_key;
        $this->production_api_key = $production_api_key;
    }

    /**
     * Cria um objeto de pagamento com as informações do pedido e envia a solicitação para api do SafetyPay
     * para que o pagamento possa ser processado
     * @param null $params dados da requisição
     * @return array
     * @throws Exception
     */
    public function create_payment($params): array
    {
        $api_key = null;
        if (is_null($params)) {
            throw new Exception('Missing params for request');

        } else if ($this->environment === self::$SANDBOX) {
            $api_key = $this->sandbox_api_key;

        } else if ($this->environment === self::$PRODUCTION) {
            $api_key = $this->production_api_key;
        }

        return self::send_request($params, $api_key, 'online-payment-requests', 'POST');
    }

    /**
     * Envia uma solicitação para api do SafetyPay
     * @param array $params
     * @param string $method
     * @return array
     */
    private function send_request(array $params = array(), $api_key = null, string $endpoint = '', string $method = 'GET'): array
    {
        $data = array(
            'method' => $method,
            'headers' => array(
                'X-Api-Key' => $api_key,
                'X-Version' => self::$API_VERSION,
                'Content-Type' => 'application/json',
            )
        );

        if (in_array($method, array('POST'))) {
            $data['body'] = json_encode($params);
        }

        $url = ($this->environment === self::$SANDBOX ? self::$SANDBOX_API_URL : self::$PRODUCTION_API_URL) . $endpoint;
        return wp_remote_request(esc_url_raw($url), $data);
    }
}