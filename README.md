# Research Collaboration Hub UIU

A research collaboration platform for United International University, where students and faculty
publish their research, find collaborators working on similar topics, and run research projects together.

Built with **HTML, CSS, JavaScript, PHP and MySQL** — no frameworks, no Composer, no CDN.
Everything runs offline on XAMPP.

> Course project — Web Development, 14th Trimester, United International University.

---

## Features

**Research profiles** — cover image, avatar, bio, department, CGPA, research interests and
skills with proficiency levels, plus a profile-completion indicator.

**Smart matchmaking** — ranks every researcher against you by shared research interests using
Jaccard similarity with a same-department bonus.

**Projects** — create a project with objectives, deadline and required skills. Each project has a
workspace with a milestone timeline, task pipeline, shared file library and team chat.

**Collaboration requests** — browse open projects, apply to join, and accept or decline applicants.

**Publications with version control** — upload papers, pre-prints, datasets and theses. Every
revision is kept: each version has its own file, change note and download link, so an old revision
stays citable. See [Paper versioning](#paper-versioning) below.

**Open repository** — search, filter by year and category, sort and paginate across all published work.

**Communities** — research groups with discussion threads and replies.

**Events** — seminars and workshops with RSVP and capacity limits.

**Messaging and notifications** — private one-to-one inbox and an activity feed.

**Admin panel** — manage users, projects, publications, communities and events, with
platform-wide statistics.

---

## Paper versioning

A research paper is not finished when it is first uploaded. It gets revised after supervisor
feedback, after peer review, and again for the camera-ready copy. If each upload overwrote the
last one, a citation would be ambiguous and the earlier file would be gone.

**How it works**

- The `publications` row always holds the **live** copy of a paper.
- Each revision is a row in `publication_versions`, holding that revision's title, abstract,
  its own uploaded file, and a note describing what changed.
- `publications.current_version` points at the newest version number.
- The version table is **append-only** — saving a revision never updates or deletes an older row.

**When a new version is created**

| Author action | Result |
|---|---|
| Uploads a paper for the first time | Version 1 |
| Edits and attaches a new file | Always a new version |
| Edits with "Save as a new version" ticked | New version |
| Edits with the box unticked and no new file | Updated in place, no new version |

Uploading a replacement file **never deletes the old file** — the previous version still points at
it, so an old revision stays downloadable. Deleting a paper removes every version's file together.

---

## Installation

You need **XAMPP** with PHP 8.0 or newer. Nothing else.

1. Copy this folder into `C:\xampp\htdocs\research-collaboration-hub\`
2. Start **Apache** and **MySQL** in the XAMPP Control Panel
3. Go to <http://localhost/phpmyadmin> → **Import** → choose `database/rch_uiu.sql` → **Go**
4. Open <http://localhost/research-collaboration-hub/>

The SQL file creates the database, builds all 29 tables and fills them with demo data.
You do not need to create the database by hand.

### Demo accounts

Password for every account is `password123`.

| Role | Email |
|---|---|
| Student | `mehrab@bscse.uiu.ac.bd` |
| Student | `tanjila@bscse.uiu.ac.bd` |
| Faculty | `rezwan.khan@cse.uiu.ac.bd` |
| Admin | `admin@rchuiu.edu` |

The faculty account owns a paper with a three-version history, for trying the versioning feature.

---

## Project structure

```
├── index.php ................ public landing page
├── login.php / register.php / logout.php / onboarding.php
├── dashboard.php ............ logged-in home
├── profile.php / profile_edit.php
├── matchmaking.php .......... interest-based collaborator matching
├── directory.php ............ browse all researchers
├── opportunities.php / opportunity.php
├── projects.php / project.php / project_form.php
├── requests.php ............. accept or decline join requests
├── publications.php / publication.php / publication_form.php
├── repository.php ........... open research repository
├── communities.php / community.php / post.php
├── events.php / messages.php / notifications.php
├── search.php / page.php / download.php
│
├── admin/ ................... admin panel
├── assets/
│   ├── css/style.css ........ design system
│   ├── js/main.js ........... sidebar, tabs, filters, validation
│   └── img/
├── config/
│   ├── config.php ........... credentials, paths, session bootstrap
│   └── database.php ......... PDO connection + query helpers
├── includes/
│   ├── header.php / footer.php
│   ├── functions.php ........ auth, CSRF, uploads, formatting, matching, versioning
│   └── icons.php ............ inline SVG icon set
├── database/
│   └── rch_uiu.sql .......... schema + demo data
└── uploads/ ................. avatars, covers, publications, project files
```

---

## How it is built

Each URL is a real PHP file. Every page follows the same shape: bootstrap, access guard,
handle POST, query, render.

```php
require_once __DIR__ . '/config/config.php';   // bootstrap
requireLogin();                                 // guard

if ($_SERVER['REQUEST_METHOD'] === 'POST') {    // handle the form
    verifyCsrf();
    // ... write ...
    redirect('thispage.php');                   // Post / Redirect / Get
}

$rows = fetchAll('SELECT ...', [$uid]);         // read
require_once ROOT_PATH . '/includes/header.php';
```

### Database

29 normalised InnoDB tables, all `utf8mb4`. Many-to-many relationships use junction tables with
composite primary keys, so duplicates are impossible at the database level. Every foreign key is
`ON DELETE CASCADE`, so deleting a user leaves no orphan rows.

### Security

| Threat | Control |
|---|---|
| SQL injection | PDO prepared statements everywhere, with `ATTR_EMULATE_PREPARES` set to `false`. No query concatenates user input. |
| Password theft | `password_hash()` / `password_verify()` (bcrypt, random per-password salt). |
| Cross-site scripting | `htmlspecialchars()` on every value that came from the database or a form. |
| Cross-site request forgery | A per-session token on every state-changing form, checked with `hash_equals()`. |
| Broken access control | `requireLogin()` and `requireRole()` guards, plus per-record ownership checks. |
| Malicious upload | Extension whitelist, size limit, randomised stored filename, and an `.htaccess` in `uploads/` that blocks execution. |
| Path disclosure | Downloads are proxied through `download.php`, which takes a database id and re-checks permission. |

---

## Notes

- `config/config.php` contains the default XAMPP credentials (`root`, empty password) so the
  project runs immediately after cloning. Change them if your MySQL has a password.
- Error display is on for development. Turn it off in `config/config.php` before deploying anywhere real.
- The seeded demo papers are database records with no real file attached, so their download links
  are inactive. Upload your own file and downloads work normally.

---

*United International University · Collaborate · Innovate · Impact*
