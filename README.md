## Compatibilidade

- Compatível com WooCommerce 3.0+.
- Testado e desenvolvido com base na API v.2 do BoaCompra;
- Compatível com PHP 5.4.x à 7.x.x;
- Requer a biblioteca cURL ativa na hospedagem.
- Obrigatório o uso de certificado SSL com o protocolo TLS 1.2;

## Instalação

Envie os arquivos do plugin para o seu diretório de plugins (/wp-content/plugins/) ou instale usando a ferramenta de "Adicionar novo plugin" do WordPress.
Na área de plugins do WordPress, ative o módulo BoaCompra for WooCommerce

![](https://user-images.githubusercontent.com/22198227/63347955-a410ff00-c32e-11e9-9cd9-ef7af5435d7c.png "Instalação")

## Configuração

---
#### 1 - Ativação

##### MerchantID & SecretKey

A Ativação, MerchantID & SecretKey são os primeiros passos para tornar a sua integração funcional. Após se cadastrar e formalizar a contratação do serviço junto ao BoaCompra, você receberá um SecretKey que será utilizado para referenciar a sua conta e validar os pagamentos processados.

Com os dados em mãos basta selecionar o Ambiente BoaCompra como Produção e em seguida, e copiar e colar o seu MerchantID e o seu SecretKey nos campos indicados abaixo.

![](https://user-images.githubusercontent.com/22198227/63347983-b9862900-c32e-11e9-871c-d5e1724bb0eb.png "Configuração - Ativação, MerchantID & SecretKey")

---
#### 2 - Opções de Pagamento

O módulo disponibiliza 3 opções de pagamento via Direct Checkout:

- Cartão de Crédito

![](https://user-images.githubusercontent.com/22198227/63348042-dde20580-c32e-11e9-91fe-36351d7d34c1.png "Configuração - Opções de Pagamento - Cartão de Crédito")

- Boleto Bancário

![](https://user-images.githubusercontent.com/22198227/63348067-ec302180-c32e-11e9-9c6c-fa2496f310bc.png "Configuração - Opções de Pagamento - Boleto Bancário")

- E-Wallet

![](https://user-images.githubusercontent.com/22198227/63348102-fd792e00-c32e-11e9-8635-d6818a6e62be.png "Configuração - Opções de Pagamento - E-Wallet")

---
#### 3 - Parcelamento

Defina o máximo de parcelas aceitas pela loja, selecione entre 1 e 12 parcelas.

![](https://user-images.githubusercontent.com/22198227/63348190-2d283600-c32f-11e9-8f09-4e94fe46ebf3.png "Configuração - Parcelamento")

**`Observação:`**

A taxa de juros pode variar de acordo com o teto de faturamento da loja ou a sua negociação contratual junto ao BoaCompra.

---
#### 4 - Boleto Bancário

Na opção Mensagem de Checkout do Boleto, defina a mensagem que será exibida ao cliente no pagamento via boleto, exemplo: "Após a confirmação do pedido, lembre-se de quitar o boleto o mais rápido possível."

![](https://user-images.githubusercontent.com/22198227/63348232-3e714280-c32f-11e9-94ba-10e3080be0ca.png "Configuração - Boleto Bancário")

---
#### 5 - Status de Pedido

Para facilitar o gerenciamento do pedido disponibilizamos a opção de mapeamento de Status.

Os Status disponíveis são:

- Status Iniciado (O pedido mudará automaticamente para este status quando a transação estiver marcada como Pagamento Pendente no BoaCompra)
- Status Aprovado (O pedido mudará automaticamente para este status em caso de aprovação no BoaCompra)
- Status Cancelado (O pedido mudará automaticamente para este status quando a transação for cancelada no BoaCompra)
- Status Aguardando (O pedido mudará automaticamente para este status quando a transação estiver no status aguardando no BoaCompra)

![](https://user-images.githubusercontent.com/22198227/61637073-40cb7880-ac6d-11e9-8c02-f794705f7a2d.png "Configuração - Status de Pedido")

**`Atenção`**

Exitem 02 formas de cancelar um pedido:

**a)** Acesse o pedido desejado no painel do WooCommerce e clique no botão "Reembolso". Em seguida, defina o "Total Reembolsado" e clique no botão "Reembolso R$X,XX por Pagamento via BoaCompra" e em tempo real o módulo transmitirá a requisição para o BoaCompra.

![](https://user-images.githubusercontent.com/22198227/63348304-5c3ea780-c32f-11e9-9eca-a6773d7de79b.png "Exemplo - Refund")

**b)** Diretamente em sua conta no BoaCompra em "Transações". Ao cancelar o pedido o BoaCompra irá transmitir para a sua loja a requisição de cancelamento.

**https://billing-partner.boacompra.com/transactions.php**

---
#### 6 - Habilitar logs do módulo

Habilite a opção para que o módulo registre tudo o que é enviado e recebido entre a sua loja e o BoaCompra.

![](https://user-images.githubusercontent.com/22198227/61637067-3f9a4b80-ac6d-11e9-89f1-d58d9e1b61df.png "Configuração - Habilitar logs do módulo")

Para visualizar os registros de Logs clique no link Logs ou acesse "WooCommerce > Status do Sistema > Logs > selecione o log boacompra-payment-xxxxx.log" e clique em "Visualizar" para analisar detalhes do que foi enviado e recebido entre a sua loja e o BoaCompra.

![](https://user-images.githubusercontent.com/22198227/63348577-e25aee00-c32f-11e9-82a5-b06b8912a447.png "Configuração - Visualização dos Logs")

---
## CHANGELOG

**1.0.0**

- Primeira versão do módulo BoaCompra;