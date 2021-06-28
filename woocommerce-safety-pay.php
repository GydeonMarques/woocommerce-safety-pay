<?php /** @noinspection ALL */
/*
  Plugin Name:  SafetyPay for WooCommerce
  Plugin URI:   https://github.com/GydeonMarques/woocommerce-satefty-pay.git
  Description:  SafetyPay Payment Gateway Integration for WooCommerce.
  Version:      1.0.0
  Author:       Gideon Marques da Silva
  Author URI:   https://www.linkedin.com/in/gideon-marques-da-silva-40921a1b0/
  License:      GPL-2.0+
  License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
*/

if (!defined('ABSPATH')) exit;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

//Certifique-se de que WooCommerce está ativo
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;

// Os gateways de pagamento devem ser criados como plug-ins adicionais que se conectam ao WooCommerce.
// Dentro do plug-in, você precisa criar uma classe depois que os plug-ins forem carregados.
add_action('plugins_loaded', 'woocommerce_safety_pay_init', 0);

function woocommerce_safety_pay_init()
{

    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // E importante que sua classe de gateway estenda a classe de gateway base WooCommerce (WC_Payment_Gateway),
    // para que você tenha acesso a métodos importantes e à API de configurações.
    class WC_SafetyPay extends WC_Payment_Gateway
    {
        const SANDBOX = "sandbox";
        const PRODUCTION = "production";
        const SUPPORTED_CURRENCIES = array(
            'EUR' => '(€) Euro',
        );

        const DEFAULT_TITLE = "Pagar com SafetyPay";
        const DEFAULT_UNSUPPORTED_MESSAGE = "SafetyPay não suporta a moeda da sua loja, atualmente o suporte se dará apenas para a(s) moeda(s): ";
        const DEFAULT_SUCCESS_MESSAGE = "Obrigado por comprar conosco, começaremos a processar seu pedido em breve, confira se e-mail com os detalhes da sua compra";
        const DEFAULT_DESCRIPTION = "Pague com segurança utilizando o SafetyPay, a maior rede bancária que permite pagamentos em dinheiro, transferências bancárias e transações Internacionais on-line em todo mundo.";

        //ID exclusivo para o gateway
        public $id = "safetypay";
        public $icon;
        public $title;
        public $description;
        public $sandbox_mode;
        public $currency_code;
        public $sandbox_api_key;
        public $production_api_key;
        public $enabled_safetyPay;
        public $sandbox_signature_key;
        public $order_success_message;
        public $sandbox_webhook_secret;
        public $payment_link_expiry_time;
        public $production_signature_key;
        public $production_webhook_secret;
        public $send_email_shopper = true;
        public $process_payment_callback_uri;

        //uri de retorno de chamado de processar pagamento
        //Campos a serem mostrados na página de adminstração
        public $form_fields = array();

        public $visible_settings = array(
            "title",
            "description",
            "sandbox_mode",
            "currency_code",
            "sandbox_api_key",
            "enabled_safetyPay",
            "production_api_key",
            "send_email_shopper",
            "order_success_message",
            "sandbox_signature_key",
            "payment_link_expiry_time",
            "production_signature_key",
            "process_payment_callback_uri",
        );

        // Gateways podem oferecer suporte a assinaturas, reembolsos, métodos de pagamento salvos.
        public $supports = array('products');

        // Pode ser definido como verdadeiro se você quiser que os campos de pagamento
        // apareçam na finalização da compra (se estiver fazendo uma integração direta).
        public $has_fields = false;

        // Defina se o botão de pagamento do pedido  deve ser renomeado na seleção.
        public $order_button_text = "Pagar com SafetyPay";

        //Nome do plugin, que será mostrado na página de administração de plugin do WooCommerce.
        public $method_title = "SafetyPay";

        //Descrição plugin que será mostrado na página de administração.
        public $method_description = "<a target='_blank' href='https://www.safetypay.com/'>SafetyPay</a> é um meio de pagamento que não é cartão, com a maior rede bancária que permite pagamentos em dinheiro, transferências bancárias e transações Internacionais on-line para um mercado global de consumidores.";

        public function __construct()
        {

            //URL do ícone que será exibido na página de checkout próximo ao nome do seu gateway
            $this->icon = plugins_url('images/logo.png', __FILE__);

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->api_version = $this->get_option('api_version');
            $this->currency_code = $this->get_option('currency_code');
            $this->sandbox_api_key = $this->get_option('sandbox_api_key');
            $this->sandbox_mode = 'yes' === $this->get_option('sandbox_mode');
            $this->enabled_safetyPay = $this->get_option('enabled_safetyPay');
            $this->production_api_key = $this->get_option('production_api_key');
            $this->send_email_shopper = $this->get_option('send_email_shopper');
            $this->order_success_message = $this->get_option('order_success_message');
            $this->sandbox_signature_key = $this->get_option('sandbox_signature_key');
            $this->production_signature_key = $this->get_option('production_signature_key');
            $this->payment_link_expiry_time = $this->get_option('payment_link_expiry_time');

            //Inicia as configurações do webooks.
            //Webooks são notificações enviadas ao seu sistema por meio de um link de callback,
            //quando o status do pagamento e alterado. E responsabilidade do gateway de pagamento de
            //enviar o status do pagamento ao seu sistema, com base no status recebido, o seu sistema fará
            //as devidadas atualizações no status do pedido, ex: Pagamento confirmado | Falha no pagamento.
            add_action('woocommerce_api_' . $this->id, array($this, 'process_webhook'));

            //Rediciona o usuário para a página de agradecimentos
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

            //Permite salvar as informações alteradas pelo adminstrador da página
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        }

        /**
         * Inicia as configurações referente aos campos do formulário
         * da página de administração do SafetyPay
         */
        public function init_form_fields()
        {
            $default_form_fields = array(
                'enabled_safetyPay' => array(
                    'title' => __('Plugin', $this->id),
                    'label' => __('Ativar plugin SafetyPay', $this->id),
                    'description' => __('Ativar ou desativar sistema de pagamentos utilizando o SafetyPay.', $this->id),
                    'type' => 'checkbox',
                    'default' => 'yes'
                ),
                'send_email_shopper' => array(
                    'title' => __('E-mail', $this->id),
                    'label' => __('Enviar e-mail ao comprador', $this->id),
                    'description' => __('Ativar ou desativar o envio de e-mail ao comprador ao realizar transações.', $this->id),
                    'type' => 'checkbox',
                    'default' => 'yes',
                ),
                'currency_code' => array(
                    'title' => __('Moeda', $this->id),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'default' => 'EUR',
                    'description' => __('Código da moeda padrão de operação', $this->id),
                    'options' => self::SUPPORTED_CURRENCIES,
                ),
                'title' => array(
                    'title' => __('Título', $this->id),
                    'description' => __('Título da opção de pagamento que irá ser apresentado ao usuário durante o checkout.', $this->id),
                    'type' => 'text',
                    'default' => __(self::DEFAULT_TITLE, $this->id)
                ),
                'description' => array(
                    'title' => __('Descrição', $this->id),
                    'description' => __('Descrição da opção de pagamento que irá ser apresentado ao usuário durante o checkout.', $this->id),
                    'type' => 'textarea',
                    'default' => __(self::DEFAULT_DESCRIPTION, $this->id),
                ),

                'sandbox_mode' => array(
                    'title' => __('Modo sandbox', $this->id),
                    'label' => __('Ativar modo sandbox', $this->id),
                    'description' => __('Execute transações de teste no modo sandbox.<br><strong>Atenção:</strong> Enquanto o modo sandbox estiver ativo, todas as transações ocorrerão somente em modo sandbox, ao publicar sua loja em produção, tenha certeza que este campo estará desativado.', $this->id),
                    'type' => 'checkbox',
                    'default' => 'yes',
                ),
                'sandbox_api_key' => array(
                    'type' => 'text',
                    'title' => __('Chave da api sandbox', $this->id),
                    'description' =>
                        __('Chave do ambiente de sandbox para criar transações de testes. <br><br>Para obter sua chave de api do ambiente de sandbox:', $this->id) . '<br>' .
                        __('1. Navegue até o painel do <a href="https://sandbox-secure.safetypay.com/Merchants/Login.aspx" target="_blank">Sandbox SafetyPay</a>', $this->id) . '<br>' .
                        __('2. Informe seus dados de acesso e click em <strong>Login</strong>', $this->id) . '<br>' .
                        __('3. Click no menu <strong>Profile</strong>  > <strong>Credentials</strong>', $this->id) . '<br>' .
                        __('2. Copie a chave <strong>Api Key</strong> e cole na caixa acima.', $this->id),
                    'required' => true,

                ),
                'production_api_key' => array(
                    'title' => __('Chave da api produção', $this->id),
                    'type' => 'text',
                    'description' =>
                        __('Chave de assinatura do ambiente de produção para criar transações reais. <br><br>Para obter sua chave de api do ambiente de produção:', $this->id) . '<br>' .
                        __('1. Navegue até o painel do <a href="https://secure.safetypay.com/Merchants/Login.aspx" target="_blank">SafetyPay</a>', $this->id) . '<br>' .
                        __('2. Informe seus dados de acesso e click em <strong>Login</strong>', $this->id) . '<br>' .
                        __('3. Click no menu <strong>Profile</strong>  > <strong>Credentials</strong>', $this->id) . '<br>' .
                        __('2. Copie a chave <strong>Api Key</strong> e cole na caixa acima.', $this->id),
                    'required' => true,
                ),
                'sandbox_signature_key' => array(
                    'title' => __('Chave de assinatura sandbox', $this->id),
                    'type' => 'text',
                    'description' =>
                        __('Chave de assinatura do ambiente sandbox para criar transações de testes. <br><br>Para obter sua chave de assinatura do ambiente de produção:', $this->id) . '<br>' .
                        __('1. Navegue até o painel do <a href="https://secure.safetypay.com/Merchants/Login.aspx" target="_blank">SafetyPay</a>', $this->id) . '<br>' .
                        __('2. Informe seus dados de acesso e click em <strong>Login</strong>', $this->id) . '<br>' .
                        __('3. Click no menu <strong>Profile</strong>  > <strong>Credentials</strong>', $this->id) . '<br>' .
                        __('2. Copie a chave <strong>Signature Key</strong> e cole na caixa acima.', $this->id),
                    'required' => true,
                ),
                'production_signature_key' => array(
                    'title' => __('Chave de assinatura produção', $this->id),
                    'type' => 'text',
                    'description' =>
                        __('Chave do ambiente de produção para criar transações reais. <br><br>Para obter sua chave de assinatura do ambiente de produção:', $this->id) . '<br>' .
                        __('1. Navegue até o painel do <a href="https://secure.safetypay.com/Merchants/Login.aspx" target="_blank">SafetyPay</a>', $this->id) . '<br>' .
                        __('2. Informe seus dados de acesso e click em <strong>Login</strong>', $this->id) . '<br>' .
                        __('3. Click no menu <strong>Profile</strong>  > <strong>Credentials</strong>', $this->id) . '<br>' .
                        __('2. Copie a chave <strong>Signature Key</strong> e cole na caixa acima.', $this->id),
                    'required' => true,
                ),
                'process_payment_callback_uri' => array(
                    'title' => __('URL para receber notificações (webhook)', $this->id),
                    'type' => 'text',
                    'description' =>
                        __('Essa e a <strong>URL</strong> que o SafateyPay usará para notificar o seu sistema quando houver alguma mudança no status de pagamento. Toda vez que o seu sistema recebe essa notificação do SafetyPay, o status dos seus pedidos são atualizados automaticamente.<br><br>Para configurar a url de notificação:', $this->id) . '<br>' .
                        __('1. Navegue até o painel: <a href="https://sandbox-secure.safetypay.com/Merchants/Login.aspx" target="_blank">Sandbox SafetyPay</a> | <a href="https://secure.safetypay.com/Merchants/Login.aspx" target="_blank">SafetyPay</a>', $this->id) . '<br>' .
                        __('2. Informe seus dados de acesso e click em <strong>Login</strong>', $this->id) . '<br>' .
                        __('3. Click no menu <strong>Profile</strong> > <strong>Notifications</strong>', $this->id) . '<br>' .
                        __('2. Copie a url acima e cole no campo <strong>POST URL</strong>', $this->id),
                    'default' => __(get_site_url() . '/wc-api/' . $this->id, $this->id),
                    'custom_attributes' => array(
                        'readonly' => 'true',
                    ),
                ),
                'payment_link_expiry_time' => array(
                    'title' => __('Tempo de expiração do link de pagamento', $this->id),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'default' => '24',
                    'description' => __('Tempo de expiração do link de pagamento, após esse tempo são será possível realizar o pagamento utilizando o mesmo link, será necessário efeturar um novo pedido para obter um link atualizado.', $this->id),
                    'options' => array(
                        '2' => '2 Horas',
                        '4' => '4 Horas',
                        '8' => '8 Horas',
                        '16' => '16 Horas',
                        '24' => '24 Horas',
                        '36' => '36 Horas',
                        '48' => '48 Horas',
                    ),
                ),
                'order_success_message' => array(
                    'title' => __('Mensagem de agradecimento', $this->id),
                    'type' => 'textarea',
                    'description' => __('Mensagem que será adicionada à página de agradecimento e em notas do pedido após a finalização.', $this->id),
                    'default' => __(self::DEFAULT_SUCCESS_MESSAGE, $this->id)
                ),
            );

            foreach ($default_form_fields as $key => $value) {
                if (in_array($key, $this->visible_settings, true)) {
                    $this->form_fields[$key] = $value;
                }
            }
        }

        /**
         * Atualiza o status do pedido com base na resposta enviada pelo gateway de pagamento por meio
         * de um link de callback (webhooks)
         */
        public function process_webhook()
        {

            $data = file_get_contents('php://input');
            if (isset($data)) {
                header('HTTP/1.1 200 OK');
                $response = json_decode($data);
                $order = wc_get_order($response->order_id);
                //TODO - Webhooks under development...
                echo 'Success (under development)...';
            }

            exit();
        }

        /**
         * Processe o pagamento e devolva o resultado.
         *
         * @param int $order_id Order ID.
         * @return array
         */
        public function process_payment($order_id)
        {

            $order = new WC_Order($order_id);
            include_once dirname(__FILE__) . '/includes/safety-pay-api-.php';

            $sandbox_api_key = $this->get_option('sandbox_api_key');
            $production_api_key = $this->get_option('production_api_key');
            $environment = $this->sandbox_mode ? self::SANDBOX : self::PRODUCTION;

            $safety_pay_api = new SafetyPay_API($environment, $sandbox_api_key, $production_api_key);

            //Dados para criar a requisição de pagamento
            $params = array(
                "merchant_set_pay_amount" => false,
                "merchant_sales_id" => $order->get_id(),
                "requested_payment_type" => "StandardorBoleto",
                "payment_ok_url" => $this->get_return_url($order),
                "payment_error_url" => $this->get_return_url($order),
                "transaction_email" => $order->get_user()->user_email,
                "application_id" => SafetyPay_API::$EXPRESS_APPLICATION_ID,
                "language_code" => strtoupper(explode("_", get_locale())[0]),
                "expiration_time_minutes" => ((int)$this->payment_link_expiry_time) * 60,
                "send_email_shopper" => $this->send_email_shopper == 'yes' ? true : false,
                "sales_amount" => array(
                    "value" => $order->get_total(),
                    "currency_code" => $this->currency_code,
                )
            );

            // Chama a api do SafetyPay para processar o pagamento do pedido
            // e logo após verifica o retorno da api relacionado
            // ao processamento do novo pagamento
            $response = $safety_pay_api->create_payment($params);
            if (is_wp_error($response)) {
                wc_add_notice($response->get_error_message(), 'error');

            } else {

                $code = $response['response']['code'];
                $result = json_decode($response['body'], true);

                if ($code == 201) {

                    // Add algumas notas para o cliente
                    $order->add_order_note(self::DEFAULT_SUCCESS_MESSAGE, true, false);

                    // Atualiza o status do pedido (Aguardando pagamento...)
                    $order->update_status('on-hold', __('Aguardando pagamento...', $this->id));

                    //Salva o token da transação nos meta dados do pedido
                    $order->update_meta_data('digest_check', $result['digest_check']);

                    //Url de checkout para onde o usuário será redirecionado para realizar o pagamento.
                    $checkout_url = $result['gateway_token_url'];

                    //Limpa os items do carrinho
                    WC()->cart->empty_cart();

                    $order->save();

                    return array(
                        'result' => 'success',
                        'redirect' => $checkout_url,
                    );

                } else {
                    return array(
                        'result' => 'fail',
                        'redirect' => $this->get_return_url($order)
                    );
                }
            }
        }

        /**
         * Thank You page message.
         *
         * @param int $order_id Order ID.
         */
        public function thankyou_page($order_id)
        {
            wc_get_template(
                'thank-you-page.php',
                array(
                    'id' => $this->id,
                    'message' => $this->order_success_message,
                ),
                $this->id,
                plugin_dir_path(__FILE__) . 'templates/'
            );
        }

        /**
         * Este método verifica o tipo da moeda corrente da sua loja
         * e análiza os tipos de moedas que são suportadas por este gateway de pagamento
         */
        public function admin_options()
        {
            $supported_currencies = array();
            foreach (self::SUPPORTED_CURRENCIES as $key => $value) {
                $supported_currencies[] = $key;
            }
            if (in_array(get_woocommerce_currency(), $supported_currencies)) {
                parent::admin_options();
            } else {
                ?>
                <div class="inline error">
                    <p>
                        <strong><?php esc_html_e('Gateway disabled', 'woocommerce'); ?></strong>:
                        <?php esc_html_e(self::DEFAULT_UNSUPPORTED_MESSAGE . implode(' | ', self::SUPPORTED_CURRENCIES), 'woocommerce'); ?>
                    </p>
                </div>
                <?php
            }
        }
    }

    /**
     * Adicione o plugin do SafetyPay a lista de gateways de pagamentos do WooCommerce
     **/
    function woocommerce_add_safetyPay_gateway($gateways)
    {
        $gateways[] = 'WC_SafetyPay';
        return $gateways;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_safetypay_gateway');

}
