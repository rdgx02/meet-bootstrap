# Análise de MVP — Meet LADETEC

> Análise original em **2026-06-01** (82/100). Revisada em **2026-06-09** e **2026-06-12** ao longo de
> melhorias sucessivas: **82 → 90 → 93 → 94 → 96**. Referência de "100%" = **MVP funcional pronto para
> entregar** a uma secretaria interna (não produção enterprise). Avaliação subjetiva, baseada em leitura
> do código e execução da suíte (**104 testes, 376 asserções**).

## Nota geral: 96/100 (para MVP) — antes: 94/100 (+2)

**Todos os itens funcionais do MVP foram resolvidos.** O **+2 para 96** vem de tirar o **WhatsApp
síncrono** do escopo: ele foi **movido para a fase de integração com o ERP** que já existe — naquele
ambiente o worker de fila provavelmente já existe, tornando o envio em segundo plano trivial; resolver
agora seria construir infraestrutura que o ERP já oferece. Não é mais critério deste MVP. O que sobra
é só polish — tudo 🟢.

**Justificativa (+2):** com o WhatsApp síncrono **fora do escopo** (adiado, não pendente — ver "Fora do
escopo do MVP / Adiado"), **não resta nenhuma lacuna funcional**. Os **4 pontos** que faltam para 100
refletem **polish de UI/UX** (em andamento) e a **falta de validação em produção com usuários reais**.
Continua um MVP entregável **hoje**.

| Critério | Avaliação atual |
|---|---|
| Funcionalidades do MVP | Completas para o MVP (booking simples + recorrente + conflito + papéis + disponibilidade + export + WhatsApp), com janela de expediente validada no backend; resta só polish de UI/UX |
| Arquitetura / qualidade | Forte — separação Controller→Request→Action→Service→Model real e consistente |
| Testes | Boa cobertura do domínio crítico (**104 testes**); reforçada nos pontos sensíveis (conflito, "following", arquivar sala, expediente, intervalo de recorrência) e **rodando em CI** |
| Segurança | Adequada para uso interno (policies, rate limit, escaping, sem mass-assignment) + hardening de deploy |
| Performance | Sem problemas no volume esperado (uma secretaria) |
| Bugs/riscos concretos | Os dois riscos concretos da v1 (perda de dados e double-booking) foram eliminados |

---

## O que foi RESOLVIDO

### ✅ 1. Exclusão destrutiva de sala → arquivamento — `ffe8f10`  (era 🟡, o pior risco prático)
O `RoomController::destroy` fazia hard delete com `cascadeOnDelete`, apagando silenciosamente todas
as reservas (futuras e histórico) de uma sala. Agora a ação **arquiva** (`is_active=false`), há um
botão **Reativar**, o histórico é preservado e a sala arquivada some das telas de novo agendamento.
Era o único ponto de perda de dados irreversível — eliminado.

### ✅ 2. Conflito centralizado + bug de double-booking corrigido — `9b706b0`  (era 🟡 + bug oculto)
A regra de overlap reescrita inline e a formatação copiada em 3 Actions foram unificadas no
`ReservationConflictService` (agora aceita ignorar um *conjunto* de ids + `describeOccurrenceConflict`).
No processo descobriu-se que `UpdateReservationFollowingAction` **deixava passar um conflito real**
quando a primeira sobreposição era uma ocorrência a ser substituída — ou seja, a edição "esta e
próximas" podia gerar **agendamento duplo silencioso**. Corrigido (exclusão no SQL via `whereNotIn`)
e **provado por teste** (red→green): `test_following_update_detects_conflict_hidden_behind_replaced_occurrence`.

### ✅ 3. Hardening de deploy — `6bd7322`  (era 🟡 no momento da entrega)
A senha-padrão fraca (`12345678`) deixou de ter fallback no seeder (falha de propósito sem
`DEFAULT_USER_PASSWORD`); `.env.example` ganhou chaves de produção (`SESSION_SECURE_COOKIE` etc.) e
o README ganhou uma seção "Deploy seguro" (APP_DEBUG=false, APP_ENV=production, etc.).

### ✅ 4. Código morto removido — `246d2c8` (AdminMiddleware, Unit/ExampleTest, er_id) + `b0b7fc6` (coluna `contact`)  (era 🟢)
Removidos: `AdminMiddleware` órfão (+ alias), `tests/Unit/ExampleTest.php` boilerplate, arquivo
`er_id` (dump de Tinker) e a coluna `contact` das duas tabelas (migration reversível). O
`tests/Feature/ExampleTest.php` foi **mantido de propósito** — não é boilerplate, cobre o redirect de `/`.

### ✅ 5. Validação de janela de expediente no backend — `6773d97`  (era 🟢, mas lacuna funcional real)
O backend aceitava **qualquer** horário (o limite 08–18h existia só no front). Regra
`WithinBusinessHours` (config `reservations.business_hours`, env `RESERVATION_OPENING_TIME`/
`CLOSING_TIME`) aplicada em **todos** os caminhos de escrita (criar single/recorrente, editar
ocorrência/following/all e edição direta da série); UI de disponibilidade alinhada ao mesmo config.
Coberta por `BusinessHoursValidationTest` (red→green: rejeita 07:00 e 19:00, aceita 08:00–18:00,
inclui série recorrente).

### ✅ 6. CI (GitHub Actions) — `62eee7c` (workflow) + `87f6dc1` (hermeticidade)  (era 🟢)
Workflow roda a suíte em **push e pull_request na `main`** (PHP 8.3, SQLite `:memory:`, build do Vite
— necessário pro manifest exigido por `@vite` nas views —, smoke `php artisan about`, Pint como passo
**informativo**). O primeiro run revelou 5 testes de WhatsApp **não-herméticos**: sobrescreviam só
`enabled`/`queue` via `config()`, mas dependiam das credenciais reais do `.env` local — em ambiente
limpo (CI) o gate `EvolutionWhatsAppService::enabled()` retornava `false` e o job não era enfileirado.
Corrigido com o helper `fakeEvolutionWhatsApp()` na base `TestCase` (seta também `base_url`/`instance`/
`api_key` fake) → CI verde, **98 testes**.

### ✅ 7. `conflict_mode` (código morto) removido — `5e4425e`  (era 🟢)
O modo de conflito das séries era fixo em `'strict'` e o caminho alternativo ("criar válidas e pular
conflitantes") nunca foi exposto na UI nem executava — **código morto**. Removidos a variável e a
condição morta em `CreateRecurringReservationSeriesAction`, a cópia inerte em
`UpdateReservationFollowingAction` e o campo do `$fillable` em `ReservationSeries`; a coluna
`conflict_mode` foi **dropada** de `reservation_series` via migration reversível (`down()` recria
simétrico). **Comportamento strict preservado** e suíte **98 verde**.

### ✅ 8. `interval` real ("a cada N semanas") — `b85832b` (+ dropdown `d34b63a`)  (era 🟢, feature incompleta)
O gerador de ocorrências **passa a ler o `interval`** (antes inerte, fixo em 1). A recorrência
**semanal** aceita intervalo de **1 a 4 semanas**, ancorado na **primeira ocorrência gerada** — o que
corrige o início no meio da semana (uma série quinzenal começando numa quarta não perde a primeira
segunda). Validação `nullable|integer|min:1|max:4` (PT-BR) nos FormRequests de criação e edição de
série; `following`/`all` herdam a cadência na regeneração. **Diária e mensal seguem fixas em 1.**
Conflito e expediente continuam valendo por ocorrência. **Sem migration** (a coluna já existia).
Coberto por `RecurringIntervalTest` (red→green: datas de 2 em 2 na criação e na regeneração, início no
meio da semana ancorado na 1ª ocorrência, interval inválido rejeitado, série anual sem truncamento) →
suíte **104 verde**. **UX (`d34b63a`):** o campo virou um **dropdown em linguagem humana** ("Toda
semana / A cada N semanas") em vez de input numérico — o caso comum ("toda semana") fica óbvio.

> Observação: a suíte saiu de 87 para **104 testes**, com a cobertura nova concentrada nos pontos
> críticos (service de conflito, o bug do "following", arquivar/reativar sala, janela de expediente,
> intervalo de recorrência) e agora **executada automaticamente em CI** — não foi volume vazio.

---

## O que está BOM (mantém-se forte)

**Arquitetura limpa e consistente, de verdade.** O fluxo `Controller → FormRequest → Action → Service → Model`
é seguido à risca; controllers finos delegam para `app/Actions/Reservations` e `app/Services`.

**Detecção de conflito à prova de corrida e agora centralizada.** `ReservationConflictService` é a
fonte única da verdade para sobreposição, chamado com `lockForUpdate: true` dentro de
`DB::transaction` (`CreateReservationAction.php:23`, `UpdateReservationAction.php:18`). Após a v1,
**todos** os caminhos de série usam o service (sem mais SQL inline).

**Autorização coerente.** Policies + `authorize()` nos FormRequests; `ReservationPolicy` bloqueia
editar/excluir reserva já terminada; `scopeVisibleTo` garante que usuário comum só vê o que é dele —
inclusive na grade PowerGrid (`ReservationsTable.php:299`), não só na policy de detalhe.

**Segurança de base:** XSS escapado com `e()`; login barra inativo + rate limit 5 + `session()->regenerate()`;
`owner_user_id` forçado server-side; `.env` fora do git; registro público fechado por `abort_unless`.

**Recorrência com teto** (12 meses) e WhatsApp best-effort (falha logada, não propagada).

---

## O que ainda desconta (polish — tudo 🟢, nada funcional)

O **MVP funcional está completo**: não há lacuna de domínio em aberto. Os **4 pontos** que faltam para
100 são de acabamento, não de funcionalidade:

- **Polish de UI/UX — em andamento.** Refinamento visual e de microcopy (ex.: o dropdown de intervalo
  em linguagem humana, `d34b63a`). É o trabalho atual; não envolve mais código de domínio.
- **Validação em produção com usuários reais.** O sistema ainda não rodou em produção com a
  secretaria/laboratórios usando de fato — parte da nota só se ganha com esse uso real.

---

## Fora do escopo do MVP / Adiado

### WhatsApp síncrono → integração com o ERP
O envio de WhatsApp **funciona hoje** (as mensagens chegam), mas é **síncrono**: com
`EVOLUTION_WHATSAPP_QUEUE=false` o POST à Evolution API acontece **dentro do request**
(`ReservationWhatsAppNotificationService.php:194`, timeout 10s, best-effort). O **desacoplamento**
(fila + worker) foi **adiado para a fase de integração com o ERP** que já existe — naquele ambiente o
worker de fila provavelmente já roda, tornando o envio em segundo plano trivial; construir essa infra
agora seria refazer o que o ERP já oferece. **Não é mais critério deste MVP** (não desconta nota).

- **Diagnóstico (já levantado):** o lado da **aplicação já está pronto** — `SendWhatsAppMessageJob`
  (com `tries=3`/backoff/log de falha), `QUEUE_CONNECTION=database` e a tabela `jobs` (migration
  `0001_01_01_000002`) já existem. Falta apenas o **operacional**: virar a flag
  `EVOLUTION_WHATSAPP_QUEUE=true` e manter um **worker persistente** no servidor
  (`php artisan queue:work` via systemd/supervisor). Sem o worker, virar a flag sozinha deixaria os
  jobs parados na tabela — por isso o item depende do ambiente do ERP, não de código novo aqui.

---

## Por que 96 e não mais — e não menos

- **Não mais:** os **4 pontos** que faltam são **polish de UI/UX** (em andamento) e **validação em
  produção com usuários reais** — coisas que se ganham com refinamento visual e uso real, não com mais
  código de domínio. O **WhatsApp síncrono saiu do escopo** (adiado para a integração com o ERP), então
  não desconta mais.
- **Não menos:** o núcleo já era forte, os **riscos concretos** (perda de dados na exclusão de sala;
  double-booking no "following") foram **eliminados**, **todas as lacunas funcionais** (expediente,
  `interval`) foram fechadas e o **CI** protege contra regressões.

## Resumo em uma linha

**De 82 → 90 → 93 → 94 → 96:** riscos reais eliminados, casa limpa, todas as lacunas funcionais
fechadas (expediente, `interval`) e CI verde; o WhatsApp síncrono foi **adiado para a integração com o
ERP** (fora do escopo). O que falta para 100 é só **polish de UI/UX** e **validação em produção** —
nada funcional, nada bloqueante.
