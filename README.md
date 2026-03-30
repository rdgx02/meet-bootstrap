# Meet LADETEC

Sistema web de agendamento de salas com foco em uso interno da secretaria.

## Objetivo
- Registrar reservas de salas de forma simples e rapida.
- Evitar conflitos de horario.
- Garantir rastreabilidade (quem criou e quem editou).

## Perfis de acesso
- `admin`: gerencia salas e pode gerenciar agenda.
- `secretary`: gerencia agenda (criar/editar/excluir reservas).
- `user`: consulta agenda (sem criar reservas, no modelo atual).

## Fluxo principal
1. Usuario faz login.
2. Sistema abre a tela de `Agendamentos`.
3. Secretaria filtra/busca e cria ou edita reservas.
4. Sistema valida conflito de horario na mesma sala/data.

## Stack
- PHP 8.2+
- Laravel 12
- Blade + Tailwind (Breeze)
- Banco: SQLite (padrao local)

## Configuracao local
1. Copie variaveis de ambiente:
```bash
cp .env.example .env
```
2. Instale dependencias:
```bash
composer install
npm install
```
3. Gere chave e rode migracoes:
```bash
php artisan key:generate
php artisan migrate
```
4. (Opcional) semear dados de exemplo:
```bash
php artisan db:seed
```
5. Inicie aplicacao:
```bash
composer run dev
```

## Acesso e seguranca
- Registro publico fica desabilitado por padrao:
```env
ALLOW_PUBLIC_REGISTRATION=false
```
- Senha padrao para seed inicial:
```env
DEFAULT_USER_PASSWORD=12345678
```
- Para habilitar cadastro aberto (nao recomendado em ambiente interno):
```env
ALLOW_PUBLIC_REGISTRATION=true
```

## Usuarios iniciais via seed
Ao rodar `php artisan db:seed`, o sistema cria/atualiza:
- `admin@meet.local` (role `admin`)
- `secretaria@meet.local` (role `secretary`)

Senha padrao: valor de `DEFAULT_USER_PASSWORD`.

## Comandos uteis
- Rodar testes:
```bash
php artisan test
```
- Definir papel de usuario:
```bash
php artisan user:role <user_id> admin
php artisan user:role <user_id> secretary
php artisan user:role <user_id> user
```

## Estrutura principal
- `app/Actions/Reservations`: casos de uso da agenda.
- `app/Http/Controllers`: camada HTTP.
- `app/Http/Requests`: validacao e autorizacao de entrada.
- `app/Policies`: regras de permissao por perfil.
- `resources/views/reservations`: telas da agenda.

## Estado atual
- Autenticacao com Breeze e redirecionamento principal para a agenda.
- CRUD de salas para `admin`.
- CRUD de usuarios para `admin`, com controle de status ativo/inativo e papel.
- Agenda com listagem separada entre `Agendamentos` e `Historico`.
- Listagem principal usando Livewire PowerGrid com filtros, ordenacao, selecao e exportacao dos itens selecionados.
- Criacao, edicao e exclusao de reservas avulsas com validacao de conflito.
- Bloqueio de alteracao/exclusao de reservas que ja terminaram.
- Reservas recorrentes com serie, edicao da ocorrencia, edicao desta e proximas, edicao da serie inteira e cancelamento da serie.
- Auditoria basica de criacao e ultima edicao do agendamento.

## Proximos passos sugeridos
- Reforcar testes de exportacao, exclusao em lote e comportamento fino da listagem PowerGrid.
- Revisar a UX das telas de detalhes e confirmacoes de exclusao/cancelamento.
- Melhorar relatorios e formatos de exportacao.
- Avaliar QR Code para auto-reserva de usuarios como fase futura.
