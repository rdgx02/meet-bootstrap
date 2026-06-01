# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**Meet LADETEC** — internal room-booking system for a secretary's office (LADETEC). Laravel 12 + Blade + Bootstrap + Livewire PowerGrid, SQLite by default. The codebase, comments, UI, and commit messages are in **Brazilian Portuguese** — match that language when writing user-facing strings, comments, and validation messages.

## Commands

```bash
composer run dev          # Start everything: php serve + queue:listen + pail logs + vite (concurrently)
composer run test         # Clears config then runs the full suite (php artisan test)
php artisan test                                   # Run all tests
php artisan test --filter ReservationManagementTest # Run one test class
php artisan test --filter test_method_name          # Run one test method
./vendor/bin/pint         # Format / lint (Laravel preset, no pint.json)
npm run build             # Production asset build
php artisan user:role <user_id> {admin|secretary|user}  # Change a user's role
php artisan queue:work    # Required when EVOLUTION_WHATSAPP_QUEUE=true
php artisan db:seed       # Seeds admin@meet.local + secretaria@meet.local (password = DEFAULT_USER_PASSWORD)
```

Tests use PHPUnit (not Pest) with an in-memory SQLite DB (see `phpunit.xml`). New test classes go in `tests/Feature` or `tests/Unit`.

## Architecture

The reservation domain is the heart of the app. HTTP is kept thin; business rules live in **Actions** and **Services**, invoked from controllers via method-injected dependencies.

**Request flow:** `Controller` → `FormRequest` (validation + `authorize()`) → `Action` (orchestration, transactions) → `Service` (reusable domain logic) → `Model`. Controllers also fire WhatsApp notifications *after* the action succeeds.

### Two reservation shapes
- **Single reservation** (`Reservation`): one booking. Created by `CreateReservationAction`.
- **Recurring series** (`ReservationSeries` + many child `Reservation`s linked by `series_id`): `CreateRecurringReservationSeriesAction` uses `RecurringReservationOccurrenceGenerator` to expand a frequency/weekday/date-range into individual `Reservation` rows. Series creation is restricted to `secretary`/`admin`.

`ReservationController::update` and `destroy` branch on a `series_scope` input (`occurrence` | `following` | `all`):
- `occurrence` → edit/delete just this `Reservation`
- `following` → `UpdateReservationFollowingAction` / `DeleteReservationFollowingAction` (this and later occurrences; trims the series)
- `all` → `UpdateReservationSeriesAction` / `CancelReservationSeriesAction` (entire series)

### Conflict detection
`ReservationConflictService` is the single source of truth for time-overlap checks (same `room_id` + `date`, overlapping `[start_time, end_time)`). Actions call `findConflict(..., lockForUpdate: true)` **inside a `DB::transaction`** to avoid race conditions, throwing `ReservationConflictException` (single) or `RecurringReservationConflictException` (series, carries per-occurrence conflicts). Controllers catch these and return `back()->withInput()` with flash data — never let them bubble.

### Creator vs. owner
A reservation has both `user_id` (who created it) and `owner_user_id` (the titular — who the booking is *for*). This lets the secretary book on someone else's behalf. `updated_by` is auto-filled on update via the model's `booted()` hook. Visibility for non-managers is enforced by `Reservation::scopeVisibleTo()` (owner or, when owner is null, creator).

### Authorization
Roles are an enum (`App\Enums\UserRole`: Admin / Secretary / User). `User::canManageAgenda()` (admin OR secretary) is the key gate. Policies (`app/Policies`) enforce the rest — notably `ReservationPolicy` blocks updating/deleting a reservation whose end datetime has already passed (`hasReservationEnded`). Any new authorization should go through policies + FormRequest `authorize()`, not inline controller checks.

### Listing
The main agenda list is a Livewire PowerGrid component (`app/Livewire/ReservationsTable.php`) with filters, sorting, multi-select, and CSV export of selected rows (`reservations.export-selected`). The same controller serves "Agendamentos" (upcoming) and "Histórico" (past) via a `scope` argument.

### WhatsApp notifications (Evolution API)
`ReservationWhatsAppNotificationService` builds PT-BR messages and dispatches them for create/update/cancel of reservations and series. It honors `services.evolution_whatsapp.enabled`; when `queue=true` it pushes `SendWhatsAppMessageJob` (needs `queue:work`), otherwise sends synchronously via `EvolutionWhatsAppService`. All config is under `config/services.php` → `evolution_whatsapp` (env `EVOLUTION_WHATSAPP_*`). Notifications are best-effort — failures are logged, not thrown.

## Conventions
- Put new domain operations in `app/Actions/Reservations` (orchestration) and `app/Services` (reusable logic); keep controllers thin.
- Wrap any write that depends on a conflict check in `DB::transaction` with `lockForUpdate`.
- Models expose PT-BR formatted accessors (`date_br`, `start_time_br`, `frequency_label`, etc.) — reuse them in views/exports instead of re-formatting.
- Auth scaffolding is Laravel Breeze; public registration is gated by `ALLOW_PUBLIC_REGISTRATION` (default false). `/`, `/dashboard` redirect to `reservations.index`.
