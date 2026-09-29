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

---

## 🛠️ Arquivos Chave Modificados e Responsabilidades

| Arquivo | Finalidade |
| :--- | :--- |
| `app/Http/Controllers/StatementController.php` | Rotas de listagem de extratos recentes e método `destroy` com rollback de saldo. |
| `app/Http/Controllers/DashboardController.php` | Método `resetData` com opções de limpeza e validação da trava `ZERAR`. |
| `routes/web.php` | Rotas `/extratos/recentes`, `/extratos/importacao/{id}` e `/configuracoes/reset-dados`. |
| `resources/js/Components/Mobile/MobileQuickActionsGrid.vue` | Barra simplificada com 2 botões de ação essenciais. |
| `resources/js/Components/Dashboard/SafeToSpendGauge.vue` | Cartão principal limpo, com semáforo simplificado e atalho de configuração. |
| `resources/js/Components/Financial/PreferencesModal.vue` | Modal com ciclo salarial e sanfona de confirmação segura para zerar dados. |
| `resources/js/Components/Mobile/MobileMenuDrawer.vue` | Gaveta mobile com acesso direto a "Zerar Dados & Recomeçar". |
| `resources/js/Layouts/AppLayout.vue` | Menu lateral desktop com "Configurações & Zerar" e escuta global de eventos. |
| `resources/js/Pages/Dashboard.vue` | Painel central com abas responsivas (desktop) e botão de configurações no cabeçalho. |

---

## 🚀 Próximos Passos Sugeridos / Onde Paramos
1. **Validar no Dispositivo do Usuário**: Coletar feedback do usuário sobre a usabilidade da nova tela inicial mobile e desktop.
2. **Refinamento do Leitor de Cupom / OCR**: Garantir que a leitura por foto/câmera permaneça rápida e intuitiva para quem não quer digitar nada manualmente.
3. **Alertas Pré-Vencimento Simplificados**: Refinar notificações automáticas quando contas fixas estiverem próximas do vencimento sem que o usuário precise procurar na tela.
