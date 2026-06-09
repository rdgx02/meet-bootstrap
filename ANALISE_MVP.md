# Análise de MVP — Meet LADETEC

> Análise original em **2026-06-01** (nota 82/100). **Atualizada em 2026-06-09** após uma rodada de
> melhorias. Referência de "100%" = **MVP funcional pronto para entregar** a uma secretaria interna
> (não produção enterprise). Avaliação subjetiva, baseada em leitura do código e execução da suíte
> (92 testes, 321 asserções).

## Nota geral: 90/100 (para MVP) — antes: 82/100 (+8)

A nota subiu porque os três pontos que realmente pesavam na análise anterior (todos 🟡) foram
resolvidos, **e** um bug de integridade de dados que nem estava no radar original foi encontrado e
corrigido. O que sobra hoje é majoritariamente 🟢 (nice-to-have).

Não chega a 95+ porque ainda há lacunas funcionais reais (ainda que aceitáveis para MVP) e dívida
de polimento — descritas honestamente abaixo. Continua sendo um MVP entregável **hoje**, agora com
menos risco.

| Critério | Avaliação atual |
|---|---|
| Funcionalidades do MVP | Praticamente completas (booking simples + recorrente + conflito + papéis + disponibilidade + export + WhatsApp) |
| Arquitetura / qualidade | Forte — separação Controller→Request→Action→Service→Model real e consistente |
| Testes | Boa cobertura do domínio crítico (92 testes); reforçada nos pontos sensíveis (conflito, "following", arquivar sala) |
| Segurança | Adequada para uso interno (policies, rate limit, escaping, sem mass-assignment) + hardening de deploy |
| Performance | Sem problemas no volume esperado (uma secretaria) |
| Bugs/riscos concretos | Os dois riscos concretos da v1 (perda de dados e double-booking) foram eliminados |

---

## O que foi RESOLVIDO desde a v1 (82 → 90)

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

> Observação: a suíte saiu de 87 para **92 testes**, com a cobertura nova concentrada nos pontos
> críticos (service de conflito, o bug do "following", arquivar/reativar sala) — não foi volume vazio.

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

### 1. Sem validação de janela de expediente
- A tela de Disponibilidade assume 08:00–18:00 (`AvailabilityController` hardcoded), mas a
  criação/edição aceita **qualquer** horário (ex.: 03:00). Os FormRequests só validam `end_time after start_time`.
- **Onde:** `app/Http/Requests/ReservationRequest.php`. **Gravidade:** 🟢. **Esforço:** baixo.

### 2. `conflict_mode` é código morto (feature pela metade)
- `CreateRecurringReservationSeriesAction` fixa `$conflictMode = 'strict'` (`:22`), então o caminho
  "criar válidas e pular conflitantes" nunca roda; a coluna `conflict_mode` só guarda `'strict'`.
- **Onde:** `CreateRecurringReservationSeriesAction.php:22,40`. **Gravidade:** 🟢. **Esforço:** baixo (remover) / médio (expor "lenient" na UI).

### 3. `interval` sempre 1
- A coluna `interval` existe nas séries mas é sempre gravada como `1` — não há "a cada 2 semanas".
- **Onde:** Actions de série (`'interval' => 1`). **Gravidade:** 🟢. **Esforço:** médio.

### 4. WhatsApp síncrono quando `queue=false`
- Com `EVOLUTION_WHATSAPP_QUEUE=false`, o envio acontece dentro do request; uma chamada HTTP lenta
  atrasa a resposta. Mitigado por timeout e por ser best-effort; o default `queue=true` exige `queue:work`.
- **Onde:** `ReservationWhatsAppNotificationService.php:194`. **Gravidade:** 🟢. **Esforço:** baixo (manter `queue=true`).

### 5. Sem CI / sem smoke test em ambiente de produção
- A suíte roda localmente (92 verdes), mas não há pipeline automatizado nem teste end-to-end num
  ambiente parecido com produção. Não é exigido para MVP, mas é o que separaria 90 de 95+.
- **Gravidade:** 🟢. **Esforço:** baixo/médio.

---

## Por que 90 e não mais — e não menos

- **Não mais:** a ausência de validação de horário é uma lacuna funcional real num sistema de
  reserva de salas (não só estética), e o `conflict_mode` pela metade é ambiguidade no domínio mais
  complexo do app. Somados, valem os ~10 pontos restantes. 95+ exigiria fechar essas pontas + CI.
- **Não menos:** o núcleo já era forte a 82, e os **riscos concretos** (perda de dados na exclusão
  de sala; double-booking no "following") foram **eliminados** — não contornados.

## Resumo em uma linha

**De 82 → 90:** os riscos reais foram fechados (perda de dados e double-booking) e a casa foi limpa;
o que falta é só polimento de MVP (validar expediente, decidir o `conflict_mode`, CI) — nada bloqueante.
