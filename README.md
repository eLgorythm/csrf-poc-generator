# CSRF Online — PoC Builder

Generator **Cross-Site Request Forgery (CSRF)** Proof-of-Concept dalam satu file `index.php`.
Dibangun ulang dengan UI *modern dark glassmorphism* oleh **0xfndlabs**.

> ⚠️ **Hanya untuk pengujian keamanan berizin.** Gunakan pada target yang kamu miliki atau
> punya otorisasi tertulis. Penyalahgunaan di luar konteks tersebut adalah ilegal dan tanggung
> jawab pengguna.

---

## Fitur

- **Request Builder dinamis** — tambah/hapus baris *fields*, *file fields*, dan *custom headers* tanpa batas.
- **Method**: `POST`, `GET`, `PUT`, `PATCH`, `DELETE`.
- **Content-Type**: `multipart/form-data`, `application/x-www-form-urlencoded`, `application/json`.
- **Dua mode PoC** (otomatis dipilih):
  - **Form klasik** — HTML `form` murni (bisa auto-submit on load), paling reliable untuk CSRF lintas-origin karena tidak dibatasi CORS.
  - **Fetch/FormData** — dipakai otomatis jika ada header custom, Content-Type `JSON`, atau method `PUT/PATCH/DELETE`; mengirim cookie via `credentials: include`.
- **Opsional**:
  - *Auto-submit on load* (tanpa file)
  - *Auto-submit saat file dipilih* (onchange)
  - Target buka: tab baru / tab sama (form klasik)
- **Hasil**: PoC otomatis dibuka di tab baru (via AJAX, tanpa reload), plus tombol **Download .html**, **Copy HTML**, dan **HTML Source**.
- **Keamanan input**: semua output di-`htmlspecialchars`, URL divalidasi hanya `http/https`, method/enctype/target di-whitelist (bebas XSS & open redirect).

---

## Cara Menjalankan

Persyaratan: **PHP ≥ 7** (dengan ekstensi `filter` & `json` — keduanya bawaan).

```bash
# via server bawaan PHP
php -S 0.0.0.0:8080

# lalu buka
# http://localhost:8080
```

Atau taruh `index.php` di hosting/`web root` PHP apa pun.

---

## Cara Pakai

1. Isi **URL Target** (harus `http(s)://`).
2. Pilih **Method** dan **Content-Type**.
3. Tambahkan **Fields** — parameter body/query (name + value).
4. Tambahkan **Upload Fields** — nama field file sesuai target (mis. `file`, `Filedata`, `file[]`).
5. Opsional: **Custom Headers**, dan hidupkan/matikan perilaku **auto-submit**.
6. Klik **Generate PoC**.

### Alur hasil

```
Generate PoC ──▶ (AJAX, tanpa reload) ──▶ tab baru berisi PoC
                      │
                      └─ auto-submit ON? ──▶ konfirmasi dulu sebelum tab dijalankan
```

Jika popup browser diblokir, gunakan tombol **"Buka di tab baru"** di kartu hasil
(klik nyata = diizinkan oleh browser), atau **Download .html** untuk disimpan.

---

## Contoh Request

| Skenario                    | Method | Content-Type | Fields            | Upload Fields |
|-----------------------------|--------|--------------|-------------------|---------------|
| Upload file + token         | POST   | multipart    | `token=abc123`    | `file`        |
| Login form                  | POST   | urlencoded   | `user`, `pass`    | –             |
| Fetch-like XHR (token dll)  | POST   | json         | `{"name":"x"}`    | –             |
| Query API                   | GET    | urlencoded   | `q=foo`           | –             |
| REST + custom header        | PUT    | json         | `data`            | –             |

---

## Detail Implementasi

| Bagian              | Keterangan |
|---------------------|------------|
| `valid_url()`       | Validasi skema `http/https`; menolak `javascript:`, dll. |
| `po_classic()`      | Generator PoC form klasik. |
| `po_fetch()`        | Generator PoC fetch/FormData. |
| `poc_style()`       | Tema glassmorphism untuk halaman PoC hasil generate. |
| AJAX `ajax=1`       | Server membalas `JSON {ok, poc, method, enctype, auto, autoChange}`. |
| Sanitasi            | `htmlspecialchars(..., ENT_QUOTES)` pada semua echo user input. |

### Batasan & perilaku browser (bukan bug)

- **Origin/Referer tidak bisa diset** — *forbidden header* pada browser.
- **Fetch lintas-origin** butuh CORS; mode form klasik tidak.
- **File tidak bisa diisi otomatis** — pemilih file wajib pilih file (kebijakan keamanan browser); gunakan *auto-submit saat file dipilih*.
- Kecocokan nama field file harus pas dengan nama parameter target `$_FILES` (mis. PHP `$_FILES['file']` ⇄ field `file`).

---

## Teknologi

- PHP (tanpa dependensi/Composer) — logika & generator PoC.
- CSS murni (tanpa Bootstrap/jQuery) — glassmorphism, responsive.
- JS vanilla — builder dinamis & AJAX generate.

## Kredit

Dikembangkan oleh **0xfndlabs**.