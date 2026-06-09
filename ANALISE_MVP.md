# Análise de MVP — Meet LADETEC

> Análise original em **2026-06-01** (82/100). Revisada em **2026-06-09** ao longo de melhorias
> sucessivas: **82 → 90 → 93**. Referência de "100%" = **MVP funcional pronto para entregar** a uma
> secretaria interna (não produção enterprise). Avaliação subjetiva, baseada em leitura do código e
> execução da suíte (**98 testes, 341 asserções**).

## Nota geral: 93/100 (para MVP) — antes: 90/100 (+3)

A nota subiu porque os dois pontos que o próprio documento citava como barreira aos 95 foram
fechados: a **validação de janela de expediente** (lacuna funcional real num sistema de reserva) e o
**CI** (que agora guarda regressões). O que sobra é polish e feature incompleta — tudo 🟢.

**Justificativa (+3):** validar o expediente fechou a última lacuna funcional real e o CI verde passa
a guardar regressões; o que ainda segura em 93 é só `conflict_mode` meio-feito, `interval` não
suportado e o acoplamento síncrono do WhatsApp. Continua um MVP entregável **hoje**, com menos risco.

| Critério | Avaliação atual |
|---|---|
| Funcionalidades do MVP | Praticamente completas (booking simples + recorrente + conflito + papéis + disponibilidade + export + WhatsApp), agora com janela de expediente validada no backend |
| Arquitetura / qualidade | Forte — separação Controller→Request→Action→Service→Model real e consistente |
| Testes | Boa cobertura do domínio crítico (**98 testes**); reforçada nos pontos sensíveis (conflito, "following", arquivar sala, expediente) e **rodando em CI** |
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

> Observação: a suíte saiu de 87 para **98 testes**, com a cobertura nova concentrada nos pontos
> críticos (service de conflito, o bug do "following", arquivar/reativar sala, janela de expediente)
> e agora **executada automaticamente em CI** — não foi volume vazio.

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

## O que AINDA falta (tudo 🟢 — nenhum bloqueante)

### 1. `conflict_mode` é código morto (feature pela metade)
- `CreateRecurringReservationSeriesAction` fixa `$conflictMode = 'strict'` (`:22`), então o caminho
  "criar válidas e pular conflitantes" nunca roda; a coluna `conflict_mode` só guarda `'strict'`.
- **Onde:** `CreateRecurringReservationSeriesAction.php:22,40`. **Gravidade:** 🟢. **Esforço:** baixo (remover) / médio (expor "lenient" na UI).

### 2. `interval` sempre 1
- A coluna `interval` existe nas séries mas é sempre gravada como `1` — não há "a cada 2 semanas".
- **Onde:** Actions de série (`'interval' => 1`). **Gravidade:** 🟢. **Esforço:** médio.

### 3. WhatsApp síncrono quando `queue=false`
- Com `EVOLUTION_WHATSAPP_QUEUE=false`, o envio acontece dentro do request; uma chamada HTTP lenta
  atrasa a resposta. Mitigado por timeout e por ser best-effort; o default `queue=true` exige `queue:work`.
- **Onde:** `ReservationWhatsAppNotificationService.php:194`. **Gravidade:** 🟢. **Esforço:** baixo (manter `queue=true`) / médio (desacoplar).
- **Nota do diagnóstico (sessão de CI):** o gargalo real não é só a flag `queue`. O gate
  `EvolutionWhatsAppService::enabled()` exige `base_url`/`instance`/`api_key` preenchidos; quando
  `queue=false` (como está no `.env` de produção/local), `dispatch()` faz um **POST síncrono à
  Evolution API dentro do request HTTP** (timeout 10s, best-effort em try/catch) — ou seja, **a
  latência do request fica acoplada a um serviço externo** (até ~10s se a API travar). Resolver o
  item = **desacoplar o envio** (sempre enfileirar, ou circuit-breaker/timeout curto), não só mexer
  na flag. Esse mesmo gate `enabled()` foi o que expôs a não-hermeticidade dos testes corrigida em `87f6dc1`.

---

## Por que 93 e não mais — e não menos

- **Não mais:** o `conflict_mode` pela metade é ambiguidade no domínio mais complexo do app, falta
  `interval` real, e o WhatsApp síncrono acopla a latência do request a um serviço externo. Somados,
  valem os ~7 pontos restantes. 95+ exigiria fechar essas pontas.
- **Não menos:** o núcleo já era forte, os **riscos concretos** (perda de dados na exclusão de sala;
  double-booking no "following") foram **eliminados**, a última lacuna funcional real (validação de
  expediente) foi fechada, e o **CI** agora protege contra regressões.

## Resumo em uma linha

**De 82 → 90 → 93:** riscos reais eliminados, casa limpa, expediente validado no backend e CI verde;
o que falta é só polish (`conflict_mode`, `interval`, desacoplar o WhatsApp síncrono) — nada bloqueante.
