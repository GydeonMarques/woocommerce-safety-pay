======== SAFETYPAY PARA WOOCOMMERCE ==================================================================================================
Este é o plugin não oficial do Safetypay para WooCommerce.
Ele permite que você faça pagamento usando o SafetyPay que um meio de pagamento que não é cartão,com a maior rede bancária que permite
pagamentos em dinheiro, transferências bancárias e transações Internacionais on-line para um mercado global de consumidores.

======= LICENÇA ======================================================================================================================
GPLv2 ou posterior URI de licença: http: www.gnu.orglicensesgpl-2.0.html

======= INSTALAÇÂO ===================================================================================================================
Versão minima suportada:

PHP: 5.6+
Woocommerce: 5.4.1+

======= CONFIGURAÇÃO DO PLUGIN =======================================================================================================
1°  -   Após realizar a instalação do plugin, siga todos os passos apresentados na tela de adminstração do plugin
        informando todos os valores de todos os campos corretamentes.
2°  -   Caso ainda não tenha os dados de produção, informe-os futuramente quando possível.


======= REALIZAR TESTES NO AMBIENTE SANDBOX ==========================================================================================
Para realizar testes no ambiente de sandbox siga os passos abaixo:

1°  -   Suponhamos que já tenha produtos cadastrados em sua loja, caso não tenha cadastre-os.
2°  -   Faça uma compra normalmente e nas opções de pagamento, escolha o SafeteyPay para processar o pagamento.
3°  -   Após concluir o pedido, você será redirecionado para a tela de check-out (nesse momento não será preciso completar o checkout, feche-a).
4°  -   Acesse o painel do SafetyPay através do  link https://sandbox-secure.safetypay.com/Merchants/Operations/ConsultAccountActivity.aspx
5°  -   Insira seus dados de acesso, clique no botão 'Login' e logo após clique no botão 'Search' para listar todas as transações.
6°  -   Verifique qual foi a última transação que você gerou e copie o id e o valor da transação.
7°  -   Acesse o painel do simulador de pagamentos através do link http://sandbox-demobank.safetypay.com/Default/Login.aspx
8°  -   Insira novamente seus dados de acesso e clique em 'Login'.
9°  -   Após o login com sucesso, você verá 4 campos:
        'Currency',
        'Account No',
        'Transaction ID',
        'Amount'
        no campo 'Currency', selecione a moeda no qual o pedido foi realizado,
        no campo 'Account No', mantenha a primeira opção selecionada por padrão,
        no campo 'Transaction ID' e 'Amount' cole o id e o valor que você copiou na 6° etapa
        logo após click no botão 'Accept'.
10° -   Após clicar no botão 'Accept', será apresentado uma tela confirmando os dados informados, clique no botão 'Confirm',
        ao confirmar a simulação do pagamento, o valor da transação será devidamente pago e a sua loja será notificada
        com a confirmação de pagamento. OBS: O tempo de envio da confirmação do pagamento pode variar em alguns minutos.