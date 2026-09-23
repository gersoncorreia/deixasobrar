# PROMPT MESTRE: SISTEMA SAAS & LANDING PAGE DE GESTÃO FINANCEIRA ("DEIXASOBRAR")
## STACK: LARAVEL 11 + VUE.JS 3 (INERTIA.JS / PINIA) + MYSQL 8 + TAILWIND CSS

---

## 1. VISÃO GERAL DO PRODUTO & BRANDING
Você é um Engenheiro de Software Staff, Especialista em Arquitetura Limpa (Clean Architecture), Lead Designer UI/UX e Especialista Frontend.
Sua missão é desenvolver a arquitetura completa, especificações técnicas, migrações MySQL, regras de negócio em Domain-Driven Design (DDD) modular, a **Landing Page institucional de alta conversão** e a **Área Autenticada SPA** para o SaaS:

* **Nome do Produto**: **DeixaSobrar**
* **Slogan Oficial**: *"Cuide dos gastos e deixe sobrar para o que importa."*
* **Proposta de Valor**: Um sistema simples, acolhedor e antifrágil desenhado para a pessoa comum, famílias e autônomos que desejam ter controle real do seu dinheiro sem enfrentar termos contábeis herméticos.

### Metodologia Central: "Dinheiro Livre Real" (Safe-to-Spend)
1. **Zero Economês**: Termos familiares e acolhedores (sem 'passivo circulante' ou 'DRE técnica').
2. **Previsibilidade por Teto Diário Seguro**:
   $$\text{Teto Diário} = \frac{\text{Saldo em Conta} - \text{Contas Fixas a Vencer} - \text{Reserva Mínima}}{\text{Dias Restantes até o Próximo Salário}}$$
3. **Raio-X de Vazamentos & Micro-Pix**: Identificação de pequenas saídas recorrentes (apostas/bets, delivery diário, tarifas e repasses) evidenciando o impacto acumulado no mês.
4. **Gerenciador de Quitação ("Adeus Carnês")**: Acompanhamento de parcelas (cartões, crediários como Bemol/Gazin) e cálculo da margem de renda liberada a cada liquidação.
5. **Importador Inteligente de Extratos**: Leitura e classificação automática de arquivos bancários em CSV, OFX e PDF.

---

## 2. ARQUITETURA TÉCNICA & BOAS PRÁTICAS

### Stack Tecnológica
- **Backend**: Laravel 11.x (PHP 8.3+)
- **Banco de Dados**: MySQL 8.0+ (InnoDB, charset `utf8mb4`, collation `utf8mb4_unicode_ci`)
- **SPA Bridge**: Inertia.js v1.x (renderização reativa sem sobrecarga de APIs REST desconectadas)
- **Frontend**: Vue.js 3 (Composition API com `<script setup>`, TypeScript estrito)
- **Estado Global**: Pinia (para carrinho de planos, modais, filtros e cálculos em cache)
- **Estilização**: Tailwind CSS v3.4+ com plugins `@tailwindcss/forms` e `@tailwindcss/typography`
- **Componentes UI**: Primitivas acessíveis Headless (Radix Vue / Reka UI) + Ícones Lucide (`lucide-vue-next`)
- **Visualização de Dados**: Chart.js com `vue-chartjs` otimizado em canvas
- **Qualidade & Linhas de Defesa**: Laravel Pint (PSR-12), PHPStan nível 8+, ESLint + Prettier

### Arquitetura de Pastas (Modular / Action-Domain-Responder)
```text
deixasobrar-saas/
├── app/
│   ├── Actions/                 # Casos de uso únicos (Single Action Classes)
│   │   ├── Financial/
│   │   │   ├── CalculateSafeToSpendAction.php
│   │   │   ├── ClassifyTransactionAction.php
│   │   │   └── ProcessStatementCsvAction.php
│   │   └── Billing/
│   │       └── HandleSubscriptionWebhookAction.php
│   ├── Enums/                   # Enums tipados do PHP 8.1+
│   │   ├── AccountType.php
│   │   ├── TransactionType.php
│   │   └── SubscriptionPlan.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── LandingPageController.php   # Renderiza a Landing Page de alta conversão
│   │   │   ├── DashboardController.php     # Painel principal autenticado
│   │   │   ├── TransactionController.php
│   │   │   ├── StatementController.php
│   │   │   └── DebtPayoffController.php
│   │   └── Requests/            # Validações estritas de entrada
│   ├── Models/                  # Eloquent com Scopes, Casts e Relacionamentos
│   └── Services/                # Parsers de extratos e gateways (Asaas / Stripe / PIX)
├── database/
│   ├── migrations/              # Estrutura relacional MySQL com índices de alta performance
│   └── seeders/                 # Categorias essenciais pré-configuradas
├── resources/
│   ├── js/
│   │   ├── Components/
│   │   │   ├── Landing/         # Componentes da Landing Page
│   │   │   │   ├── HeroSection.vue
│   │   │   │   ├── FeaturesGrid.vue
│   │   │   │   ├── CalculatorWidget.vue
│   │   │   │   ├── PricingPlans.vue
│   │   │   │   ├── Testimonials.vue
│   │   │   │   └── FaqAccordion.vue
│   │   │   ├── UI/              # Botões, Modais, Cards, Badges acessíveis
│   │   │   ├── Financial/       # Widget Teto Diário, Radar de Vazamentos, Progresso Carnês
│   │   │   └── Charts/          # Gráficos de fluxo financeiro
│   │   ├── Composables/         # useCurrencyFormat.ts, useFinancialCalculations.ts
│   │   ├── Layouts/
│   │   │   ├── SiteLayout.vue   # Layout institucional (Header, Footer)
│   │   │   └── AppLayout.vue    # Layout autenticado (Sidebar, Topbar)
│   │   └── Pages/
│   │       ├── Landing.vue      # Página principal institucional completa
│   │       ├── Dashboard.vue    # Painel interno
│   │       ├── Transactions/
│   │       └── Reports/
└── routes/
    ├── web.php                  # Rotas públicas e protegidas Inertia
    └── api.php                  # Webhooks de pagamento
```

---

## 3. MODELAGEM RELACIONAL MYSQL (MIGRATIONS)

```php
// database/migrations/2026_01_01_000001_create_deixasobrar_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Usuários & Assinaturas
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('plan_tier', 50)->default('free'); // free, pro_mensal, pro_anual, familia
            $table->string('status', 50)->default('active'); // active, trialing, past_due, canceled
            $table->timestamp('current_period_end')->nullable();
            $table->string('payment_method', 30)->default('pix');
            $table->timestamps();
        });

        // Contas e Instituições Bancárias
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->string('type', 30); // checking, savings, credit_card, cash
            $table->decimal('current_balance', 14, 2)->default(0.00);
            $table->string('bank_name', 50)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'type']);
        });

        // Categorias com Teto de Gastos
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->string('group_type', 40); // fixed_expense, routine_variable, habits_lifestyle, debt_installment, income
            $table->string('icon', 50)->default('tag');
            $table->string('color_hex', 7)->default('#2563eb');
            $table->decimal('budget_ceiling', 12, 2)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'group_type']);
        });

        // Lançamentos
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->date('transaction_date');
            $table->string('description', 255);
            $table->text('raw_statement_text')->nullable();
            $table->decimal('amount', 14, 2); // Negativo = Saída, Positivo = Entrada
            $table->string('type', 20); // income, expense, transfer
            $table->string('status', 20)->default('confirmed');
            $table->boolean('is_recurring')->default(false);
            $table->unsignedSmallInteger('installment_number')->nullable();
            $table->unsignedSmallInteger('installment_total')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['user_id', 'category_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('subscriptions');
    }
};
```

---

## 4. ESPECIFICAÇÃO DA LANDING PAGE DE ALTA CONVERSÃO (PAGE DESIGN)

A Landing Page deve ser implementada em **`resources/js/Pages/Landing.vue`** utilizando **Vue 3 + Tailwind CSS**, com estrutura moderna de storytelling e foco em conversão imediata para o plano gratuito ou teste grátis:

### Seções Estruturais da Landing Page:
1. **Header Fixo (Navigation Bar)**:
   - Logotipo: **DeixaSobrar** com ícone moderno de folga/moeda ascendente em Tailwind (`emerald-500` e `slate-900`).
   - Links: *Como Funciona*, *Diferenciais*, *Simulador*, *Planos*, *Depoimentos*.
   - Botões de Ação: `Entrar` (Ghost button) e `Começar Grátis` (Primary Emerald button pulsante).

2. **Hero Section (Acima da Dobra)**:
   - **Headline de Impacto**: *"O controle financeiro que não te dá dor de cabeça. Cuide dos seus gastos e deixe sobrar para o que importa."*
   - **Sub-headline**: *"Chega de planilhas complexas com nomes difíceis. Saiba exatamente quantos reais você pode gastar por dia sem faltar dinheiro para pagar suas contas."*
   - **CTAs Duplos**: `[Comece Grátis em 1 Minuto]` + `[Ver Demonstração Prática (Vídeo)]`.
   - **Mockup Visual 3D / Dashboard Preview**: Prévia do widget central mostrando: *Seu Teto Diário Seguro: R$ 42,50/dia até o dia 28*.

3. **Seção de Dores vs. Solução (O que torna o DeixaSobrar único)**:
   - *Aplicativos Tradicionais*: Cheios de gráficos que você não entende, cobram para conectar ao banco, e só te mostram onde você errou no passado.
   - *DeixaSobrar*: Te fala o que você pode gastar hoje, encontra vazamentos de pequenos Pix e apostas, e comemora cada carnê que você quita.

4. **Widget Interativo: "Simulador de Folga no Bolso"**:
   - Componente Vue interativo onde o visitante insere seu salário líquido e suas contas fixas.
   - O simulador calcula instantaneamente na tela o seu teto diário seguro e exibe o aviso: *"Com o DeixaSobrar, você elimina em média R$ 380/mês em gastos invisíveis."*

5. **Apresentação dos Recursos Principais (Features Cards)**:
   - **Teto Diário Seguro**: Nunca mais passe aperto na semana anterior ao pagamento.
   - **Radar de Micro-Pix & Apostas**: Mostra o valor real acumulado de pequenas saídas que somam centenas de reais.
   - **Importador Arrasta-e-Solta**: Leia seus extratos bancários de qualquer banco em segundos.
   - **Adeus Dívida**: Acompanhe o fim de carnês e veja sua renda mensal aumentar.

6. **Tabela de Preços & Planos (SaaS Pricing)**:
   - **Gratuito**: Registro de contas, saldo seguro e importação básica (R$ 0/mês).
   - **Plano Pro (Mais Popular)**: Importações ilimitadas, Radar de Vazamentos por IA, Alertas de Teto Diário e Suporte Prioritário (**R$ 19,90/mês** ou **R$ 179,90/ano** via PIX ou Cartão).
   - **Plano Família**: 2 acessos independentes compartilhando as despesas da casa (**R$ 29,90/mês**).

7. **Perguntas Frequentes (FAQ Acordeão)** & **Footer Completo**:
   - Respostas diretas sobre segurança, compatibilidade de extratos e cancelamento em 1 clique.

---

## 5. DESIGN SYSTEM & REQUISITOS DE UI/UX (ESTILO CLEAN & MODERNO)

- **Cores Tailwind**:
  - `slate-950` / `slate-900` para textos nobres e cabeçalhos.
  - `emerald-500` / `emerald-600` para destaque de superávit, botões primários e sucesso.
  - `rose-500` para alertas de limites e despesas críticas.
  - `amber-500` para avisos de aproximação do teto diário.
  - `slate-50` / `slate-100` para fundos limpos e cartões respirados.
- **Componentização & Micro-interações**:
  - Efeitos hover suaves (`transition-all duration-200 ease-in-out`).
  - Skeleton screens pulsantes durante o carregamento de dados.
  - Formatação de moeda nativa com `Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })`.

---

## 6. INSTRUÇÕES DE EXECUÇÃO PARA A IA (ANTIGRAVITY 2)
Ao executar este prompt:
1. Gere o código completo da **Landing Page (`Landing.vue`)** com todas as seções descritas, componentizada, responsiva e pronta para produção com Tailwind CSS.
2. Crie os arquivos de migration MySQL e models do Laravel 11 correspondentes.
3. Forneça o controller `LandingPageController.php` e as rotas no `web.php` integrando o ecossistema Inertia.js.
