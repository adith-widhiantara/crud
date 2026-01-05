# Changelog

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