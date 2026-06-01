# LADETEC Agenda

Sistema web de agendamento de salas com foco em uso interno da secretaria.

## Objetivo
- Registrar reservas de salas de forma simples e rápida.
- Evitar conflitos de horário.
- Garantir rastreabilidade (quem criou e quem editou).

## Perfis de acesso
- `admin`: gerencia salas e pode gerenciar agenda.
- `secretary`: gerencia agenda (criar/editar/excluir reservas).
- `user`: cria agendamento simples e consulta apenas os próprios agendamentos e histórico.

## Fluxo principal
1. Usuário faz login.
2. Sistema abre a tela de `Agendamentos`.
3. Usuário ou secretaria cria agendamento simples; recorrência fica para a secretaria.
4. Sistema valida conflito de horário na mesma sala/data.

## Stack
- PHP 8.2+
- Laravel 12
- Blade + Bootstrap
- Banco: SQLite (padrão local)

## Configuração local
1. Copie as variáveis de ambiente:
```bash
cp .env.example .env
```
2. Instale as dependências:
```bash
composer install
npm install
```
3. Gere a chave e rode as migrações:
```bash
php artisan key:generate
php artisan migrate
```
4. (Opcional) semear dados de exemplo:
```bash
php artisan db:seed
```
5. Inicie a aplicação:
```bash
composer run dev
```

## Acesso e segurança
- O registro público fica desabilitado por padrão:
```env
ALLOW_PUBLIC_REGISTRATION=false
```
- Senha padrão para seed inicial:
```env
DEFAULT_USER_PASSWORD=12345678
```
- Para habilitar cadastro aberto (não recomendado em ambiente interno):
```env
ALLOW_PUBLIC_REGISTRATION=true
```

## Usuários iniciais via seed
Ao rodar `php artisan db:seed`, o sistema cria/atualiza:
- `admin@meet.local` (role `admin`)
- `secretaria@meet.local` (role `secretary`)

Senha padrão: valor de `DEFAULT_USER_PASSWORD`.

## Comandos úteis
- Rodar testes:
```bash
php artisan test
```
- Definir papel de usuário:
```bash
php artisan user:role <user_id> admin
php artisan user:role <user_id> secretary
php artisan user:role <user_id> user
```

- Worker da fila (recomendado quando `EVOLUTION_WHATSAPP_QUEUE=true`):
```bash
php artisan queue:work
```

## Estrutura principal
- `app/Actions/Reservations`: casos de uso da agenda.
- `app/Http/Controllers`: camada HTTP.
- `app/Http/Requests`: validação e autorização de entrada.
- `app/Policies`: regras de permissão por perfil.
- `app/Services`: lógica reutilizável de disponibilidade e apoio ao domínio.
- `resources/views/reservations`: telas da agenda.

## WhatsApp (Evolution)
O sistema já pode enviar notificações de WhatsApp para o **titular** da reserva em:
- criação de agendamento
- atualização de agendamento
- cancelamento de agendamento
- criação, atualização e cancelamento de série recorrente

Configuração:
```env
EVOLUTION_WHATSAPP_ENABLED=true
EVOLUTION_WHATSAPP_QUEUE=true
EVOLUTION_WHATSAPP_BASE_URL=https://seu-endpoint-da-evolution
EVOLUTION_WHATSAPP_INSTANCE=SuaInstancia
EVOLUTION_WHATSAPP_API_KEY=sua-chave
EVOLUTION_WHATSAPP_TIMEOUT=10
```

Observações:
- `EVOLUTION_WHATSAPP_QUEUE=true`: usa fila e requer `php artisan queue:work`
- `EVOLUTION_WHATSAPP_QUEUE=false`: envia direto na requisição; útil para ambiente controlado/teste

## Estado atual
- Autenticação com Breeze e redirecionamento principal para a agenda.
- CRUD de salas para `admin`.
- Consulta de salas liberada para qualquer usuário autenticado.
- CRUD de usuários para `admin`, com controle de status ativo/inativo e papel.
- Agenda com listagem separada entre `Agendamentos` e `Histórico`.
- Item `Novo agendamento` destacado na sidebar para perfis que podem criar.
- Listagem principal usando Livewire PowerGrid com filtros, ordenação, seleção e exportação dos itens selecionados.
- Criação, edição e exclusão de reservas avulsas com validação de conflito.
- Criação simples liberada para `user`; criação recorrente restrita a `secretary` e `admin`.
- Bloqueio de alteração/exclusão de reservas que já terminaram.
- Reservas recorrentes com série, edição da ocorrência, edição desta e próximas, edição da série inteira e cancelamento da série.
- Separação entre criador da reserva e titular (`owner_user_id`), permitindo que a secretaria crie em nome de outra pessoa.
- Auditoria básica de criação e última edição do agendamento.
- Tela `Disponibilidade` com consulta por data e sala.
- Modo `Todas` na disponibilidade priorizando leitura textual por sala, com ordenação por status (`Livre`, `Parcialmente ocupada`, `Ocupada`).
- Modo de sala específica com resumo principal de horários livres e ocupados.
- Tabela operacional do dia preservada abaixo da disponibilidade para apoio da secretaria.
- Identidade visual ajustada para o padrão institucional do LADETEC.
- Suíte automatizada atual com `87` testes passando.

## Próximos passos sugeridos
- Revisar visualmente os fluxos principais em uso real da secretaria.
- Avaliar se a janela consultiva da disponibilidade (`08:00` às `18:00`) deve virar configuração de sistema.
- Melhorar relatórios e formatos de exportação.
- Avaliar QR Code para auto-reserva de usuários como fase futura.
