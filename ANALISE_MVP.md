# Análise de MVP — Meet LADETEC

> Análise feita em 2026-06-01. Referência de "100%" = **MVP funcional pronto para entregar** a uma secretaria interna (não produção enterprise). Avaliação subjetiva, baseada em leitura do código e execução da suíte de testes (87 passando, 301 asserções).

## Nota geral: 82/100 (para MVP)

A nota se apoia em seis critérios, com peso maior para "funciona e é seguro o suficiente para entregar":

| Critério | Avaliação |
|---|---|
| Funcionalidades do MVP | Praticamente completas (booking simples + recorrente + conflito + papéis + disponibilidade + export + WhatsApp) |
| Arquitetura / qualidade | Forte — separação Controller→Request→Action→Service→Model real e consistente |
| Testes | Boa cobertura do domínio crítico (87 testes, foco em reservas/séries) |
| Segurança | Adequada para uso interno (policies, rate limit, escaping, sem mass-assignment) |
| Performance | Sem problemas no volume esperado (uma secretaria) |
| Bugs/riscos concretos | Poucos; nenhum bloqueante, mas há arestas reais |

**O que subiria a nota:** remover o risco de cascade ao excluir sala, eliminar duplicação da lógica de conflito, validar horário de funcionamento na criação, limpar código morto. **O que desceria:** se o domínio de conflito não tivesse transação+lock (tem), ou se a listagem vazasse reservas entre usuários (não vaza).

Não há nenhum item 🔴 que eu classifique como bloqueio absoluto de entrega — por isso a nota está no início dos 80, e não nos 60. É um MVP entregável **hoje**, com ressalvas conhecidas.

---

## O que está BOM

**Arquitetura limpa e consistente, de verdade.** O fluxo `Controller → FormRequest → Action → Service → Model` é seguido à risca. Controllers são finos (ex.: `ReservationController::store` só orquestra e delega para `CreateReservationAction`/`CreateRecurringReservationSeriesAction`). Regras de negócio estão em `app/Actions/Reservations` e `app/Services`. Isso não é fachada — é real.

**Detecção de conflito à prova de corrida.** `ReservationConflictService::findConflict` é a fonte única da verdade para sobreposição (`start < fim_existente AND fim > início_existente`), e os Actions a chamam com `lockForUpdate: true` **dentro de `DB::transaction`** (`CreateReservationAction.php:23`, `UpdateReservationAction.php:18`). É o ponto mais bem-feito do projeto.

**Autorização coerente.** Tudo passa por Policies (`app/Policies/*`) + `authorize()` nos FormRequests, sem `if` espalhado em controller. `ReservationPolicy` ainda bloqueia editar/excluir reserva que já terminou (`hasReservationEnded`). `view`/`scopeVisibleTo` garantem que usuário comum só vê o que é dele.

**Visibilidade aplicada na listagem.** A `ReservationsTable` (PowerGrid) aplica `->visibleTo($user)` no datasource (`app/Livewire/ReservationsTable.php:299`) — não é só na policy de detalhe. Usuário comum não enxerga reservas de terceiros na grade.

**Segurança de base bem coberta para uso interno:**
- XSS: campos da grade usam `e()` (`fields()` em `ReservationsTable.php`).
- Login: usuário inativo é barrado (`LoginRequest.php:50`), rate limit de 5 tentativas, `session()->regenerate()`.
- Mass-assignment controlado; `owner_user_id` é **forçado server-side** para o próprio id quando o usuário não gerencia agenda (`ReservationRequest::prepareForValidation`).
- `.env` está no `.gitignore` e **não** está versionado (confirmado).
- Registro público fechado por padrão e protegido por `abort_unless` (`RegisteredUserController`).

**Testes no lugar certo.** 87 testes passando, concentrados no que importa: 25 em `ReservationManagementTest`, 15 em `ReservationSeriesManagementTest` cobrindo os três escopos (`occurrence`/`following`/`all`) de update e delete, além de conflito, visibilidade e notificações WhatsApp.

**Recorrência com teto.** Geração de ocorrências limitada a 12 meses (`StoreReservationRequest`/`UpdateReservationSeriesRequest`), evitando explosão de linhas. WhatsApp é best-effort (falha logada, não propagada) — decisão correta.

---

## O que está RUIM / faltando

### 1. Excluir sala apaga reservas futuras em cascata, sem aviso
- **Problema:** `room_id` tem `cascadeOnDelete` (`2026_02_15_033733_create_reservations_table.php`). `RoomController::destroy` faz `$room->delete()` direto — apaga **todas** as reservas da sala, inclusive futuras, **sem confirmação, sem checagem e sem notificar ninguém** por WhatsApp.
- **Onde:** `app/Http/Controllers/RoomController.php:56` + migration de reservas.
- **Gravidade:** 🟡 importante (só admin faz isso, mas é destrutivo e silencioso).
- **Esforço:** baixo (bloquear exclusão se houver reserva futura, ou inativar em vez de excluir).

### 2. Lógica de conflito duplicada (quebra o "fonte única da verdade")
- **Problema:** `UpdateReservationSeriesAction::execute` **reimplementa a query de conflito inline** (`->where('start_time','<',...)->where('end_time','>',...)`) em vez de usar `ReservationConflictService`. O mesmo bloco de `formatConflict` está copiado em 3 Actions de série.
- **Onde:** `app/Actions/Reservations/UpdateReservationSeriesAction.php:44-60`, `UpdateReservationFollowingAction.php`, `CreateRecurringReservationSeriesAction.php`.
- **Gravidade:** 🟡 importante (risco de divergência: se a regra de overlap mudar, um lugar fica para trás).
- **Esforço:** médio (extrair `findConflict` + um formatter compartilhado).

### 3. Sem validação de horário de funcionamento na criação
- **Problema:** A tela de disponibilidade assume expediente 08:00–18:00 (`AvailabilityController` hardcoded), mas a criação aceita **qualquer** horário/duração (ex.: 03:00 às 23:00). Há inconsistência entre o que o sistema "promete" e o que valida.
- **Onde:** `app/Http/Requests/ReservationRequest.php` (só valida `end_time after start_time`) vs `AvailabilityController.php:31`.
- **Gravidade:** 🟢 nice-to-have para MVP (mas é uma regra de negócio plausível que falta).
- **Esforço:** baixo.

### 4. "Pular conflitos" na recorrência é código morto / feature incompleta
- **Problema:** `CreateRecurringReservationSeriesAction` fixa `$conflictMode = 'strict'`; logo o caminho de "criar ocorrências válidas e ignorar conflitantes" (`$validOccurrences`, coluna `conflict_mode`) **nunca é exercido** — sempre lança exceção no primeiro lote de conflito. A coluna `conflict_mode` existe mas só guarda `'strict'`.
- **Onde:** `app/Actions/Reservations/CreateRecurringReservationSeriesAction.php:25-46`.
- **Gravidade:** 🟢 nice-to-have (ou é feature pela metade, ou código a remover).
- **Esforço:** baixo (remover) / médio (expor o modo "lenient" na UI).

### 5. Código morto / sobras
- **Problema:**
  - Coluna `contact` na tabela `reservations` (migration original) nunca foi usada nem removida — substituída por `phone` numa migration posterior. Não está em `$fillable`.
  - `AdminMiddleware` está registrado como alias `'admin'` em `bootstrap/app.php:16`, mas **nenhuma rota o usa** (rooms/users dependem de Policies). Middleware órfão.
  - `tests/Feature/ExampleTest.php` e `tests/Unit/ExampleTest.php` (boilerplate do Laravel) ainda presentes; arquivo `er_id` solto na raiz.
- **Onde:** vários (citados acima).
- **Gravidade:** 🟢 nice-to-have.
- **Esforço:** baixo.

### 6. Senha-padrão fraca e APP_DEBUG=true no exemplo
- **Problema:** `DEFAULT_USER_PASSWORD=12345678` e `APP_DEBUG=true` no `.env.example`. Para MVP interno é tolerável, mas o seed cria `admin@meet.local`/`secretaria@meet.local` com essa senha — se subir assim, são contas previsíveis.
- **Onde:** `.env.example`, `database/seeders/UserSeeder`.
- **Gravidade:** 🟡 importante apenas no momento do deploy (trocar senha + `APP_DEBUG=false`).
- **Esforço:** baixo (procedimento de entrega, não código).

### 7. Notificações WhatsApp síncronas quando `queue=false`
- **Problema:** Com `EVOLUTION_WHATSAPP_QUEUE=false`, o envio acontece dentro do request (controller, após a ação). Uma chamada HTTP lenta atrasa a resposta ao usuário. Mitigado por timeout e por ser best-effort, mas o default recomendado (`queue=true`) exige `queue:work` rodando — fácil de esquecer.
- **Onde:** `ReservationWhatsAppNotificationService` + `ReservationController::store`.
- **Gravidade:** 🟢 nice-to-have.
- **Esforço:** baixo (manter `queue=true` documentado/forçado).

---

## Top 5 prioridades para chegar no MVP

1. **Proteger exclusão de sala (item 1).** É o único ponto destrutivo silencioso. Bloquear delete com reservas futuras (ou inativar a sala) elimina perda de dados acidental. *Por quê primeiro: impacto irreversível, esforço baixo.*

2. **Procedimento de entrega: trocar senha-padrão e `APP_DEBUG=false` (item 6).** Não é código, é checklist de deploy — mas barato e necessário antes de qualquer entrega real. *Por quê: contas previsíveis num sistema multiusuário.*

3. **Unificar a lógica de conflito no `ReservationConflictService` (item 2).** Remove o maior risco de manutenção: hoje a regra de overlap vive em 4 lugares. *Por quê: a feature central não pode divergir entre caminhos.*

4. **Decidir o destino do modo de conflito da recorrência (item 4).** Ou remover o código morto, ou expor "ignorar conflitos" na UI. Deixar pela metade confunde quem mantém. *Por quê: clareza do domínio mais complexo do app.*

5. **Limpeza geral (itens 3 e 5).** Validar horário de funcionamento (opcional), remover `contact`, `AdminMiddleware` órfão, `ExampleTest`, `er_id`. *Por quê: baixo esforço, deixa o MVP apresentável e coerente.*

---

## Resumo em uma linha

**Falta pouco (~18%):** MVP sólido e entregável já, mas com uma exclusão de sala perigosa, lógica de conflito duplicada e algumas sobras a limpar — nada bloqueante.
