# T014 A1 — Pengeluaran

Input basis: `T008=3610253be1dedb1d9abad958059f52e33377f320`. Implemented from branch `codex/t014-expenses`, starting at `3c35dd1118121d02cca7abb0ae99b78baeb30634`.

## Checks run

- PHP 8.3 `artisan test tests/Feature/Expenses/ExpensesTest.php`: **5 tests, 57 assertions passed**.
- Scoped Pint: passed for the component and feature test.
- Scoped PHPStan (`--debug`): passed with **0 errors**.
- `git diff --check`: passed.
- Headless Chrome, private SQLite fixture and isolated app storage: create 150000 displayed as `150.000` and persisted as `150000`; edit to 175000 persisted; date filter included both endpoints and totaled only matching rows; same-day, reversed, cleared, and recovered ranges behaved as expected. Invalid date and amount validation are covered by feature tests.
- Delete confirmation cancel and Escape both closed the dialog and restored focus to the row action trigger. Reopening and confirming deleted the synthetic row. Technician request to `/pengeluaran` returned **403**. Browser reported no console errors.
- List/form screenshots were captured at 360, 768 and 1280 CSS pixels; the refreshed list screenshots confirm the description is on its own line and no horizontal overflow at 360 or 1280. The screenshots named `reference-tts-*` and `reference-tts-form-*` are the nearest approved mockups used for visual comparison; no expense-specific mockup exists.

## Screenshots

- Expense list: `screens/list-final-360.png`, `screens/list-final-768.png`, `screens/list-final-1280.png`.
- Expense form: `screens/form-final-360.png`, `screens/form-final-768.png`, `screens/form-final-1280.png`.
- Nearest references: `screens/reference-tts-360.png`, `screens/reference-tts-1280.png`, `screens/reference-tts-form-360.png`, `screens/reference-tts-form-1280.png`.

## Limits

- All browser data was synthetic in a private SQLite database; no MySQL data was accessed or reset.
- The full suite and asset build were not repeated; T015 owns the combined fresh build. Deployment and production printing were not tested.
- Task model hint was `gpt-6-luna`; no backend model receipt was available, so the effective model is not asserted.
