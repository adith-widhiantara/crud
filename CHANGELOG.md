# Changelog

## 1.2.0 | 2025-01-06

### Added

- **Filtering System**: Menambahkan method `filterableColumns()` pada `CrudModel` untuk whitelist kolom yang aman
  difilter via query string (support exact match, `null`, `!null`, dan array `whereIn`).
- **Advanced Search**: Menambahkan method `searchableColumns()` pada `CrudModel` untuk pencarian global fuzzy (
  `LIKE %...%`).
- **Nested Relation Search**: Fitur search mendukung dot notation (contoh: `category.name`, `posts.comments.body`) untuk
  mencari data di dalam relasi.
- **Query Hook**: Menambahkan method `extendQuery(Builder $query)` pada `BaseCrudService` untuk mempermudah modifikasi
  query (seperti scoping atau sorting custom) tanpa meng-override `getAll`.
- **API Parameters**: Endpoint `index` sekarang menerima parameter `filter[...]` dan `search`.

### Changed

- Method `getAll` pada `BaseCrudService` dan `BaseCrudController` kini menerima argumen tambahan `$filter` dan
  `$search`.

## 1.1.0 | 2025-01-05

### Added

- **Auto Load Relations**: Menambahkan dukungan untuk memuat relasi secara otomatis menggunakan *dot-notation* di dalam
  method `getShowOnListColumns()`.
    - Contoh: `'category.name'` akan otomatis menjalankan `with('category:id,name')`.
    - Sistem secara cerdas menyuntikkan `id` pada partial select relasi untuk memastikan *hydration model* berjalan
      sukses.

### Changed

- **BREAKING CHANGE** pada `BaseCrudService`:
    - Menghapus fallback implisit ke `SELECT *` saat `getShowOnListColumns()` mengembalikan array kosong atau hanya
      kolom relasi.
    - **Strict Mode**: Kini akan melempar `InvalidArgumentException` jika tidak ada kolom lokal (tabel utama) yang
      didefinisikan secara eksplisit. Hal ini untuk mencegah *over-fetching* (query select *) yang tidak disengaja dan
      memaksakan *conscious decision* dari developer terkait data yang diambil.