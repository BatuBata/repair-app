# TODO — Lean SaaS Multi-Toko

## Tujuan dan cara pakai

Rencana ini mengubah aplikasi Anugerah menjadi aplikasi multi-toko dengan perubahan terkecil yang aman. Sumber kebutuhan adalah `docs/SAAS-RANCANGAN.md`; `PRD.md` dan `TODO.md` lama tetap menjadi sumber produk produksi saat ini.

**Sumber status tunggal untuk pekerjaan SaaS adalah ledger JSON di bawah.** Koordinator memperbarui ledger setelah memeriksa bukti worker. Task implementasi `done` hanya berarti candidate siap diuji; fitur baru diterima setelah QA independen hasil integrasi lulus.

Batas anti-over-engineering:

- tidak membuat panel platform, billing, paket, trial, subdomain, plugin, feature flag, API, queue, atau microservice;
- toko HP memakai kategori dan pengaturan existing, bukan modul baru;
- tidak menambah dependency multi-tenancy;
- hanya `S002` yang membuat migrasi schema SaaS;
- pekerjaan dijalankan berurutan bila menyentuh fondasi tenant atau database yang sama;
- deployment produksi dan migrasi data memerlukan izin terpisah, backup teruji, dan maintenance window.

Goal aktif 17 November 2026 mengizinkan eksekusi S001–S012. Deployment produksi tetap mengikuti dependency, QA, backup/restore, dan maintenance gate di S010.

## Overview pekerjaan

<!-- GENERATED VIEW: regenerate from canonical JSON; do not edit statuses here. -->
| ID | Hasil | Petugas / model | Kesulitan / risiko | Dependensi | Calon paralel | Status |
|---|---|---|---|---|---|---|
| S001 | Tetapkan kontrak username dan tenant nonaktif | planner / 6.1-sol | easy / medium | — | — | done |
| S002 | Bangun schema dan fondasi isolasi tenant | developer / 6.1-sol | complex / high | S001 | — | done |
| S003 | Jadikan auth, pengguna, pengaturan, dan onboarding tenant-aware | developer / 6.1-sol | medium / high | S002 | — | done |
| S004 | Isolasi alur servis, dokumen, dan tracking | developer / 6.1-sol | complex / high | S003 | — | done |
| S005 | Isolasi stok, penjualan, pengeluaran, dashboard, dan laporan | developer / 6.1-sol | complex / high | S004 | — | done |
| S006 | Integrasikan candidate Lean SaaS | integrator / 6.1-sol | medium / high | S003, S004, S005 | — | done |
| S007 | QA independen keamanan tenant dan regresi aplikasi | tester / 6.1-sol | complex / high | S006 | — | running |
| S008 | Siapkan runbook dan rehearsal migrasi Anugerah | developer / 6.1-sol | medium / high | S007 | — | pending |
| S009 | QA independen rehearsal backup, migrasi, dan rollback | tester / 6.1-sol | complex / high | S008 | — | pending |
| S010 | Deploy dan migrasikan Anugerah ke schema tenant | deployer / 6.1-sol | medium / high | S006, S007, S009 | — | pending |
| S011 | Verifikasi independen produksi setelah migrasi | tester / 6.1-sol | medium / high | S010 | — | pending |
| S012 | Onboard dan verifikasi pilot client kedua | tester / 6.1-sol | medium / high | S010, S011 | — | pending |
<!-- END GENERATED VIEW -->

## Urutan minimum

| Gelombang | Task | Hasil |
|---|---|---|
| 1. Kontrak | S001 | Dua keputusan yang mengubah auth/perilaku tenant disetujui |
| 2. Fondasi | S002 | Schema tenant, context, scope, dan middleware |
| 3. Caller existing | S003 → S004 → S005 | Semua modul existing terisolasi tanpa fitur baru |
| 4. Penerimaan software | S006 → S007 | Candidate gabungan dan QA independen |
| 5. Migrasi produksi | S008 → S009 → S010 → S011 | Rehearsal, deployment berizin, dan smoke produksi |
| 6. Pilot | S012 | Client kedua dibuat tanpa repo/database/deployment baru |

Walau beberapa caller dapat terlihat independen, rencana default menjalankannya berurutan karena semuanya memakai schema, fixture, dan kontrak tenant yang sama. Paralelisme hanya dibuka bila scope aktual terbukti tidak beririsan.

## Ledger canonical

```json
{
  "schema": "project-delivery.todo.v1",
  "project": {
    "id": "repair-app-saas",
    "prd_file": "docs/SAAS-RANCANGAN.md",
    "prd_revision": "sha256:a2b342d0aabb9c1b9dbcf57668a5409b448b6df575dc1f571e568346b0b698c6",
    "max_workers": 2,
    "max_implementation_attempts": 2,
    "execution": {
      "state": "running",
      "allowed_tasks": [
        "S001",
        "S002",
        "S003",
        "S004",
        "S005",
        "S006",
        "S007",
        "S008",
        "S009",
        "S010",
        "S011",
        "S012"
      ],
      "stop_after_tasks": [
        "S012"
      ],
      "source_ref": "User17Nov2026 mengaktifkan Goal 'sekarang kerjakan semua todo SAAS-TODO.md'. Izin mencakup S001–S012, tetapi tidak menghapus gate dependency, QA, backup/restore, maintenance window, atau larangan tindakan destruktif."
    },
    "exclusive_resources": [
      "branch:saas-integration",
      "branch:main",
      "db:mysql-saas-test",
      "db:mysql-saas-migration",
      "db:production",
      "server:demo-repair-app"
    ],
    "artifact_retention": {
      "authorization_source_ref": null,
      "generated_paths": [],
      "preserve_paths": [
        "docs/evidence/SAAS/**"
      ],
      "worktree_retirement_authorized": false
    },
    "model_catalog": {
      "status": "verified",
      "available": [
        "gpt-6.1-sol",
        "gpt-6.1-terra",
        "gpt-6-luna"
      ],
      "evidence_ref": "codex-runtime:goal-start-2026-11-17"
    },
    "authorizations": [
      {
        "id": "saas-production-2026-11-17",
        "environment": "production",
        "scope": "deployment",
        "source_ref": "User17Nov2026 explicitly activates Goal to execute every task S001–S012, including S010 deployment. Authorization remains conditional on S007/S009 PASS, offsite backup with proven restore, maintenance gate, and non-destructive rollback."
      }
    ]
  },
  "deliverables": [
    {
      "id": "SD1",
      "title": "Aplikasi existing aman dipakai banyak tenant",
      "kind": "feature",
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §3–§7",
        "docs/SAAS-RANCANGAN.md §11"
      ],
      "acceptance_tasks": [
        "S007"
      ],
      "status": "pending"
    },
    {
      "id": "SD2",
      "title": "Data Anugerah dimigrasikan tanpa hilang atau tercampur",
      "kind": "release",
      "environment": "production",
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §10",
        "docs/SAAS-RANCANGAN.md §13"
      ],
      "acceptance_tasks": [
        "S011"
      ],
      "status": "pending"
    },
    {
      "id": "SD3",
      "title": "Client kedua dapat memakai aplikasi tanpa infrastruktur baru",
      "kind": "release",
      "environment": "production",
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §8–§9",
        "docs/SAAS-RANCANGAN.md §13"
      ],
      "acceptance_tasks": [
        "S012"
      ],
      "status": "pending"
    }
  ],
  "tasks": [
    {
      "id": "S001",
      "title": "Tetapkan kontrak username dan tenant nonaktif",
      "kind": "contract",
      "responsible_role": "planner",
      "work_type": "backend",
      "deliverable_id": null,
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §14"
      ],
      "priority": "must",
      "difficulty": "easy",
      "risk": "medium",
      "uncertainty": "medium",
      "triage_reason": "Dua keputusan ini mengubah query login dan perilaku akses tenant; keduanya harus stabil sebelum schema dan middleware ditulis.",
      "model_hint": "gpt-6.1-sol",
      "model_reason": "Sol untuk menilai konsekuensi auth dan keamanan tenant tanpa memperluas scope produk.",
      "depends_on": {
        "hard": [],
        "contracts": []
      },
      "write_scope": [
        "docs/SAAS-RANCANGAN.md",
        "docs/evidence/SAAS/S001/**"
      ],
      "shared_resources": [],
      "conflicts_with": [],
      "parallel_candidates": [],
      "acceptance_criteria": [
        "Diputuskan apakah username tetap unik global untuk v1 SaaS.",
        "Diputuskan bahwa tenant nonaktif memblokir login internal dan apakah tracking lama tetap tersedia.",
        "Keputusan dicatat tanpa menambah paket, billing, domain, membership, atau fitur lain."
      ],
      "verification_plan": [
        "Review keputusan terhadap alur Fortify, tracking publik, dan batas Lean SaaS; pin revisi rancangan yang disetujui."
      ],
      "status": "done",
      "attempts": [
        {
          "id": "A1",
          "kind": "discovery",
          "worker_id": "/root",
          "model_requested": "gpt-6.1-sol",
          "model_effective": null,
          "dispatch_ref": "user-goal:all-saas-todo-2026-11-17",
          "outcome": "pass",
          "receipt_ref": "docs/evidence/SAAS/S001/README.md"
        }
      ],
      "result": {
        "verdict": "pass",
        "artifact_ref": "docs/evidence/SAAS/S001/README.md",
        "revision": "sha256:a2b342d0aabb9c1b9dbcf57668a5409b448b6df575dc1f571e568346b0b698c6",
        "checked_revision": "sha256:a2b342d0aabb9c1b9dbcf57668a5409b448b6df575dc1f571e568346b0b698c6",
        "inputs": {},
        "evidence": [
          "docs/evidence/SAAS/S001/README.md"
        ],
        "summary": "Username unik global dan perilaku tenant nonaktif ditetapkan sesuai rekomendasi Lean SaaS; scope tetap tanpa panel, billing, subdomain, membership, atau dependency baru."
      }
    },
    {
      "id": "S002",
      "title": "Bangun schema dan fondasi isolasi tenant",
      "kind": "implementation",
      "responsible_role": "developer",
      "work_type": "backend",
      "deliverable_id": "SD1",
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §4–§6"
      ],
      "priority": "must",
      "difficulty": "complex",
      "risk": "high",
      "uncertainty": "medium",
      "triage_reason": "Kesalahan schema, scope, atau pengisian tenant_id dapat membocorkan atau merusak seluruh data client; task ini menjadi satu-satunya pemilik migrasi schema SaaS.",
      "model_hint": "gpt-6.1-sol",
      "model_reason": "Sol untuk migrasi kompatibel, global scope, middleware, dan integritas relasi berisiko tinggi.",
      "depends_on": {
        "hard": [],
        "contracts": [
          "S001"
        ]
      },
      "write_scope": [
        "database/migrations/**",
        "app/Models/Tenant.php",
        "app/Models/Concerns/BelongsToTenant.php",
        "app/Support/TenantContext.php",
        "app/Http/Middleware/EnsureTenant.php",
        "app/Providers/AppServiceProvider.php",
        "bootstrap/app.php",
        "tests/Feature/Tenancy/FoundationTest.php",
        "docs/evidence/SAAS/S002/**"
      ],
      "shared_resources": [
        {
          "name": "db:mysql-saas-test",
          "mode": "exclusive"
        }
      ],
      "conflicts_with": [],
      "parallel_candidates": [],
      "acceptance_criteria": [
        "Tabel tenants hanya memuat kolom minimum yang disetujui dan data utama memiliki tenant_id serta indeks/constraint yang diperlukan.",
        "TenantContext, BelongsToTenant, dan middleware membatasi query/create ke tenant user aktif tanpa menerima tenant_id dari request.",
        "Record tenant lain menghasilkan 404 dan relasi silang tenant ditolak.",
        "Tidak ada package multi-tenancy, repository layer, API, queue, feature flag, atau abstraksi tambahan."
      ],
      "verification_plan": [
        "Tes SQLite untuk scope/create/404/tenant nonaktif dan tes migrasi MariaDB/MySQL nyata pada database khusus SaaS.",
        "Audit schema, unique key, foreign key, rollback migration, Pint, dan static analysis yang sudah dipakai proyek."
      ],
      "status": "done",
      "workspace": "/Users/muzib/Projects/web/repair-app",
      "attempts": [
        {
          "id": "A1",
          "kind": "implementation",
          "worker_id": "/root/s002_tenant_foundation",
          "model_requested": "gpt-6.1-sol",
          "model_effective": null,
          "dispatch_ref": "native-codex:S002:A1",
          "outcome": "pass",
          "receipt_ref": "docs/evidence/SAAS/S002/A1/README.md"
        }
      ],
      "result": {
        "verdict": "pass",
        "artifact_ref": "docs/evidence/SAAS/S002/A1/README.md",
        "revision": "sha256:b2f96a6201d13e31997abab578edc0917f899f2b2c54657617c607907637626c",
        "checked_revision": "sha256:b2f96a6201d13e31997abab578edc0917f899f2b2c54657617c607907637626c",
        "inputs": {
          "S001": "sha256:a2b342d0aabb9c1b9dbcf57668a5409b448b6df575dc1f571e568346b0b698c6"
        },
        "evidence": [
          "docs/evidence/SAAS/S002/A1/README.md",
          "tests/Feature/Tenancy/FoundationTest.php"
        ],
        "summary": "Fondasi tenant minimal, scope fail-closed, tenant nonaktif, migrasi data-preserving, rollback aman, dan DDL MySQL 8.0.31 lulus; tidak ada dependency atau fitur platform tambahan."
      }
    },
    {
      "id": "S003",
      "title": "Jadikan auth, pengguna, pengaturan, dan onboarding tenant-aware",
      "kind": "implementation",
      "responsible_role": "developer",
      "work_type": "backend",
      "deliverable_id": "SD1",
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §4",
        "docs/SAAS-RANCANGAN.md §7",
        "docs/SAAS-RANCANGAN.md §9"
      ],
      "priority": "must",
      "difficulty": "medium",
      "risk": "high",
      "uncertainty": "low",
      "triage_reason": "Login adalah pengecualian query sebelum tenant diketahui; kesalahan scope pengguna atau store setting dapat memberi akses ke tenant lain.",
      "model_hint": "gpt-6.1-sol",
      "model_reason": "Sol untuk auth, validasi unik, dan provisioning atomik yang menyentuh batas keamanan.",
      "depends_on": {
        "hard": [
          "S002"
        ],
        "contracts": []
      },
      "write_scope": [
        "app/Providers/FortifyServiceProvider.php",
        "app/Http/Middleware/EnsureActiveAccount.php",
        "app/Models/User.php",
        "app/Models/StoreSetting.php",
        "app/Livewire/Users/**",
        "app/Livewire/Settings/**",
        "resources/views/layouts/app/sidebar.blade.php",
        "resources/views/layouts/auth/simple.blade.php",
        "resources/views/pages/auth/login.blade.php",
        "resources/views/livewire/users/index.blade.php",
        "app/Console/Commands/CreateTenant.php",
        "database/factories/**",
        "database/seeders/**",
        "tests/Feature/Auth/**",
        "tests/Feature/Users/**",
        "tests/Feature/Settings/**",
        "tests/Feature/Tenancy/IdentityTest.php",
        "docs/evidence/SAAS/S003/**"
      ],
      "shared_resources": [
        {
          "name": "db:mysql-saas-test",
          "mode": "exclusive"
        }
      ],
      "conflicts_with": [],
      "parallel_candidates": [],
      "acceptance_criteria": [
        "Login global hanya mencari satu username unik lalu menetapkan tenant dari user; user tenant lain tidak muncul pada pengelolaan pengguna.",
        "Store setting dan kategori hanya membaca/menulis tenant aktif.",
        "Command tenant:create secara interaktif membuat tenant, store setting, dan owner admin dalam satu transaksi tanpa password di argumen/log.",
        "Owner tetap mengisi kategori dan teknisi melalui layar existing; tidak ada preset engine atau panel platform."
      ],
      "verification_plan": [
        "Feature test login dua tenant, tenant nonaktif, unique username, user/settings/category isolation, rollback provisioning, dan password tidak tercetak.",
        "Jalankan test auth/users/settings existing, Pint, dan audit command pada kegagalan transaksi."
      ],
      "status": "done",
      "workspace": "/Users/muzib/Projects/web/repair-app",
      "attempts": [
        {
          "id": "A1",
          "kind": "implementation",
          "worker_id": "/root/s003_identity",
          "model_requested": "gpt-6.1-sol",
          "model_effective": null,
          "dispatch_ref": "native-codex:S003:A1",
          "outcome": "pass",
          "receipt_ref": "docs/evidence/SAAS/S003/A1/README.md"
        }
      ],
      "result": {
        "verdict": "pass",
        "artifact_ref": "docs/evidence/SAAS/S003/A1/README.md",
        "revision": "sha256:d5a8bb86214580207d73ca1e8039b84f20e34dc912cad3c924be5398320a3160",
        "checked_revision": "sha256:d5a8bb86214580207d73ca1e8039b84f20e34dc912cad3c924be5398320a3160",
        "inputs": {
          "S002": "sha256:b2f96a6201d13e31997abab578edc0917f899f2b2c54657617c607907637626c"
        },
        "evidence": [
          "docs/evidence/SAAS/S003/A1/README.md",
          "docs/evidence/SAAS/S003/A1/tests.json",
          "docs/evidence/SAAS/S003/A1/terminal-check.txt"
        ],
        "summary": "Auth global, session/remember, user, kategori, pengaturan, identitas shell, dan onboarding atomik sudah tenant-aware; 66 tes/614 assertion, static analysis, formatter, dan terminal password tersembunyi lulus tanpa fitur platform tambahan."
      }
    },
    {
      "id": "S004",
      "title": "Isolasi alur servis, dokumen, dan tracking",
      "kind": "implementation",
      "responsible_role": "developer",
      "work_type": "backend",
      "deliverable_id": "SD1",
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §6–§7",
        "docs/SAAS-RANCANGAN.md §11"
      ],
      "priority": "must",
      "difficulty": "complex",
      "risk": "high",
      "uncertainty": "low",
      "triage_reason": "TTS membawa data customer dan dokumen publik; satu query atau relasi tanpa scope dapat menjadi kebocoran lintas tenant.",
      "model_hint": "gpt-6.1-sol",
      "model_reason": "Sol untuk audit akses end-to-end pada transaksi, dokumen, dan token publik.",
      "depends_on": {
        "hard": [
          "S003"
        ],
        "contracts": []
      },
      "write_scope": [
        "app/Actions/ServiceOrders/**",
        "app/Models/ServiceOrder.php",
        "app/Services/CreateServiceOrder.php",
        "app/Services/DocumentNumber.php",
        "app/Services/Documents/**",
        "app/Http/Controllers/TrackingController.php",
        "app/Http/Controllers/DocumentController.php",
        "app/Livewire/Tts/**",
        "app/Livewire/Invoice/**",
        "app/Support/WhatsApp.php",
        "resources/views/tracking/**",
        "resources/views/documents/**",
        "routes/modules/tracking.php",
        "routes/modules/documents.php",
        "tests/Feature/Tts/**",
        "tests/Feature/Invoice/**",
        "tests/Feature/Tracking/**",
        "tests/Feature/Documents/**",
        "tests/Feature/Tenancy/ServiceIsolationTest.php",
        "docs/evidence/SAAS/S004/**"
      ],
      "shared_resources": [
        {
          "name": "db:mysql-saas-test",
          "mode": "exclusive"
        }
      ],
      "conflicts_with": [],
      "parallel_candidates": [],
      "acceptance_criteria": [
        "TTS, teknisi, kategori, rincian, dokumen, dan pencarian selalu berasal dari tenant aktif.",
        "Nomor TTS/JL memiliki counter independen per tenant dan tetap aman saat konkurensi.",
        "Tracking token global hanya menampilkan TTS dan identitas toko pemilik tanpa data privat tambahan.",
        "ID atau payload tenant lain ditolak dan tidak mengubah data."
      ],
      "verification_plan": [
        "Tes dua tenant untuk list/detail/edit/action, relasi silang, PDF/struk/WhatsApp/tracking, dan 404 pada ID tenant lain.",
        "Uji konkurensi nomor per tenant pada MariaDB/MySQL test serta jalankan regresi TTS/Invoice/Tracking/Documents existing."
      ],
      "status": "done",
      "workspace": "/Users/muzib/Projects/web/repair-app",
      "attempts": [
        {
          "id": "A1",
          "kind": "implementation",
          "worker_id": "/root/s004_service",
          "model_requested": "gpt-6.1-sol",
          "model_effective": null,
          "dispatch_ref": "native-codex:S004:A1",
          "outcome": "pass",
          "receipt_ref": "docs/evidence/SAAS/S004/A1/README.md"
        }
      ],
      "result": {
        "verdict": "pass",
        "artifact_ref": "docs/evidence/SAAS/S004/A1/README.md",
        "revision": "sha256:c721514661e503ee70abe9fbdd508896f18835b3e0070cb1d6c20664c4a66674",
        "checked_revision": "sha256:c721514661e503ee70abe9fbdd508896f18835b3e0070cb1d6c20664c4a66674",
        "inputs": {
          "S003": "sha256:d5a8bb86214580207d73ca1e8039b84f20e34dc912cad3c924be5398320a3160"
        },
        "evidence": [
          "docs/evidence/SAAS/S004/A1/README.md",
          "docs/evidence/SAAS/S004/A1/mysql-concurrency.json",
          "docs/evidence/SAAS/S004/A1/regression-final.json"
        ],
        "summary": "Servis, relasi, nomor, dokumen, WhatsApp, dan tracking publik sudah terisolasi per tenant; 48 tes S004 lulus dan konkurensi MySQL 32 proses lulus. Dua case dokumen commerce tetap dicatat untuk diselesaikan oleh S005."
      }
    },
    {
      "id": "S005",
      "title": "Isolasi stok, penjualan, pengeluaran, dashboard, dan laporan",
      "kind": "implementation",
      "responsible_role": "developer",
      "work_type": "backend",
      "deliverable_id": "SD1",
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §6–§7",
        "docs/SAAS-RANCANGAN.md §11"
      ],
      "priority": "must",
      "difficulty": "complex",
      "risk": "high",
      "uncertainty": "low",
      "triage_reason": "Stok dan laporan mengubah serta mengagregasi data keuangan; scope yang salah dapat merusak stok atau membuka angka tenant lain.",
      "model_hint": "gpt-6.1-sol",
      "model_reason": "Sol untuk transaksi stok dan agregasi keuangan dengan batas tenant yang wajib konsisten.",
      "depends_on": {
        "hard": [
          "S004"
        ],
        "contracts": []
      },
      "write_scope": [
        "app/Actions/Sales/**",
        "app/Models/Product.php",
        "app/Models/StockEntry.php",
        "app/Models/Sale.php",
        "app/Models/SaleItem.php",
        "app/Models/Expense.php",
        "app/Livewire/Stock/**",
        "app/Livewire/Sales/**",
        "app/Livewire/Expenses/**",
        "app/Livewire/Dashboard/**",
        "app/Livewire/Reports/**",
        "app/Services/Reports/**",
        "tests/Feature/Stock/**",
        "tests/Feature/Sales/**",
        "tests/Feature/Expenses/**",
        "tests/Feature/Dashboard/**",
        "tests/Feature/Reports/**",
        "tests/Feature/Tenancy/CommerceIsolationTest.php",
        "docs/evidence/SAAS/S005/**"
      ],
      "shared_resources": [
        {
          "name": "db:mysql-saas-test",
          "mode": "exclusive"
        }
      ],
      "conflicts_with": [],
      "parallel_candidates": [],
      "acceptance_criteria": [
        "Produk, restok, penjualan, pengeluaran, garansi, dashboard, dan laporan hanya memakai tenant aktif.",
        "Payload produk/penjualan tenant lain ditolak tanpa perubahan stok.",
        "Nomor JL, stok, dan seluruh total laporan independen per tenant.",
        "Tidak ada analitik lintas tenant atau fitur keuangan baru."
      ],
      "verification_plan": [
        "Tes dua tenant untuk CRUD/action, relasi silang, rollback stok, garansi, dashboard, dan total laporan.",
        "Jalankan regresi Stock/Sales/Expenses/Dashboard/Reports existing serta tes konkurensi stok yang sudah ada."
      ],
      "status": "done",
      "workspace": "/Users/muzib/Projects/web/repair-app",
      "attempts": [
        {
          "id": "A1",
          "kind": "implementation",
          "worker_id": "/root/s005_commerce",
          "model_requested": "gpt-6.1-sol",
          "model_effective": null,
          "dispatch_ref": "native-codex:S005:A1",
          "outcome": "pass",
          "receipt_ref": "docs/evidence/SAAS/S005/A1/README.md"
        }
      ],
      "result": {
        "verdict": "pass",
        "artifact_ref": "docs/evidence/SAAS/S005/A1/README.md",
        "revision": "sha256:9ab1f13ae929cb26cdac0c12aa61137e48d518e2617b886cf354f44b9340802f",
        "checked_revision": "sha256:9ab1f13ae929cb26cdac0c12aa61137e48d518e2617b886cf354f44b9340802f",
        "inputs": {
          "S004": "sha256:c721514661e503ee70abe9fbdd508896f18835b3e0070cb1d6c20664c4a66674"
        },
        "evidence": [
          "docs/evidence/SAAS/S005/A1/README.md",
          "docs/evidence/SAAS/S005/A1/tests.txt",
          "docs/evidence/SAAS/S005/A1/mysql-concurrency.json"
        ],
        "summary": "Stok, penjualan, pengeluaran, garansi, dashboard, laporan, dan dokumen penjualan terisolasi per tenant; 66 tes/893 assertion serta race stok MySQL dua tenant lulus."
      }
    },
    {
      "id": "S006",
      "title": "Integrasikan candidate Lean SaaS",
      "kind": "integration",
      "responsible_role": "integrator",
      "work_type": "integration",
      "deliverable_id": "SD1",
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §2–§7"
      ],
      "priority": "must",
      "difficulty": "medium",
      "risk": "high",
      "uncertainty": "low",
      "triage_reason": "Schema, auth, servis, dan keuangan harus berada pada revisi yang sama sebelum keamanan tenant dapat dinilai.",
      "model_hint": "gpt-6.1-sol",
      "model_reason": "Sol untuk review konflik dan integrasi hasil backend berisiko tinggi.",
      "depends_on": {
        "hard": [
          "S003",
          "S004",
          "S005"
        ],
        "contracts": []
      },
      "write_scope": [
        "**"
      ],
      "shared_resources": [
        {
          "name": "branch:saas-integration",
          "mode": "exclusive"
        }
      ],
      "conflicts_with": [],
      "parallel_candidates": [],
      "acceptance_criteria": [
        "Hanya candidate S003–S005 yang lulus self-check digabung di atas fondasi S002.",
        "Tidak ada fitur yang termasuk daftar sengaja tidak dibuat pada rancangan Lean SaaS.",
        "Migrasi, source, test, dan lockfile ditinjau; dependency baru ditolak kecuali ada keputusan user baru.",
        "Candidate gabungan memiliki pin revisi tunggal untuk QA."
      ],
      "verification_plan": [
        "Review commit/diff, konflik, daftar file, schema, dependency, dan secret; jalankan formatter, static checks, build, serta suite software lengkap pada candidate gabungan."
      ],
      "status": "done",
      "workspace": "/Users/muzib/Projects/web/repair-app",
      "attempts": [
        {
          "id": "A1",
          "kind": "implementation",
          "worker_id": "/root/s006_integration",
          "model_requested": "gpt-6.1-sol",
          "model_effective": null,
          "dispatch_ref": "native-codex:S006:A1",
          "outcome": "pass",
          "receipt_ref": "docs/evidence/SAAS/S006/A1/README.md"
        }
      ],
      "result": {
        "verdict": "pass",
        "artifact_ref": "docs/evidence/SAAS/S006/A1/README.md",
        "revision": "sha256:066bb9e82bd3ac5142a1ef516fc46c862c7e3b1b2cbc28ee4eb0a8ab402996f6",
        "checked_revision": "sha256:066bb9e82bd3ac5142a1ef516fc46c862c7e3b1b2cbc28ee4eb0a8ab402996f6",
        "inputs": {
          "S003": "sha256:d5a8bb86214580207d73ca1e8039b84f20e34dc912cad3c924be5398320a3160",
          "S004": "sha256:c721514661e503ee70abe9fbdd508896f18835b3e0070cb1d6c20664c4a66674",
          "S005": "sha256:9ab1f13ae929cb26cdac0c12aa61137e48d518e2617b886cf354f44b9340802f"
        },
        "evidence": [
          "docs/evidence/SAAS/S006/A1/README.md",
          "docs/evidence/SAAS/S006/A1/tests-final.txt",
          "docs/evidence/SAAS/S006/A1/integration-review.json",
          "docs/evidence/SAAS/S006/A1/candidate.json"
        ],
        "summary": "Candidate gabungan Lean SaaS memiliki satu pin immutable; 216 tes lulus, satu test historis terkontrol skipped, formatter, static analysis, build, dan migrasi SQLite bersih lulus tanpa dependency atau fitur platform baru."
      }
    },
    {
      "id": "S007",
      "title": "QA independen keamanan tenant dan regresi aplikasi",
      "kind": "qa",
      "responsible_role": "tester",
      "work_type": "security",
      "deliverable_id": "SD1",
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §11",
        "docs/SAAS-RANCANGAN.md §13"
      ],
      "priority": "must",
      "difficulty": "complex",
      "risk": "high",
      "uncertainty": "low",
      "triage_reason": "Kebocoran tenant merupakan kegagalan keamanan; QA harus menguji revisi integrasi, bukan hanya branch implementer.",
      "model_hint": "gpt-6.1-sol",
      "model_reason": "Sol untuk adversarial access testing, regresi lintas modul, dan verifikasi schema.",
      "depends_on": {
        "hard": [
          "S006"
        ],
        "contracts": []
      },
      "qa_for": "S006",
      "write_scope": [
        "docs/evidence/SAAS/S007/**"
      ],
      "shared_resources": [
        {
          "name": "db:mysql-saas-test",
          "mode": "exclusive"
        }
      ],
      "conflicts_with": [],
      "parallel_candidates": [],
      "acceptance_criteria": [
        "QA mem-pin candidate integrasi dan membuktikan setiap modul menolak ID/payload tenant lain.",
        "Regresi software lengkap, migration test, konkurensi nomor/stok, dokumen, tracking, dan build lulus.",
        "Browser dua sesi membuktikan data, pilihan, dashboard, dan laporan tidak tercampur.",
        "QA mengonfirmasi tidak ada fitur spekulatif atau dependency baru di luar kontrak."
      ],
      "verification_plan": [
        "Jalankan suite penuh pada SQLite dan test tenant/konkurensi pada MariaDB/MySQL khusus; audit query raw/global-scope bypass dan Livewire payload.",
        "Smoke browser tenant A/B pada alur login, TTS, stok, laporan, dokumen, dan tracking."
      ],
      "status": "running",
      "workspace": "/Users/muzib/Projects/web/repair-app",
      "attempts": [
        {
          "id": "A1",
          "kind": "verification",
          "worker_id": "/root/s007_qa",
          "model_requested": "gpt-6.1-sol",
          "model_effective": null,
          "dispatch_ref": "native-codex:S007:A1",
          "outcome": "running",
          "receipt_ref": null
        }
      ]
    },
    {
      "id": "S008",
      "title": "Siapkan runbook dan rehearsal migrasi Anugerah",
      "kind": "implementation",
      "responsible_role": "developer",
      "work_type": "ops",
      "deliverable_id": "SD2",
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §10",
        "docs/SAAS-RANCANGAN.md §12"
      ],
      "priority": "must",
      "difficulty": "medium",
      "risk": "high",
      "uncertainty": "medium",
      "triage_reason": "Aplikasi telah dipakai client; prosedur backup, backfill, verifikasi, dan rollback harus terbukti pada salinan sebelum produksi disentuh.",
      "model_hint": "gpt-6.1-sol",
      "model_reason": "Sol untuk prosedur migrasi data dan rollback dengan risiko kehilangan data.",
      "depends_on": {
        "hard": [
          "S007"
        ],
        "contracts": []
      },
      "write_scope": [
        "deploy/**",
        "docs/SAAS-MIGRATION-RUNBOOK.md",
        "tests/Feature/Tenancy/MigrationTest.php",
        "docs/evidence/SAAS/S008/**"
      ],
      "shared_resources": [
        {
          "name": "db:mysql-saas-migration",
          "mode": "exclusive"
        }
      ],
      "conflicts_with": [],
      "parallel_candidates": [],
      "acceptance_criteria": [
        "Runbook memakai alat backup/restore existing bila cukup dan tidak membuat sistem deployment baru.",
        "Salinan data baseline dapat dibackup, direstore, dimigrasikan ke tenant Anugerah, dan diverifikasi tanpa perubahan baris bisnis.",
        "Verifikasi mencakup jumlah baris, relasi, nomor, stok, total laporan, pengguna, dan file upload.",
        "Rollback memiliki langkah konkret dan tidak memakai migrate:fresh/reset produksi."
      ],
      "verification_plan": [
        "Rehearsal otomatis pada database khusus dari fixture representatif; bandingkan inventaris dan checksum sebelum/sesudah/restore.",
        "Review command untuk target eksplisit, redaksi secret, idempotensi aman, dan failure injection pada titik backfill."
      ],
      "status": "pending"
    },
    {
      "id": "S009",
      "title": "QA independen rehearsal backup, migrasi, dan rollback",
      "kind": "qa",
      "responsible_role": "tester",
      "work_type": "ops",
      "deliverable_id": "SD2",
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §10–§11"
      ],
      "priority": "must",
      "difficulty": "complex",
      "risk": "high",
      "uncertainty": "low",
      "triage_reason": "Developer tidak boleh menjadi satu-satunya pihak yang menyatakan prosedur penyelamatan data produksi berhasil.",
      "model_hint": "gpt-6.1-sol",
      "model_reason": "Sol untuk audit bukti migrasi/restore dan perbandingan data berisiko tinggi.",
      "depends_on": {
        "hard": [
          "S008"
        ],
        "contracts": []
      },
      "qa_for": "S008",
      "write_scope": [
        "docs/evidence/SAAS/S009/**"
      ],
      "shared_resources": [
        {
          "name": "db:mysql-saas-migration",
          "mode": "exclusive"
        }
      ],
      "conflicts_with": [],
      "parallel_candidates": [],
      "acceptance_criteria": [
        "Tester menjalankan backup, restore, migrate, verifikasi, dan rollback pada target non-produksi yang eksplisit.",
        "Seluruh baseline bisnis identik dan tenant_id Anugerah terisi benar setelah migrasi.",
        "Kegagalan terkontrol tidak meninggalkan target dalam keadaan yang diklaim sehat.",
        "Receipt menyebut hal yang belum diuji dan kebutuhan maintenance produksi."
      ],
      "verification_plan": [
        "Ulangi runbook dari awal pada database bersih, periksa checksum/inventaris/log teredaksi, lalu jalankan smoke fitur pada hasil restore dan hasil migrasi."
      ],
      "status": "pending"
    },
    {
      "id": "S010",
      "title": "Deploy dan migrasikan Anugerah ke schema tenant",
      "kind": "deployment",
      "responsible_role": "deployer",
      "work_type": "ops",
      "deliverable_id": "SD2",
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §10",
        "docs/SAAS-RANCANGAN.md §13"
      ],
      "priority": "must",
      "difficulty": "medium",
      "risk": "high",
      "uncertainty": "medium",
      "triage_reason": "Deployment mengubah schema dan data aplikasi yang sedang dipakai; otorisasi, backup offsite teruji, maintenance window, serta rollback wajib tersedia saat eksekusi.",
      "model_hint": "gpt-6.1-sol",
      "model_reason": "Sol untuk deployment production dan pengambilan keputusan aman saat migrasi gagal.",
      "depends_on": {
        "hard": [
          "S006",
          "S007",
          "S009"
        ],
        "contracts": []
      },
      "deployment": {
        "environment": "production",
        "source_task": "S006",
        "authorization_ref": "saas-production-2026-11-17"
      },
      "write_scope": [
        "docs/evidence/SAAS/S010/**"
      ],
      "shared_resources": [
        {
          "name": "branch:main",
          "mode": "exclusive"
        },
        {
          "name": "db:production",
          "mode": "exclusive"
        },
        {
          "name": "server:demo-repair-app",
          "mode": "exclusive"
        }
      ],
      "conflicts_with": [],
      "parallel_candidates": [],
      "acceptance_criteria": [
        "User memberi izin deployment/migrasi SaaS yang eksplisit dan lokasi backup offsite serta maintenance window tercatat.",
        "Backup database/upload produksi berhasil dan restore-nya sudah dibuktikan sebelum migration command berjalan.",
        "Commit yang dideploy sama dengan candidate S006 yang lulus S007 dan rehearsal S009.",
        "Migrasi selesai dengan inventaris data cocok; bila gate gagal, prosedur rollback dijalankan tanpa improvisasi destruktif."
      ],
      "verification_plan": [
        "Catat commit, target, backup receipt, migration output teredaksi, inventaris sebelum/sesudah, health check, dan keputusan rollback/lanjut."
      ],
      "status": "pending"
    },
    {
      "id": "S011",
      "title": "Verifikasi independen produksi setelah migrasi",
      "kind": "post_deploy",
      "responsible_role": "tester",
      "work_type": "ops",
      "deliverable_id": "SD2",
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §10–§13"
      ],
      "priority": "must",
      "difficulty": "medium",
      "risk": "high",
      "uncertainty": "low",
      "triage_reason": "Deployment sukses tidak membuktikan data Anugerah utuh atau seluruh alur menggunakan tenant yang benar.",
      "model_hint": "gpt-6.1-sol",
      "model_reason": "Sol untuk verifikasi produksi, data, dan auth setelah migrasi berisiko tinggi.",
      "depends_on": {
        "hard": [
          "S010"
        ],
        "contracts": []
      },
      "qa_for": "S010",
      "write_scope": [
        "docs/evidence/SAAS/S011/**"
      ],
      "shared_resources": [
        {
          "name": "db:production",
          "mode": "exclusive"
        },
        {
          "name": "server:demo-repair-app",
          "mode": "exclusive"
        }
      ],
      "conflicts_with": [],
      "parallel_candidates": [],
      "acceptance_criteria": [
        "Versi produksi cocok dengan commit yang diotorisasi dan health check lulus.",
        "Login, data lama, TTS, tracking, PDF/struk, stok, penjualan, dashboard, dan laporan Anugerah tetap benar.",
        "Jumlah baris dan total penting cocok dengan baseline; tidak ada data tanpa tenant atau tenant silang.",
        "Tidak ada client kedua dibuat sebelum verdict ini PASS."
      ],
      "verification_plan": [
        "Smoke read-only dahulu, lalu transaksi sintetis berizin dengan cleanup yang aman; bandingkan inventaris, laporan, log error, dan endpoint publik."
      ],
      "status": "pending"
    },
    {
      "id": "S012",
      "title": "Onboard dan verifikasi pilot client kedua",
      "kind": "post_deploy",
      "responsible_role": "tester",
      "work_type": "ops",
      "deliverable_id": "SD3",
      "prd_refs": [
        "docs/SAAS-RANCANGAN.md §8–§9",
        "docs/SAAS-RANCANGAN.md §13"
      ],
      "priority": "must",
      "difficulty": "medium",
      "risk": "high",
      "uncertainty": "medium",
      "triage_reason": "Pilot client nyata adalah bukti bahwa onboarding sederhana cukup dan isolasi produksi bekerja; data/credential client memerlukan otorisasi serta kanal privat.",
      "model_hint": "gpt-6.1-sol",
      "model_reason": "Sol untuk verifikasi produksi lintas dua tenant tanpa memperluas fitur.",
      "depends_on": {
        "hard": [
          "S010",
          "S011"
        ],
        "contracts": []
      },
      "qa_for": "S010",
      "write_scope": [
        "docs/evidence/SAAS/S012/**"
      ],
      "shared_resources": [
        {
          "name": "db:production",
          "mode": "exclusive"
        },
        {
          "name": "server:demo-repair-app",
          "mode": "exclusive"
        }
      ],
      "conflicts_with": [],
      "parallel_candidates": [],
      "acceptance_criteria": [
        "Client kedua dibuat melalui tenant:create tanpa repo, database, deployment, panel, atau fitur baru.",
        "Owner mengisi pengaturan, kategori toko HP, dan teknisi melalui layar existing.",
        "TTS, dokumen, tracking, stok, dan laporan kedua tenant terpisah pada browser/sesi berbeda.",
        "Feedback pilot dicatat sebagai bukti kebutuhan; ide baru masuk backlog dan tidak otomatis diimplementasikan."
      ],
      "verification_plan": [
        "Operator menjalankan onboarding dengan credential melalui kanal privat; tester menguji alur inti kedua tenant dan percobaan akses silang tanpa menyalin data customer ke evidence."
      ],
      "status": "pending"
    }
  ]
}
```

## Checkpoint dan definisi selesai

| Hasil | Gate |
|---|---|
| Candidate software multi-tenant diterima | S007 PASS pada revisi integrasi S006 |
| Migrasi Anugerah diterima | S011 PASS pada deployment S010 yang berizin |
| Lean SaaS terbukti untuk client baru | S012 PASS pada client kedua |

SaaS v1 belum selesai hanya karena schema atau scope tersedia. Selesai berarti data Anugerah utuh, dua tenant terbukti terisolasi, dan client kedua memakai fitur existing tanpa infrastruktur atau fitur spekulatif baru.

## Backlog setelah v1

Berikut hanya ide, bukan task dan bukan izin implementasi:

- subdomain/custom domain;
- username yang boleh sama antartoko;
- panel admin platform;
- paket, trial, billing, dan payment gateway;
- satu user mengelola beberapa toko;
- preset kategori otomatis;
- analitik lintas tenant.

Ide dipindahkan menjadi task hanya bila ada client nyata yang membutuhkan dan user menyetujui perubahan scope.

## Pertanyaan terbuka dan blocker

| ID | Pertanyaan/blocker | Dampak | Menghambat |
|---|---|---|---|
| Q-S03 | Tentukan lokasi backup offsite dan maintenance window | Wajib sebelum menyentuh data client | S010 |
| Q-S04 | Berikan identitas client kedua dan izin onboarding melalui kanal privat | Diperlukan untuk pilot nyata | S012 |

## Log keputusan

| Tanggal | Keputusan / perubahan scope | Alasan | Task terdampak | Sumber |
|---|---|---|---|---|
| 16 Nov 2026 | SaaS dikerjakan pada branch `codex/saas-multitenant`; main produksi tidak diubah saat perencanaan | Memisahkan pekerjaan SaaS dari aplikasi client aktif | Semua | User |
| 16 Nov 2026 | Gunakan Lean SaaS dan hindari fitur spekulatif | User menegaskan over-engineering sering menghasilkan fitur tidak perlu | Semua | User |
| 17 Nov 2026 | TODO dibuat sebagai rencana saja; execution tetap planning | User meminta TODO, bukan implementasi atau deployment | Semua | User |
| 17 Nov 2026 | Goal S001–S012 aktif; username unik global dan tracking lama untuk tenant nonaktif disetujui sesuai rekomendasi | User meminta mengerjakan semua TODO | S001–S012 | User |
