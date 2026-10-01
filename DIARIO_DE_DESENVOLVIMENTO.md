# 📜 DIÁRIO DE DESENVOLVIMENTO & HISTÓRICO DE SESSÕES (DEIXASOBRAR)

> **Finalidade deste documento**: Servir como ponto único de verdade, memória contínua e recuperação rápida de contexto caso a sessão seja reiniciada, recarregada ou atualizada ("Restart to Update").
> **Regra de Manutenção**: Este arquivo deve ser sempre atualizado ao final de cada rodada de alterações ou no fim do dia de trabalho.

---

## 📌 Status Atual do Projeto
* **Data da última atualização**: 29/09/2026
* **Branch Git**: `main`
* **Ambiente de Testes**: 63 testes automatizados (464 asserções) com **100% de aprovação (`PASS`)**.
* **Frontend**: Compilação de produção Vite / Vue 3 / Tailwind / PWA ativa.

---

## 🔄 Resumo do Que Foi Feito Até Agora (Sessões Recentes)

### 1. Diagnóstico e Simplificação Radical da Linguagem e UX
* **Problema Identificado**: O sistema possuía jargões financeiros ("Liquidez Imediata", "Blindagem de Contas", "Hard Stop", "Radar de Vazamentos") e excesso de opções técnicas que afastavam o usuário leigo.
* **Ações Tomadas**:
  * Substituição de termos complexos por linguagem acessível de fácil compreensão:
    * *"Quanto posso gastar hoje"* no lugar de métricas bancárias complexas.
    * *"Minhas Contas Fixas"* no lugar de *"Blindagem"*.
    * *"Para Onde Foi Meu Dinheiro?"* no lugar de *"Radar de Vazamentos"*.
  * O indicador principal foi desenhado para funcionar como um semáforo compreensível:
    * 🟢 **Tudo Tranquilo**: Contas obrigatórias reservadas e dinheiro livre para o dia.
    * 🟡 **Cuidado com os Gastos**: Margem apertada.
    * 🔴 **Aperto no Mês**: Contas superaram o saldo; evitar gastos desnecessários.

---

### 2. Gestão de Erros de Extratos e Reset de Dados ("Zerar / Recomeçar")
* **Problema Identificado**: Caso o usuário enviasse um extrato bancário incorreto, com datas erradas ou quisesse limpar transações de teste, faltavam ferramentas para reverter o saldo e apagar os dados em lote.
* **Soluções Implementadas**:
  1. **Consolidação e Deduplicação Automática**: O processador de extratos (`ProcessUniversalStatementAction`) calcula uma assinatura única (hash) para cada movimentação (data + valor + conta + descrição), ignorando transações repetidas sem duplicar saldo.
  2. **Botão "Desfazer Importação"**:
     * Na aba de extratos (`StatementDropzone.vue`), agora são listados os últimos arquivos enviados com contagem de lançamentos e data.
     * Ao clicar em *"Desfazer Importação"*, o backend (`StatementController@destroy`) calcula o impacto líquido daquele arquivo na conta bancária, reverte o saldo e apaga com segurança todas as transações importadas por ele.
  3. **"Zona de Limpeza (Zerar Dados e Recomeçar)"**:
     * Criada no backend (`DashboardController@resetData`) e no frontend (`PreferencesModal.vue`).
     * Permite dois modos:
       * **Limpar apenas Movimentações & Extratos**: Apaga lançamentos, comprovantes e extratos, mantendo as contas bancárias e contas fixas cadastradas, zerando o saldo para recomeçar o mês.
       * **Zerar Tudo (Reinício Completo)**: Apaga transações, extratos, categorias criadas e zera o saldo de todas as contas.
     * **Trava de Segurança**: Exige digitação obrigatória da palavra **`ZERAR`** para habilitar a execução.

---

### 3. Simplificação Radical da Visão Mobile (Celular)
* **Problema Identificado**: A tela inicial do celular apresentava excesso de botões: uma grade de 8 botões no topo, 3 abas espremidas no meio, mais 3 botões dentro do cartão verde, banner e menu inferior, duplicando atalhos em múltiplos lugares.
* **Mudanças Concluídas**:
  * **Grade de 8 botões substituída por 2 Botões de Alto Destaque**:
    * 🟢 **`+ Anotar Gasto`** *(Registrar despesa na hora)*.
    * 🔵 **`Subir Extrato`** *(Importar PDF, OFX ou CSV)*.
  * **Ocultação da barra de abas espremida no celular**: A tela mobile agora tem fluxo de rolagem vertical 100% natural e limpo.
  * **Limpeza do Cartão da Calculadora de Sobra**: Removidas as pílulas duplicadas internas, adicionando apenas um ícone discreto de ajustes `⚙️` no cabeçalho do cartão.
  * **Atalhos Diretos para Zerar Dados**:
    * **No Celular**: Adicionado botão destacado em vermelho `⚠️ Zerar Dados & Recomeçar` dentro do menu gaveta (aberto pelo botão `Menu` no rodapé).
    * **No Notebook/Desktop**: Adicionado botão `⚙️ Configurações & Zerar Dados` no cabeçalho superior e no menu lateral sob *"Minha Conta"*.

### 4. Aperfeiçoamento do Scanner OCR e Análise de Produtos ("O Que Mais Compro")
* **Problema Identificado**:
  * Em cupons fiscais brasileiros, descontos de clube e produtos pesados (kg) causavam divergência entre a soma dos itens e o valor final cobrado.
  * Os itens extraídos da nota ficavam guardados no banco, mas não havia uma visualização acessível para o usuário saber o que mais compra na feira/mercado, nem como corrigir itens caso a foto ficasse borrada.
* **Ações Tomadas**:
  1. **Motor Visual com Reconciliação Matemática (`VisionOcrService`)**:
     * Prompt de IA atualizado para extrair `subtotal`, `discount` (descontos de clube) e o `total_price` exato impresso na linha de cada produto.
     * Reconciliação matemática inteligente: se houver centavos de arredondamento ou desconto global, o total é calibrado automaticamente com a soma real.
     * Salva o `total_price` exato no modelo `ReceiptItem`.
  2. **Edição Rápida de Itens no Card de Revisão (`ReceiptReviewCard.vue`)**:
     * Na conferência logo após tirar a foto, o usuário pode editar o nome do produto, quantidade, preço unitário, total do item ou excluir itens ilegíveis.
     * O total da nota recalcula em tempo real conforme o usuário ajusta os itens.
  3. **Nova Tela / Aba "O Que Mais Compro" (`Scanner/Index.vue`)**:
     * Criada visualização direta dividida em abas:
       * **Notas & Comprovantes**: Histórico de notas com foto e botão de visualização detalhada.
       * **O Que Mais Compro**: Ranking dos produtos mais comprados (quantidade acumulada, quantas vezes comprou, total gasto e classificação entre essencial/supérfluo).
     * Atalhos diretos adicionados no menu gaveta do celular e no menu lateral do notebook ("Scanner & O Que Mais Compro").

### 5. Resolução da Divergência no Scanner de Notas Reais (R$ 45,00 vs R$ 28,84) & Otimização do OCR
* **Data**: 01/10/2026
* **Causa Raiz Diagnosticada**:
  * Ao escanear o cupom de supermercado no celular, a aplicação exibia `R$ 45,00` ("Comprovante Identificado", "Item de Consumo Geral") em vez do valor real de `R$ 28,84` e dos 5 produtos.
  * O valor `R$ 45,00` é exatamente o retorno estático da função `fallbackExtraction()` de simulação/offline no `VisionOcrService.php`.
  * **O que causou a queda no fallback**:
    1. O cURL do backend possuía timeout curto (35s). Fotos em resolução nativa enviadas para a API do Gemini demoravam ~40s para transitar e inferir, gerando `cURL error 28: Operation timed out`.
    2. Como o bloco `try/catch` tratava qualquer exceção caindo silenciosamente no fallback, o usuário recebia `R$ 45,00` estático em vez da leitura da nota.
    3. Flutuações pontuais de disponibilidade da Google (status 503 temporário) em horários de pico.
* **Soluções Implementadas**:
  1. **Compressão e Redimensionamento Prévio de Imagem no Servidor (`prepareOptimizedBase64`)**:
     * Imagens brutas agora passam por downscaling proporcional via GD para no máximo 1400px com compressão JPEG limpa antes da conversão Base64.
     * O tempo de inferência da IA no Google caiu de 45 segundos para **~6 a 18 segundos**, mantendo nitidez cirúrgica nos caracteres.
  2. **Timeout Estendido para 60 Segundos**:
     * Configurado `Http::timeout(60)` para acomodar com folga qualquer latência de conexão entre a HostGator e a Google AI.
  3. **Cadeia Resiliente de Modelos com Retry Automático (`modelsToTry`)**:
     * Implementada tolerância a falhas: caso o modelo preferido receba um 503 momentâneo, o sistema aguarda 1.5s e tenta novamente ou faz o fallback em cascata para `gemini-3.6-flash`, `gemini-3.8-flash` ou `gemini-flash-latest`.
  4. **Extração de Campos Típicos de NFC-e Brasileira**:
     * Prompt de visão enriquecido para capturar: Razão Social/Fantasia, CNPJ, Data/Hora da emissão, Subtotal, Desconto total, Valor Líquido a Pagar, Forma de Pagamento (`Cartão Débito`, `Cartão Crédito`, `Pix`, `Dinheiro`), Dígitos finais do cartão, e tabela de itens com Quantidade, Unidade (UN, KG), Preço Unitário, Valor Total e Categoria.
  5. **Card de Revisão Enriquecido (`ReceiptReviewCard.vue`)**:
     * Agora exibe a forma de pagamento detectada (ex: `💳 Cartão Débito (Final 3016)`), além do valor de desconto destacado.
* **Resultado do Teste no Cupom Real do Usuário**:
  * **Estabelecimento**: `A.C.D.A. IMPORTACAO E EXPORTACAO LTDA`
  * **CNPJ**: `84.308.980/0018-22`
  * **Data**: `26/09/2026 20:08:38`
  * **Forma de Pagamento**: `debit` (Final `3016`)
  * **Subtotal**: `R$ 29,24` | **Desconto**: `R$ 0,40` | **Total Final**: `R$ 28,84` (100% exato!)
  * **Itens Extraídos**:
    1. `PAO FORMA CASA PAO` - 1 UN x R$ 8,89 = R$ 8,89
    2. `EMB COZ FILE MI FRAC` - 0,196 KG x R$ 28,97 = R$ 5,68
    3. `QUEIJO MUSS NILZA FA` - 1 UN x R$ 7,99 = R$ 7,99
    4. `SALG CHEETOS 40G OND` - 1 UN x R$ 3,99 = R$ 3,99
    5. `PIPOCA DOCE BEBE 90G` - 1 UN x R$ 2,69 = R$ 2,29 (com desconto)

---

## 🛠️ Arquivos Chave Modificados e Responsabilidades

| Arquivo | Finalidade |
| :--- | :--- |
| `app/Services/VisionOcrService.php` | Redimensionamento GD prévio, cadeia resiliente de modelos, timeout de 60s e prompt completo para NFC-e. |
| `app/Actions/Receipts/ProcessReceiptScanAction.php` | Persistência de `ReceiptScan` e de `ReceiptItem` com cálculo preciso de `total_price`. |
| `app/Http/Controllers/ReceiptScannerController.php` | Atualização de itens editados na confirmação e agregação de dados para "O Que Mais Compro". |
| `resources/js/Components/Scanner/ReceiptReviewCard.vue` | Exibição de forma de pagamento, final do cartão, desconto e recálculo dinâmico de itens. |
| `resources/js/Pages/Scanner/Index.vue` | Tela com abas para histórico de comprovantes e ranking "O Que Mais Compro". |
| `resources/js/Components/Mobile/MobileMenuDrawer.vue` | Atalho para "Scanner & O Que Mais Compro" e "Zerar Dados & Recomeçar" no celular. |
| `resources/js/Layouts/AppLayout.vue` | Menu lateral com atalho para Scanner e escuta global para o modal de preferências. |
| `app/Http/Controllers/StatementController.php` | Rotas de extratos recentes e estorno seguro via `destroy`. |
| `app/Http/Controllers/DashboardController.php` | Lógica de reset e higienização de base (`resetData`). |

---

## 🚀 Próximos Passos Sugeridos / Onde Paramos
1. **Deploy / Atualização no Servidor HostGator**:
   * O usuário deve rodar `git pull origin main` no terminal cPanel/SSH da HostGator e `php artisan config:cache` (ou recarregar arquivos modificados).
2. **Realizar Novo Teste via Celular**: Escanear a nota para validar a experiência ao vivo em produção.
3. **Alertas Preventivos de Contas Fixas**: Refinar avisos inteligentes antes da data de vencimento.


