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

---

## 🛠️ Arquivos Chave Modificados e Responsabilidades

| Arquivo | Finalidade |
| :--- | :--- |
| `app/Services/VisionOcrService.php` | Motor OCR com prompt para descontos, totais de linha e reconciliação matemática. |
| `app/Actions/Receipts/ProcessReceiptScanAction.php` | Persistência de `ReceiptScan` e de `ReceiptItem` com cálculo preciso de `total_price`. |
| `app/Http/Controllers/ReceiptScannerController.php` | Atualização de itens editados na confirmação e agregação de dados para "O Que Mais Compro". |
| `resources/js/Components/Scanner/ReceiptReviewCard.vue` | Interface de revisão de comprovante com edição e recálculo dinâmico de itens. |
| `resources/js/Pages/Scanner/Index.vue` | Tela com abas para histórico de comprovantes e ranking "O Que Mais Compro". |
| `resources/js/Components/Mobile/MobileMenuDrawer.vue` | Atalho para "Scanner & O Que Mais Compro" e "Zerar Dados & Recomeçar" no celular. |
| `resources/js/Layouts/AppLayout.vue` | Menu lateral com atalho para Scanner e escuta global para o modal de preferências. |
| `app/Http/Controllers/StatementController.php` | Rotas de extratos recentes e estorno seguro via `destroy`. |
| `app/Http/Controllers/DashboardController.php` | Lógica de reset e higienização de base (`resetData`). |

---

## 🚀 Próximos Passos Sugeridos / Onde Paramos
1. **Testar com Cupons Físicos do Usuário**: Fazer o upload de uma nota fiscal real para conferir a precisão da leitura com descontos e o preenchimento automático do ranking "O Que Mais Compro".
2. **Alertas Preventivos de Contas Fixas**: Refinar avisos inteligentes antes da data de vencimento.

