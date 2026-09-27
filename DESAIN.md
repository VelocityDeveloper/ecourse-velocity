# DESAIN.md — Design Rules

Design rules for **ecourse-velocity**, taken from the existing design system and applied to the homepage (`resources/js/pages/Welcome.vue`). New pages should follow them. **Do not add a new UI framework**: everything here uses Tailwind CSS v4, shadcn-vue (`resources/js/components/ui`) and the tokens in `resources/css/app.css`.

---

## 0. Brand & logo

- **Mark:** a check-shaped "V" (velocity, plus a finished lesson) followed by three speed lines on its left. Source: `components/AppLogoIcon.vue` (viewBox `0 0 40 40`). The SVG is drawn in `currentColor`, so its color **always** comes from the parent's text class; never give it a color of its own.
- **Wordmark:** `components/AppWordmark.vue` renders **"ecourse velocity"** in lowercase: `ecourse` `font-medium opacity-60` + `velocity` `font-semibold`, `tracking-tight`. Don't retype the text by hand; use the component.
- **Logo lockup:** the mark inside a tile `size-8 rounded-md bg-primary text-primary-foreground` (sidebar: `bg-sidebar-primary`) with an icon of `size-5`, followed by the wordmark with a `gap-2`. A small version (footer) uses a `size-6` tile + `size-4` icon.
- **Admin-uploaded logo:** an admin can replace the logo at **Admin → Pengaturan Situs** (`/admin/settings`, PNG/JPG/WebP up to 2 MB; SVG is refused because it could carry script). Always render the logo through `components/SiteLogo.vue` (`size` sm/md/lg, `tile` primary/sidebar/none, `wordmark`): it shows the uploaded image (shared prop `branding.logoUrl`) in place of the whole lockup, or the built-in mark + wordmark when none is set. Never use `AppLogoIcon` directly for the site logo.
- **Minimum size:** icon 16px (favicon). Below 24px use the mark without the wordmark.
- **Favicon/app icons** (`public/favicon.svg`, `favicon.ico`, `apple-touch-icon.png`): the white mark (`#ffffff`) on a `#cb450b` tile (the light-mode `--primary` value, brand orange), corner radius 9/40. The hex values are allowed **only** in these static files. If the mark changes, update all three from the same geometry.
- The product name for titles/emails comes from `APP_NAME` (`Ecourse Velocity`) via `page.props.name`.

## 1. Foundations

### Colors — use tokens only

The source of truth is the CSS variables in `resources/css/app.css` (`:root` for light, `.dark` for dark). **Never write hex colors or Tailwind palette colors** (`#f53003`, `bg-neutral-100`, `text-gray-500`, …) in new pages; use semantic tokens so dark mode works automatically.

| Purpose                       | Class                                                                                                                                      |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| Page background               | `bg-background text-foreground`                                                                                                            |
| Card / panel                  | `bg-card text-card-foreground border`                                                                                                      |
| Subtle section background     | `bg-muted/30` (section), `bg-muted` (placeholder/track)                                                                                    |
| Secondary text                | `text-muted-foreground`                                                                                                                    |
| Main action / strong emphasis | `bg-primary text-primary-foreground`                                                                                                       |
| Hover on a clickable card     | `hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-lg hover:shadow-primary/10` (public pages); `hover:bg-muted/40` for list rows |
| Error / destructive action    | `text-destructive`, `variant="destructive"`                                                                                                |
| Borders                       | `border` (automatically `--border`)                                                                                                        |

**Admin-picked colour.** Admin → Pengaturan Situs can replace the orange with any colour. `App\Support\BrandPalette` derives `--primary`, `--primary-foreground`, `--primary-deep`, `--brand`, `--accent(-foreground)`, `--ring` and `--sidebar-primary(-foreground)` for light and dark (text on primary is kept at ≥ 4.5:1), rendered in `<style id="brand-palette">` after app.css and kept in sync by `lib/brandPalette.ts`. This works only because pages use the tokens below: a hard-coded colour will not follow the admin's choice. The seeded course thumbnails are images and stay orange.

**Brand colour: orange.** Surfaces stay neutral (white / near-black, grey borders); orange is carried by the `primary` token, so every button, progress bar, focus ring, default badge and logo tile picks it up automatically.

| Token                              | Light                   | Dark                     | Use                                                                                                                                     |
| ---------------------------------- | ----------------------- | ------------------------ | --------------------------------------------------------------------------------------------------------------------------------------- |
| `--primary`                        | `hsl(18 90% 42%)`       | `hsl(24 95% 53%)`        | Main actions, links, progress, active states, eyebrow text                                                                              |
| `--primary-foreground`             | white                   | `hsl(20 40% 8%)`         | Text on `bg-primary` (contrast ≥ 4.7:1 in both themes)                                                                                  |
| `--primary-deep`                   | `hsl(12 85% 34%)`       | `hsl(15 85% 40%)`        | Only as the end of a gradient (`from-primary to-primary-deep`)                                                                          |
| `--brand`                          | `hsl(24 95% 53%)`       | same                     | **Decorative only** (glows, gradient text, gradient bars); never for body text or text on a white background, since contrast is too low |
| `--accent` / `--accent-foreground` | warm tint / dark orange | dark warm / light orange | Hover and active states in menus, ghost buttons, mobile navigation                                                                      |

- Orange tints: `bg-primary/10 text-primary` for icon chips and eyebrow badges, `border-primary/30`, and `shadow-primary/10`–`/25` for "lifted" elements. Never introduce Tailwind palette oranges (`bg-orange-500`); always go through the tokens.
- `destructive` (red) stays reserved for dangerous actions; do not swap it for orange.

### Accent colors

Use only the `chart-1` … `chart-5` tokens, as a **soft icon chip**:

```html
<span
    class="flex size-10 items-center justify-center rounded-lg bg-chart-2/10 text-chart-2"
>
    <Layers class="h-5 w-5" />
</span>
```

- Background opacity `/10` (up to `/15` for yellow `chart-4`); the icon/text uses the solid color.
- For lists (categories, steps), cycle through the accents in order using a static class array (`ACCENTS` in `Welcome.vue`) so Tailwind can detect the classes. Do not build class names from strings (`` `bg-chart-${n}` ``).
- Never use accent colors for body text or large backgrounds.

### Typography

- Font: **Instrument Sans** (weights 400/500/600, loaded in `vite.config.ts`). Do not add other fonts.
- Maximum weight is `font-semibold` (600). Do not use `font-bold`.

| Element                               | Class                                                            |
| ------------------------------------- | ---------------------------------------------------------------- |
| Hero H1                               | `text-4xl sm:text-5xl font-semibold tracking-tight text-balance` |
| Section H2                            | `text-2xl sm:text-3xl font-semibold tracking-tight`              |
| Eyebrow (above H2/H1 on public pages) | `text-sm font-semibold text-primary`                             |
| Card title (H3)                       | `font-semibold` (add `line-clamp-2` for titles that may be long) |
| Lead paragraph                        | `text-lg text-muted-foreground text-pretty`                      |
| Body / description                    | `text-sm text-muted-foreground`                                  |
| Statistic figure                      | `text-2xl font-semibold tracking-tight`                          |
| Inside the app (dashboard)            | `<Heading>` component (`variant="small"` for list pages)         |

Use one H1 per page and don't skip heading levels.

### Radius, shadow, borders

- `rounded-lg` for cards, panels, inputs and icon chips; `rounded-xl` only for large "hero" elements (product preview, CTA banner); `rounded-full` for avatars, progress bars and dots.
- Shadow: regular cards use `border` with no shadow at rest. On public pages, "lifted" elements (hero preview, CTA, hovered cards, the main search button) may use `shadow-lg`/`shadow-xl` tinted `shadow-primary/10`–`/25`. Dashboard pages stay flat.

### Icons

- Only `@lucide/vue`.
- Sizes: `h-4 w-4` inside buttons/text, `h-5 w-5` inside icon chips, `size-8`/`size-10` for empty states.
- Icon + text in a button: `<Icon class="ml-1 h-4 w-4" />` after the text (arrows) or `mr-2` before the text.

---

## 2. Layout

- **Public page container:** `mx-auto w-full max-w-6xl px-4 sm:px-6`.
- **Vertical section padding:** `py-16 lg:py-20` (hero: `py-16 lg:py-24`).
- **Section rhythm:** alternate plain background ↔ `border-y bg-muted/30` so sections are easy to tell apart without extra color.
- **Section heading:** eyebrow + H2 in `max-w-2xl space-y-2`, with `mb-8` (or `mb-10` when centered). A "Lihat semua" action goes on the right using `flex flex-wrap items-end justify-between`.
- **Card grids:** `grid gap-4 sm:grid-cols-2 lg:grid-cols-3` (courses) or `lg:grid-cols-4` (categories, instructors). Fixed `gap-4`.
- **Anchor sections:** give them an `id` and `scroll-mt-20` so they are not hidden under the sticky header.
- **App pages** (after login) keep using `AppLayout` (sidebar) with the root `flex flex-col space-y-6` pattern. Public pages without a layout (like `Welcome`) must set `bg-background text-foreground` on the root themselves.
- Vue components must have **one root element**; put `<Head>` inside that root.

### Public site vs dashboard

- **Dashboard** (`AppLayout`, sidebar) is only for **admins and instructors**. The `staff` middleware sends students back to the homepage on GET requests and rejects other methods with 403. Every new management route goes inside a `['auth', 'staff']` group.
- **Public site** (`layouts/PublicLayout.vue` = `PublicHeader` + page + `PublicFooter`) is for guests and students: `Welcome`, `catalog/*`, `my-courses/*`, `users/Show`. Register the layout mapping in `resources/js/app.ts`; pages should not build their own header or footer.
- **Settings** use `AccountLayout`: staff see the dashboard, students see the public site.
- Public page container: `mx-auto w-full max-w-6xl px-4 py-10 sm:px-6` (reading pages such as profiles: `max-w-4xl`). Public pages have no breadcrumbs.
- Public page header: eyebrow `text-sm font-semibold text-primary` + H1 `text-3xl font-semibold tracking-tight` + description `text-muted-foreground` in `max-w-2xl space-y-2`. Dashboard pages keep using `<Heading variant="small">`.
- Actions that require an account (such as enrolling) are shown to guests as **"Masuk untuk mendaftar"** + a "Buat akun gratis" link. The server stores the intended URL so the user comes back to the same page after logging in.

### Learning space (`learn/*`)

- Only for students with an **active** enrollment (plus admins/instructors who own the course, as a preview). Access: `CoursePolicy::learn`. Anyone else is sent to `catalog.show` with a toast.
- Layout: `components/LearnShell.vue` = outline on the left (`LearnOutline`, `300px`, sticky `top-24`) + content. This is the one exception to the container rule: `max-w-7xl`. Below `lg` the outline moves into a `Sheet` opened with the "Isi kursus · N%" button.
- Progress: a `h-2 rounded-full bg-muted` bar with a `bg-primary` fill, always with `role="progressbar"` + `aria-valuenow`. Completed items use a `CircleCheck text-primary` icon.
- Lesson: section eyebrow + H1 `text-3xl`, video `iframe` `aspect-video` inside `rounded-xl border` (YouTube/Vimeo only, via `ResolveVideoEmbed`; other hosts get a link card), articles use `.prose-editor` inside a card, attachments are downloaded through `learn.attachments.download` (never the file's public URL). The action bar at the bottom is `rounded-lg border bg-muted/30 p-4`, followed by `LearnPager`.
- Quiz attempt: narrow `max-w-3xl` page, a sticky bar under the header with an `h-4` timer (`font-mono tabular-nums`, turns `text-destructive` once 60 seconds or less remain), one question per `fieldset` card, and the chosen option marked with `border-primary bg-primary/5`. Results: correct options `border-primary bg-primary/5` + `Check`, wrong picks `border-destructive bg-destructive/5` + `X`.

### Student learning area (`learning/*`, `my-courses`)

- Every page in this area opens with the standard public page header, followed by `components/LearningNav.vue`: tabs **Overview · My Courses · Notes · Bookmarks** (`border-b`, the active tab gets `border-b-2 border-primary font-medium`, and the row scrolls horizontally on mobile).
- The "Lanjutkan belajar" (resume) card: `rounded-xl border` with the thumbnail on the left (`md:grid-cols-[280px_1fr]`), a progress bar, and a **Lanjutkan** button pointing at the last lesson opened.
- Statistics tiles use the same pattern as the homepage: an accent icon chip (`chart-*`) + `text-2xl font-semibold` figure + `text-sm text-muted-foreground` label.
- **Rating stars** (`StarRating`, `StarRatingInput`) are the only place a filled accent colour is allowed: `fill-current text-rating` (a gold `--rating` token, the same in both themes; `chart-4` turned purple in dark mode), empty stars `text-muted-foreground/40`. Show the rating as `4.5 ★★★★☆ (12)`; do not show stars when there are no reviews yet.
- **Discussion** (`LessonDiscussion`): each question is a card with an `Avatar size-8`, name, `Instructor` badge (`secondary`) for course staff, and a relative time. Replies are indented `ml-11 border-l pl-4`. Deleting always goes through a `Dialog` confirmation (never `window.confirm`).
- **Personal notes** (`LessonNotes`): a card with the label "Hanya Anda yang bisa melihatnya"; the save button stays disabled until something changes.
- Gradebook (`courses/Grades`, "Buku Nilai"): quiz grade = best attempt %, final grade = weighted average (quiz `weight`, untaken = 0), letter from `App\Support\GradeScale`, pass = final ≥ course `passing_grade`; quiz KKM = `passing_score`. Pass/fail badges use `passStatusLabel` → **Lulus / Belum lulus** everywhere (dashboard, CSV and public site). Other dashboard statuses stay English; statuses students see on the public site are Indonesian. CSV export is `;`-separated with a UTF-8 BOM for Excel.
- Certificates: earned at 100% progress + final grade ≥ course `passing_grade` (no final grade → progress alone). Students claim them on My Courses ("Ambil sertifikat" → `certificates/Show`, public verification page `/sertifikat/{code}`, PublicLayout); the PDF (`resources/views/certificates/pdf.blade.php`, dompdf, A4 landscape, site colours + QR to the verification page) downloads only for the owner and course staff. Name/course/grade are snapshotted at issue.
- Payments (manual): paid courses (`Course::isPaid()`) are bought, not self-enrolled. "Beli kursus" → checkout page (`orders/Checkout`, pick bank transfer or QRIS; nothing is created yet) → "Buat pesanan" creates the invoice (`Order`, number `INV-YYYYMMDD-XXXXX`, total = price, no unique code, deadline from Pengaturan Pembayaran) → invoice page (`orders/Show`, printable) → separate proof page (`orders/Proof`, `/orders/{number}/konfirmasi-pembayaran`, file on the private `local` disk) → admin confirms in Penjualan → Pesanan (creates a `Transaction`, enrolls the student) or rejects with a reason (back to pending). Unpaid orders expire (`orders:expire`, every 15 min + lazily on read). Order statuses: English on the dashboard (`orderStatusLabel`), Indonesian on the public site (`publicOrderStatusLabel`).
- Staff monitoring pages (`courses/Progress`, `courses/ProgressStudent`) stay in the dashboard (`AppLayout` + `<Heading variant="small">`), with progress per student shown as an `h-2` bar + a `tabular-nums` percentage.

### Public header

- `sticky top-0 z-40 border-b bg-background/80 backdrop-blur`, height `h-16`.
- Left: logo (`AppLogoIcon` in a `size-8 rounded-md bg-primary text-primary-foreground` box) + app name (`page.props.name`).
- Middle: anchor links `text-sm text-muted-foreground hover:text-foreground`, hidden below `md`.
- Right: auth actions (no theme toggle: the public site is always light): guests get **Log in** (`ghost`) + **Register** (`default`, only when the shared `canRegister` prop is true); signed-in users get **Dashboard** (staff) or **My Courses** (`outline`, students) plus an avatar that opens `UserMenuContent`.
- Below `md` the links move into a `Sheet` (side `left`) opened by a `Menu` button. Source: `components/PublicHeader.vue`.

### Footer

- `border-t`, `py-8 text-sm text-muted-foreground`; small logo + `© {year} {appName}` on the left, links on the right; stacks vertically on mobile.

---

## 3. Components (reuse, don't copy)

| Need                     | Component                                                                                                                              |
| ------------------------ | -------------------------------------------------------------------------------------------------------------------------------------- |
| Buttons                  | `Button` — `default` for the main action, `outline` for secondary, `ghost` for tertiary/navigation, `secondary` on top of `bg-primary` |
| Labels/status            | `Badge` — `outline` for level/eyebrow, `secondary` for counts, `default` for positive states (Enrolled)                                |
| Course card              | `components/CourseCard.vue` (catalog & homepage). Data shape: type `CatalogCourse`                                                     |
| Avatar                   | `Avatar` + `AvatarImage` (only when `avatar` is present) + `AvatarFallback` with `getInitials()`                                       |
| Theme (dashboard only)   | `AppearanceTabs` in Settings → Tampilan (staff only; students have no Tampilan tab)                                                    |
| Enrollment cancel dialog | `components/CancelEnrollmentDialog.vue`                                                                                                |
| Form inputs              | `Input`, `Textarea`, `Select`, `Label`, `InputError`                                                                                   |
| Formatting               | `@/lib/course`: `formatPrice`, `formatDate`, `levelLabel`, `formatTimeLimit`, `enrollmentStatusLabel`                                  |
| Links/URLs               | Wayfinder (`@/routes/...`), never hard-coded URLs in new code                                                                          |

### Card pattern

```html
<link
    class="group flex flex-col gap-3 rounded-lg border bg-card p-5 text-card-foreground transition-all hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-lg hover:shadow-primary/10"
/>
```

- The card title may turn `group-hover:text-primary`; prices in course cards use `text-primary`.
- Clickable cards: the whole card is the `Link`, with `group` for child hover effects (`group-hover:translate-x-0.5` on an arrow, `group-hover:scale-[1.02]` on a thumbnail).
- Push the card footer to the bottom with `mt-auto` so heights in a grid stay aligned.
- Thumbnails are `aspect-video object-cover`; with no image, use a `bg-muted text-muted-foreground` placeholder with a `BookOpen` icon.

### Homepage banners (editable)

The hero texts (badge, title, highlighted part, description), the optional hero image (replaces the product-preview illustration, `aspect-[4/3] rounded-xl border object-cover`) and the guest CTA banner texts come from the `banner` prop (`SiteSetting::banner()`, defaults in `SiteSetting::BANNER_DEFAULTS`). Never hard-code them in `Welcome.vue` again.

### CTA banner

`rounded-xl bg-gradient-to-br from-primary to-primary-deep px-6 py-12 text-center text-primary-foreground shadow-xl shadow-primary/20`, secondary text `text-primary-foreground/80`, main button `variant="secondary" size="lg"`. The copy adapts to whether the user is logged in.

### Decorative background (optional, hero/CTA only)

- Hero: `bg-gradient-to-b from-primary/5 via-background to-background` plus up to two blurred glows (`rounded-full bg-brand/25 blur-3xl` and `bg-chart-4/20 blur-3xl`), `aria-hidden` and `pointer-events-none`. Gradient text for the key phrase in the H1: `bg-gradient-to-r from-primary to-brand bg-clip-text text-transparent` (big headings only).
  A dot pattern from tokens, masked so it fades out:

```html
<div
    aria-hidden="true"
    class="pointer-events-none absolute inset-0
  [background-image:radial-gradient(var(--border)_1px,transparent_1px)]
  [background-size:24px_24px]
  [mask-image:radial-gradient(ellipse_at_top,black_30%,transparent_75%)]"
/>
```

On a `bg-primary` background use `currentColor` + `opacity-20`. Do not use images or gradients with hard-coded colors.

---

## 4. States & content

- **Empty state:** `rounded-lg border border-dashed p-10 text-center` + a muted icon + short heading + one line of explanation. Optional sections (categories, instructors) are **hidden** when they have no data; the main section (courses) shows an empty state.
- **Numbers:** format with `Intl.NumberFormat('id-ID')`; prices with `formatPrice` (0 → "Free").
- **Illustrations** built from UI (such as the "Lanjutkan belajar" card in the hero) must be `aria-hidden="true"`.
- **UI copy is in Indonesian** (`APP_LOCALE=id`): address the user as **"Anda"**, keep it short and action-oriented ("Lihat semua kursus", "Daftar sekarang"), and use no "(s)" plurals ("3 kursus"). Use the glossary in section 6 so the same thing always has the same name.
- Shared labels live in `@/lib/course` (`levelLabel` → Pemula/Menengah/Lanjutan, `statusLabel` + `enrollmentStatusLabel` (status badges stay **English**: Draft, Published, Active, Cancelled…), `formatPrice` → "Gratis", `optionLabel` for the stored True/False options); don't repeat them in pages.
- Server messages (flash toasts, validation, auth, emails) go through `__()` with the Indonesian line in `lang/id.json`, or in `lang/id/*.php` for framework groups. `tests/Feature/IndonesianTranslationTest.php` fails when a `__()` string has no Indonesian line. Tests run with `APP_LOCALE=en` (see `phpunit.xml`), so assertions stay on the English keys.

## 5. Accessibility & responsiveness

- Mobile-first: one column first, then `sm:` → `md:` → `lg:`. Nothing may scroll horizontally at 360px.
- Icon-only buttons must have an `aria-label`. Search forms use `role="search"` and inputs have an `aria-label` when there is no visible label.
- Make sure text contrast holds in **light and dark** mode; semantic tokens handle this, and hard-coded colors break it.
- **Dark mode is dashboard-only.** The public site (home, catalogue, course pages, learning space, auth, a student's settings) is always light, whatever the saved preference or OS setting. `App\Support\ThemeScope` decides per page component for the first render (`data-theme-scope` on `<html>` in app.blade.php); on client-side visits the layouts call `setDarkModeAllowed()` (`PublicLayout`/`AuthLayout` → false, `AppSidebarLayout`/`AppHeaderLayout` → true). Keep ThemeScope in step with the layout mapping in `resources/js/app.ts`.
- Use `transition-colors` for hover; avoid other animations except small transforms (`scale-[1.02]`, `translate-x-0.5`).

---

## 6. Glossary (EN → ID)

The fixed Indonesian names for UI terms. The same concept always uses the same word.

| EN                                            | ID                                             |
| --------------------------------------------- | ---------------------------------------------- |
| Course / Courses                              | Kursus                                         |
| Lesson(s)                                     | Materi                                         |
| Section(s)                                    | Bab                                            |
| Quiz / Quizzes                                | Kuis                                           |
| Question(s)                                   | Soal (di kuis) / Pertanyaan (di diskusi)       |
| Option / Answer                               | Pilihan / Jawaban                              |
| Attempt                                       | Percobaan                                      |
| Score / Passing score                         | Nilai / Nilai lulus                            |
| Time limit                                    | Batas waktu                                    |
| Enroll / Enroll now                           | Daftar / Daftar sekarang                       |
| Enrolled                                      | Terdaftar                                      |
| Enrollment(s)                                 | Pendaftaran                                    |
| Cancel enrollment                             | Batalkan pendaftaran                           |
| Log in to enroll                              | Masuk untuk mendaftar                          |
| Instructor(s)                                 | Instruktur                                     |
| Student(s)                                    | Siswa                                          |
| Catalog                                       | Katalog                                        |
| Category / Categories                         | Kategori                                       |
| My Courses                                    | Kursus Saya                                    |
| My Learning / My learning                     | Belajar Saya                                   |
| Overview                                      | Ringkasan                                      |
| Notes                                         | Catatan                                        |
| Bookmarks / Bookmark                          | Markah / Tandai                                |
| Continue learning / Resume                    | Lanjutkan belajar / Lanjutkan                  |
| Progress                                      | Progres                                        |
| Completed / Mark as complete                  | Selesai / Tandai selesai                       |
| Attachments                                   | Lampiran                                       |
| Discussion / Reply                            | Diskusi / Balasan                              |
| Review(s) / Rating                            | Ulasan / Penilaian                             |
| No reviews yet                                | Belum ada ulasan                               |
| Free                                          | Gratis                                         |
| Price                                         | Harga                                          |
| Level                                         | Tingkat                                        |
| Beginner / Intermediate / Advanced            | Pemula / Menengah / Lanjutan                   |
| Draft / Pending Review / Published / Archived | Draft / Pending Review / Published / Archived (status badges stay English) |
| Status                                        | Status                                         |
| Actions                                       | Aksi                                           |
| Users                                         | Pengguna                                       |
| Role                                          | Peran                                          |
| Settings / Profile / Security / Appearance    | Pengaturan / Profil / Keamanan / Tampilan      |
| Light / Dark / System                         | Terang / Gelap / Sistem                        |
| Log in / Register / Log out                   | Masuk / Daftar / Keluar                        |
| Create a free account                         | Buat akun gratis                               |
| Forgot password? / Reset password             | Lupa kata sandi? / Atur ulang kata sandi       |
| Password / Confirm password                   | Kata sandi / Konfirmasi kata sandi             |
| Remember me                                   | Ingat saya                                     |
| Name / Email address                          | Nama / Alamat email                            |
| Search / Search courses...                    | Cari / Cari kursus...                          |
| All Categories / All Levels / All Statuses    | Semua Kategori / Semua Tingkat / Semua Status  |
| Save / Saving... / Saved.                     | Simpan / Menyimpan... / Tersimpan.             |
| Cancel                                        | Batal                                          |
| Delete / Remove                               | Hapus                                          |
| Edit                                          | Ubah (tombol) / "Ubah Kursus" (judul)          |
| Create / Add                                  | Buat / Tambah                                  |
| Back                                          | Kembali                                        |
| View / View all                               | Lihat / Lihat semua                            |
| Previous / Next                               | Sebelumnya / Berikutnya                        |
| Page 1 of 2                                   | Halaman 1 dari 2                               |
| Showing 1 to 10 of 18 courses                 | Menampilkan 1–10 dari 18 kursus                |
| Submit                                        | Kirim                                          |
| Upload                                        | Unggah                                         |
| Thumbnail                                     | Gambar sampul                                  |
| Description                                   | Deskripsi                                      |
| Title                                         | Judul                                          |
| Duration / min                                | Durasi / mnt                                   |
| Last active / Last opened                     | Terakhir aktif / Terakhir dibuka               |
| Home                                          | Beranda                                        |
| How it works                                  | Cara kerja                                     |
| View Site                                     | Lihat Situs                                    |
| Repository / Documentation                    | Repositori / Dokumentasi                       |
| Are you sure...?                              | Yakin ingin ...?                               |
| This cannot be undone.                        | Tindakan ini tidak bisa dibatalkan.            |

## 7. Public site look (after kursussipil.id, 2026-09-26)

The public site follows the look of kursussipil.id. These rules override the older ones above where they conflict:

- **Dark band:** `PublicHeader` (solid, sticky), the homepage hero, the learning-path cards and `PublicFooter` use the `surface` tokens (`bg-surface text-surface-foreground`, borders/tiles `surface-muted`, translucent pieces `bg-surface-foreground/5–10`). Admins can change the surface colour at Pengaturan Situs → Warna; text on it is always white.
- **Container:** public pages (and the learning space) use `max-w-site`: 1200px of content plus `px-6` (token `--container-site` in app.css). The dashboard keeps `max-w-7xl`.
- **Weights:** public headings may use `font-bold`/`font-extrabold` (Instrument Sans 700/800 are loaded). Hero H1 `text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.1]`; section H2 `text-3xl sm:text-4xl font-extrabold`. The dashboard keeps `<Heading>` and `font-semibold`.
- **Learning-path cards** ("Belajar Sesuai Bidang"): a category's optional image (Kursus → Kategori → Gambar latar, `components/CategoryImageField.vue`) fills the card under `from-surface via-surface/80 to-surface/20` plus a bottom fade, so white text stays readable; without one the card keeps the glow + dot pattern.
- **Section heading:** `components/SectionHeading.vue` (centered pill eyebrow in `font-mono uppercase tracking-widest`, H2, description). Sections alternate plain ↔ `border-y bg-muted/40`.
- **Cards:** `rounded-2xl border bg-card shadow-sm`, hover `-translate-y-1 shadow-xl shadow-primary/10`. Course cards: level pill on the image, category eyebrow, bold title, summary, instructor, stars, big price + "Detail →".
- **Buttons on public pages:** `rounded-xl font-bold`.
- **Promo slider:** `components/BannerSlider.vue`, fed by `banners` (Pengaturan Situs → Banner Promo), 3:1 images, autoplay 5 s (off with reduced motion), arrows + dots.
- **Footer:** four columns (logo + description + social icons, two link columns, "Hubungi Kami") from the shared `site` prop (Pengaturan Situs → Kontak & Footer). Empty contacts are hidden. Brand icons come from `components/SocialIcon.vue` (lucide v1 has none).
- **Testimonials:** "Cerita Sukses Alumni" comes from Pengaturan Situs → Testimoni (name, optional masking "N***a P***i", institution, quote, rating, photo); until one is active it falls back to the latest 4–5★ course reviews. `components/TestimonialCarousel.vue` shows whole cards inside the 1200px container: 3 per view on large screens, 2 on tablets, 1 on phones; it advances every 6 s (paused on hover/focus, off with reduced motion), with arrows, dots and swipe; each card a `components/TestimonialCard.vue`. Never seed invented testimonials: they must come from real alumni.
- **Phones:** the homepage shows 4 featured courses (the rest from "Lihat semua kursus") and the testimonials as a swipeable row.
