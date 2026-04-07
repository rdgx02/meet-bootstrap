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
- Filters handled directly in PowerGrid:
  - reservation code
  - room
  - title
  - requester
  - date
  - start/end time
  - created by / edited by
- Initial request filtering is currently limited to `per_page`
- Sorting defaults:
  - upcoming by `date asc`
  - history by `date desc`
- Row highlighting:
  - upcoming rows are styled as `confirmed` for today and `reserved` for future items
  - history rows are styled as `archived`
- Bulk actions:
  - `Visualizar` requires exactly one selected row
  - `Editar` requires exactly one selected row and only appears outside history
  - `Excluir` supports one or many selected rows and respects backend policies
  - `Exportar` exports only the selected rows through `reservations.export-selected`

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

## 2026-03-30

1. Goal of the change.
   Reinforce safety around the reservations table before new UI changes.
2. Files edited.
   `tests/Feature/ReservationManagementTest.php`
3. Any new PowerGrid publish/override.
   None.
4. Any behavior change in filters, sorting, pagination, or permissions.
   None in production behavior. Coverage was added for upcoming/history datasource rules and active-room sourcing.
5. Whether tests were updated.
   Yes. New automated checks now cover the `ReservationsTable` contract more directly.

## 2026-04-07

1. Goal of the change.
   Consolidate the new `Disponibilidade` experience as a textual consultation flow for secretaria/users, while keeping the reservations table stable.
2. Files edited.
   `app/Http/Controllers/AvailabilityController.php`
   `app/Services/AvailabilityOverviewService.php`
   `resources/views/availability/index.blade.php`
   `resources/css/app.css`
   `tests/Feature/AvailabilityConsultationTest.php`
   `README.md`
3. Any new PowerGrid publish/override.
   None.
4. Any behavior change in filters, sorting, pagination, or permissions.
   No change in PowerGrid behavior. Availability now supports date + optional room filtering, prioritizes textual availability, and orders rooms by status in the `Todas` view.
5. Whether tests were updated.
   Yes. Availability coverage now includes room filtering, full-day occupancy, room ordering by status, and the no-active-rooms scenario.

## Pending Follow-Up Worth Tracking

- Add or adjust automated tests for the Livewire reservations table behavior.
- Confirm if the published PowerGrid bootstrap views need more project-specific styling or can be reduced to avoid maintenance on upgrades.
- Decide whether this same PowerGrid pattern should also replace the rooms list in the future.
