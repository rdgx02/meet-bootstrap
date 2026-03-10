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

## Roadmap
- Painel de salas com CRUD completo para admin.
- QR Code para auto-reserva de usuarios (fase futura).
- Melhorias de relatorios e exportacao.
