# Bootstrap + PowerGrid Notes

## Context

This file records the UI migration work that introduced Bootstrap 5 styling and Livewire PowerGrid into the reservations flow. Update it whenever we change the table structure, published PowerGrid views, or shared layout styles.

## What Was Applied

### 1. Bootstrap 5 in the frontend

- `bootstrap` and `@popperjs/core` are installed through `package.json`.
- Global Bootstrap JS is loaded in `resources/js/app.js`.
- Shared visual language was migrated to Bootstrap-friendly classes in:
  - `resources/views/layouts/app.blade.php`
  - `resources/views/layouts/guest.blade.php`
  - `resources/views/layouts/navigation.blade.php`
  - `resources/views/reservations/index.blade.php`
- Custom project styling lives in `resources/css/app.css`.

### 2. PowerGrid for reservations listing

- `power-components/livewire-powergrid` was added in `composer.json`.
- PowerGrid config was published to `config/livewire-powergrid.php`.
- Theme is set to `Bootstrap5::class`.
- Reservation list rendering moved from controller + Blade table to a Livewire component:
  - `app/Livewire/ReservationsTable.php`
  - `resources/views/livewire/reservations-table/filters.blade.php`
- `ReservationController` now sends validated filters to the Livewire component instead of building the paginated list directly.

### 3. Published PowerGrid view overrides

These files were published locally and are now part of the project surface:

- `resources/views/vendor/livewire-powergrid/components/frameworks/bootstrap5/table-base.blade.php`
- `resources/views/vendor/livewire-powergrid/components/frameworks/bootstrap5/header.blade.php`
- `resources/views/vendor/livewire-powergrid/components/frameworks/bootstrap5/footer.blade.php`

If PowerGrid is upgraded, review these overrides first because package updates may stop matching our local templates.

## Current Reservations Table Behavior

- Separate scopes:
  - `upcoming`: today/future reservations not yet ended
  - `history`: past reservations or same-day reservations already finished
- Filters handled in Livewire:
  - text search (`q`)
  - room (`room_id`)
  - date range (`date_from`, `date_to`)
  - per-page selection
- Sorting defaults:
  - upcoming by `date asc`
  - history by `date desc`
- Row highlighting:
  - reservations created by the logged-in user receive `row-mine`
- Actions:
  - `Ver` always available
  - `Editar` and `Excluir` respect policies

## Files To Check Before Any Future UI Change

- `app/Livewire/ReservationsTable.php`
- `resources/views/livewire/reservations-table/filters.blade.php`
- `resources/views/reservations/index.blade.php`
- `resources/css/app.css`
- `config/livewire-powergrid.php`
- `resources/views/vendor/livewire-powergrid/components/frameworks/bootstrap5/*.blade.php`

## Recommended Next Notes To Keep Updated

When we touch this area again, append a dated entry here with:

1. Goal of the change.
2. Files edited.
3. Any new PowerGrid publish/override.
4. Any behavior change in filters, sorting, pagination, or permissions.
5. Whether tests were updated.

## Pending Follow-Up Worth Tracking

- Add or adjust automated tests for the Livewire reservations table behavior.
- Review whether the date filter inputs should reuse `flatpickr` in the Livewire filter view for visual consistency.
- Confirm if the published PowerGrid bootstrap views need more project-specific styling or can be reduced to avoid maintenance on upgrades.
- Decide whether this same PowerGrid pattern should also replace the rooms list in the future.

## Local State Seen During Review

At review time, the working tree already contained local changes related to this migration:

- modified: `app/Http/Controllers/ReservationController.php`
- modified: `composer.json`
- modified: `composer.lock`
- modified: `resources/css/app.css`
- modified: `resources/views/layouts/app.blade.php`
- modified: `resources/views/layouts/guest.blade.php`
- modified: `resources/views/reservations/index.blade.php`
- untracked: `app/Livewire/`
- untracked: `config/livewire-powergrid.php`
- untracked: `resources/views/livewire/`
- untracked: `resources/views/vendor/`
