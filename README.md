# CSRF Online — PoC Builder

Cross-Site Request Forgery (CSRF) Proof-of-Concept generator in a single `index.php` file.
Rebuilt with a modern dark glassmorphism UI by **0xfndlabs**.

> ⚠️ **For authorized security testing only.** Use it on targets you own or have written
> authorization to test. Misuse outside that context is illegal and the user's responsibility.

---

## Features

- **Dynamic Request Builder** — add/remove *fields*, *file fields*, and *custom headers* rows without limit.
- **Method**: `POST`, `GET`, `PUT`, `PATCH`, `DELETE`.
- **Content-Type**: `multipart/form-data`, `application/x-www-form-urlencoded`, `application/json`.
- **Two PoC modes** (auto-selected):
  - **Classic form** — plain HTML `form` (auto-submit capable), most reliable for cross-origin CSRF because it is not restricted by CORS.
  - **Fetch/FormData** — used automatically when custom headers exist, Content-Type is `JSON`, or the method is `PUT/PATCH/DELETE`; sends cookies via `credentials: include`.
- **Options**:
  - *Auto-submit on load* (no file)
  - *Auto-submit when file is selected* (onchange)
  - Open in: new tab / same tab (classic form)
- **Result**: PoC opens in a new tab automatically (via AJAX, no reload), plus **Download .html**, **Copy HTML**, and **HTML Source** buttons.
- **Input security**: all output is `htmlspecialchars`-escaped, URL validated as `http/https` only, method/enctype/target whitelisted (free of XSS & open redirect).

---

## Running

Requirements: **PHP ≥ 7** (with built-in `filter` & `json` extensions).

```bash
# via PHP built-in server
php -S 0.0.0.0:8080

# then open
# http://localhost:8080
```

Or drop `index.php` into any PHP hosting / web root.

---

## Usage

1. Fill in **URL Target** (must be `http(s)://`).
2. Pick the **Method** and **Content-Type**.
3. Add **Fields** — body/query parameters (name + value).
4. Add **Upload Fields** — file field names matching the target (e.g. `file`, `Filedata`, `file[]`).
5. Optionally set **Custom Headers**, and toggle **auto-submit** behavior.
6. Click **Generate PoC**.

### Result flow

```
Generate PoC ──▶ (AJAX, no reload) ──▶ new tab with the PoC
                    │
                    └─ auto-submit ON? ──▶ confirm first before the tab fires
```

If the browser blocks the popup, use the **"Open in new tab"** button in the result card
(a real click is allowed by the browser), or **Download .html** to save it.

---

## Request Examples

| Scenario                    | Method | Content-Type | Fields            | Upload Fields |
|-----------------------------|--------|--------------|-------------------|---------------|
| File upload + token         | POST   | multipart    | `token=abc123`    | `file`        |
| Login form                  | POST   | urlencoded   | `user`, `pass`    | –             |
| Fetch-like XHR (token etc.) | POST   | json         | `{"name":"x"}`    | –             |
| Query API                   | GET    | urlencoded   | `q=foo`           | –             |
| REST + custom header        | PUT    | json         | `data`            | –             |

---

## Implementation Notes

| Part                | Description |
|---------------------|-------------|
| `valid_url()`       | Validates `http/https` scheme; rejects `javascript:` etc. |
| `po_classic()`      | Classic form PoC generator. |
| `po_fetch()`        | Fetch/FormData PoC generator. |
| `poc_style()`       | Glassmorphism theme for generated PoC pages. |
| AJAX `ajax=1`       | Server responds with `JSON {ok, poc, method, enctype, auto, autoChange}`. |
| Sanitization        | `htmlspecialchars(..., ENT_QUOTES)` on every echoed user input. |

### Browser limitations (not bugs)

- **Origin/Referer cannot be set** — those are *forbidden headers* in browsers.
- **Cross-origin fetch requires CORS**; classic form mode does not.
- **File inputs cannot be pre-filled** — users must select the file (browser security policy); use *auto-submit when file is selected*.
- File field names must match the target parameter (e.g. PHP `$_FILES['file']` ⇄ field `file`).

---

## Tech Stack

- PHP (no dependencies/Composer) — logic & PoC generation.
- Pure CSS (no Bootstrap/jQuery) — glassmorphism, responsive.
- Vanilla JS — dynamic builder & AJAX generation.

## Credits

Developed by **0xfndlabs**.