# PROMPT MESTRE: MÓDULO NATIVO DE CAPTURA OCR, LEITURA DE NOTAS/CUPONS FISCAIS E CONCILIAÇÃO BANCÁRIA INTELIGENTE
## SISTEMA SAAS: "DEIXASOBRAR" (LARAVEL 11 + VUE.JS 3 INERTIA + MYSQL 8 + TAILWIND CSS)

---

## 1. ESCOPO & PROPÓSITO DO MÓDULO

Você é um Arquiteto de Software Sênior e Especialista em Visão Computacional / Engenharia de Software Fullstack.
Sua missão é desenvolver o **Módulo Nativo de Captura por Imagem, Leitura de Cupom Fiscal/NFC-e e Conciliação Bancária Automática** diretamente dentro do ecossistema do SaaS **DeixaSobrar**.

### O Conceito Central: "Tudo em um Só Lugar (Zero Apps Terceiros)"
O usuário **NÃO** utilizará bots externos de WhatsApp ou Telegram. Toda a interação ocorre **100% dentro do aplicativo web/mobile (PWA) do DeixaSobrar**, garantindo que:
1. Os dados sensíveis fiquem centralizados sob a governança e segurança do SaaS.
2. A câmera do celular/computador seja acionada de forma nativa e instantânea no navegador.
3. O fluxo de conciliação entre o papel/comprovante físico e o extrato bancário importado aconteça em tempo real com auditoria visual lado a lado.

---

## 2. PILARES DA ARQUITETURA DE CAPTURA NATIVA

O módulo opera em **3 Vias de Entrada Inteligentes**, todas executadas na interface web do usuário:

```
┌───────────────────────────────────────────────────────────────────────────┐
│                 CENTRAL DE CAPTURA NATIVA (DEIXASOBRAR)                   │
├──────────────────────────┬───────────────────────┬────────────────────────┤
│ VIA 1: SCANNER QR-CODE   │ VIA 2: OCR MULTIMODAL │ VIA 3: UPLOAD DIRETO   │
│ (NFC-e de Mercado/Farm.) │ (Cupom Térmico / Pix) │ (PDF / Print / Galeria)│
└────────────┬─────────────┴───────────┬───────────┴───────────┬────────────┘
             │                         │                       │
             ▼                         ▼                       ▼
    [Extrator SEFAZ]          [Pipeline Vision AI]      [Parser Estruturado]
             │                         │                       │
             └─────────────────────────┼───────────────────────┘
                                       ▼
                     [NORMALIZAÇÃO E PRÉ-LANÇAMENTO]
                                       │
                                       ▼
                   [MOTOR DE CONCILIAÇÃO BANCÁRIA CRUZADA]
                 (Casamento Inteligente com Extrato / Cartão)
```

### Via 1: Scanner Nativo de QR Code da NFC-e (Cupom de Supermercado)
* **Mecanismo**: Leitor de câmera via WebRTC (`html5-qrcode` ou `@zxing/library`).
* **Funcionamento**: Ao apontar a câmera para o QR Code de um cupom fiscal de supermercado, restaurante ou farmácia, o frontend captura a URL oficial da SEFAZ estadual.
* **Backend Processing**: Um Service no Laravel lê os metadados oficiais:
  - Razão social e CNPJ do estabelecimento.
  - Data, hora e forma de pagamento original (Cartão de Crédito, Débito, Pix, Dinheiro).
  - Listagem completa de itens da compra com quantidade e valor unitário.
* **Raio-X de Carrinho**: Classificação automática entre *Alimentação Essencial*, *Higiene/Limpeza* e *Supérfluos/Guloseimas*.

### Via 2: OCR Multimodal & Visão Computacional (Comprovante de Maquininha e Pix Físico)
* **Mecanismo**: Captura fotográfica de papéis térmicos amarelos/brancos ou comprovantes impressos.
* **Pipeline de IA**: Processamento via Gemini 1.5 Flash Vision / OpenAI Vision API via Function Calling estrito:
  - Extração de: Estabelecimento, Data, Hora, Valor Total, Bandeira do Cartão e 4 últimos dígitos (se houver).
  - Conversão direta em JSON tipado sem alucinações.

### Via 3: Motor de Conciliação Bancária Cruzada ("Anti-Duplicação")
* **O Problema Resolvido**: Evitar que a despesa apareça duas vezes (uma quando o usuário tira a foto do comprovante e outra quando importa o extrato do banco ou a fatura do cartão).
* **A Solução Algorítmica**:
  1. A foto cria um registro com status `aguardando_extrato` (ou `pre_conciliado`).
  2. Ao importar o extrato bancário (CSV/OFX), o algoritmo executa uma busca por *Fuzzy Matching*:
     $$\text{Score} = f(\Delta \text{Valor} == 0, |\text{Data}_{\text{nota}} - \text{Data}_{\text{extrato}}| \le 3 \text{ dias}, \text{Similaridade}(\text{Favorecido}))$$
  3. Se houver correspondência, o sistema unifica o comprovante à linha do extrato em 1 clique, anexando a foto como prova e enriquecendo a descrição.

---

## 3. MODELAGEM DO BANCO DE DADOS MYSQL (MIGRATIONS)

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Tabela de Comprovantes e Notas Escaneadas
        Schema::create('receipt_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete(); // Vínculo após conciliação
            $table->string('scan_type', 30); // 'nfce_qrcode', 'paper_ocr', 'pix_receipt', 'manual_photo'
            $table->string('image_path', 255);
            $table->string('merchant_name', 150)->nullable();
            $table->string('merchant_tax_id', 20)->nullable(); // CNPJ
            $table->dateTime('purchased_at')->nullable();
            $table->decimal('total_amount', 14, 2);
            $table->string('payment_method_detected', 50)->nullable(); // 'credit', 'debit', 'pix', 'cash'
            $table->string('card_last_digits', 4)->nullable();
            $table->json('raw_ocr_payload')->nullable(); // Resposta bruta da IA / SEFAZ
            $table->string('match_status', 30)->default('pending'); // 'pending', 'matched', 'manual_created', 'ignored'
            $table->timestamps();

            $table->index(['user_id', 'match_status']);
            $table->index(['user_id', 'purchased_at']);
        });

        // Tabela de Itens Detalhados do Cupom (Raio-X do Carrinho de Compras)
        Schema::create('receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_scan_id')->constrained()->onDelete('cascade');
            $table->string('item_name', 200);
            $table->decimal('quantity', 8, 3)->default(1.000);
            $table->string('unit', 10)->default('UN'); // UN, KG, LT
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->string('item_category', 50)->default('alimentacao_essencial'); // essencial, limpeza, supérfluo, bebidas, etc.
            $table->timestamps();

            $table->index(['receipt_scan_id', 'item_category']);
        });

        // Tabela de Despesas e Cobranças Recorrentes Detectadas
        Schema::create('recurring_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title', 120); // Ex: 'Internet Vivo', 'Servidor Hostinger', 'Google AI'
            $table->decimal('expected_amount', 12, 2);
            $table->unsignedTinyInteger('due_day'); // Dia do vencimento (1 a 31)
            $table->string('frequency', 20)->default('monthly'); // monthly, annual, weekly
            $table->string('payment_channel', 50)->nullable(); // Cartão Nubank, Débito Sicredi, Boleto
            $table->boolean('is_active')->default(true);
            $table->date('last_detected_date')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'due_day']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('recurring_bills');
        Schema::dropIfExists('receipt_items');
        Schema::dropIfExists('receipt_scans');
    }
};
```

---

## 4. DESIGN PATTERNS & IMPLEMENTAÇÃO BACKEND (LARAVEL 11)

### Action 1: `ProcessReceiptImageAction.php`
Responsável pelo pipeline de tratamento e chamada de visão computacional:
- Converte a imagem enviada para formato WebP otimizado (reduzindo consumo de banda e armazenamento em S3/Local).
- Tenta decodificar QR Code de NFC-e. Se houver, aciona o `NfceScraperService`.
- Se não for QR Code, invoca o `VisionOcrService` com schema estrito JSON:
```json
{
  "merchant": "string",
  "cnpj": "string",
  "date_time": "YYYY-MM-DD HH:MM:SS",
  "total_amount": 0.00,
  "payment_method": "credit | debit | pix | cash",
  "card_last_digits": "string",
  "items": [
    {
      "name": "string",
      "qty": 1.0,
      "price": 0.00,
      "is_essential": true
    }
  ]
}
```

### Action 2: `ReconcileReceiptWithStatementAction.php`
Executada tanto no momento do escaneamento quanto no momento da importação de extratos:
- Varre transações bancárias não conciliadas.
- Verifica tolerância de centavos e intervalo de data ($\pm 3$ dias úteis).
- Emite notificação na tela para confirmação do usuário: *"Encontramos o débito correspondente de R$ 145,00 no Cartão Nubank. Vincular?"*.

---

## 5. DESIGN SYSTEM & COMPONENTES FRONTEND (VUE 3 + TAILWIND CSS)

### Componente 1: `NativeScannerModal.vue`
- Modal responsivo com visor de câmera centralizado (moldura retangular com guias e scanner animado a laser verde `emerald-500`).
- Alternador instantâneo:
  - **Botão "Câmera Ao Vivo"**: Ativa a câmera traseira do celular via WebRTC com botão disparador grande no rodapé.
  - **Botão "Galeria / Arquivo"**: Permite selecionar foto pré-existente ou PDF.
- Feedback em tempo real com indicador de processamento: *"Lendo cupom fiscal e identificando valores..."*.

### Componente 2: `ReceiptReviewCard.vue`
- Tela de conferência pós-leitura (Human-in-the-loop):
  - Exibe a foto do cupom em miniatura clicável (com zoom).
  - Campos pré-preenchidos e editáveis: Valor Total, Estabelecimento, Data e Categoria Sugerida.
  - Tabela retrátil com a lista de itens comprados no mercado.
  - Botão de confirmação primário: `[Confirmar e Salvar Lançamento]`.

### Componente 3: `ReconciliationMatchCard.vue`
- Interface de conciliação lado a lado:
  - Coluna Esquerda: Dados extraídos do Comprovante Físico (Foto, R$ 260,00, Data 12/04).
  - Coluna Direita: Linha encontrada no Extrato Bancário (Débito Sicredi, R$ 260,00, Data 12/04).
  - Botão de Ação: `[Conciliar Sem Duplicar]`.

### Componente 4: `RecurringBillsManager.vue`
- Hub de Contas e Assinaturas Recorrentes:
  - Lista de cobranças previsíveis do mês com badge de status (Pendente / Pago).
  - Alerta de variação: Destaca se uma conta recorrente veio mais cara que a média histórica.

---

## 6. INSTRUÇÕES DE EXECUÇÃO PARA O ANTIGRAVITY 2

Ao processar este prompt:
1. Comece criando as migrations MySQL (`receipt_scans`, `receipt_items`, `recurring_bills`) e os respectivos Models Eloquent com relacionamentos e casts.
2. Implemente o Service `VisionOcrService.php` com suporte a prompt multimodal estruturado.
3. Desenvolva o componente Vue 3 `NativeScannerModal.vue` com acionamento nativo da câmera via HTML5/WebRTC, tratamento de permissões e preview de imagem.
4. Integre a lógica de conciliação automática com a tabela de transações existente para garantir duplicidade zero.
