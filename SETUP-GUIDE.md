# Research Collaboration Hub UIU — Setup Guide

A full-stack research collaboration platform built with **HTML, CSS, JavaScript, PHP and MySQL**.
Runs on XAMPP. No frameworks, no Composer, no internet connection needed.

---

## Part 1 — Install

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy everything in this folder into `C:\xampp\htdocs\research-collaboration-hub\`.
3. Open **http://localhost/phpmyadmin** → **Import** → choose `database/rch_uiu.sql` → **Go**.
   This creates the database `rch_uiu`, builds all 29 tables and fills them with demo data.
4. Open **http://localhost/research-collaboration-hub/**

> The SQL file drops and recreates the `rch_uiu` database, so importing it again resets everything.

### Demo accounts

Password for every account is `password123`.

| Role    | Email                       | Use for                                   |
|---------|-----------------------------|-------------------------------------------|
| Student | `mehrab@bscse.uiu.ac.bd`    | Normal student view                       |
| Student | `tanjila@bscse.uiu.ac.bd`   | Second account, for testing messaging      |
| Faculty | `rezwan.khan@cse.uiu.ac.bd` | Owns projects and papers; owns the paper with 3 versions |
| Admin   | `admin@rchuiu.edu`          | Admin panel                               |

---

## Part 2 — Modules

### Public
- Landing page with live statistics, trending topics and open opportunities
- Register, log in, and a "choose your researcher type" onboarding step
- Policy pages — ethical guidelines, peer review policy, terms, support

### Logged in
| Page | What it does |
|------|--------------|
| Dashboard | Stats, recommended collaborators, profile completion ring, opportunities, events, messages |
| My Profile | Cover, avatar, bio, CGPA, interests, skill bars, projects and publications tabs |
| Smart Matchmaking | Scores every researcher against your interests and ranks the best fits |
| Opportunities | Browse open projects and apply to join |
| My Projects | Project cards, progress rings, upcoming milestones |
| Project workspace | Milestones, task pipeline, shared files, team chat, collaborators |
| Publications | Upload papers, pre-prints, datasets and theses — **with version history** |
| Repository | Open research repository — search, filter, sort, paginate, download |
| Communities | Research groups and discussion threads |
| Events | Seminars and workshops with RSVP |
| Messages | One-to-one private inbox |
| Requests | Accept or decline people who applied to your projects |
| Notifications | Everything that happened to you |

### Admin (`admin/`)
Overview statistics, manage users, projects, publications, communities and events.

---

## Part 3 — Paper version control

Every paper keeps its full revision history.

**How it works**

- The `publications` row always holds the **live** copy of a paper.
- Every revision is a row in `publication_versions`, holding that revision's
  title, abstract, its own uploaded file, and a note describing what changed.
- `publications.current_version` points at the newest version number.

**What the user sees**

- A `v3` badge next to the paper title, in the repository and on the profile.
- A **Version History** timeline on the paper page — every version with its date,
  author, change note and its own download button.
- The quick citation includes the version number, so a citation is unambiguous.

**When a new version is created**

| Action | Result |
|--------|--------|
| Upload a paper for the first time | Version 1 |
| Edit and attach a new file | Always a new version |
| Edit with "Save this as a new version" ticked | New version |
| Edit with the box unticked and no new file | Updated in place, no new version |

Uploading a replacement file **never deletes the old file** — the previous version
still points at it, so an old revision stays downloadable forever. Deleting a paper
removes every version's file together.

---

## Part 4 — Problems and fixes

**"Database connection failed"**
MySQL is not running, or the SQL file has not been imported. Start MySQL, then redo Part 1 step 3.

**Apache will not start (port 80 in use)**
Usually Skype or IIS. XAMPP → **Config → Apache (httpd.conf)**, change `Listen 80` to `Listen 8080`
and `ServerName localhost:80` to `ServerName localhost:8080`. Your URL becomes
`http://localhost:8080/research-collaboration-hub/`.

**MySQL will not start (port 3306 in use)**
Another MySQL is installed as a Windows service. Stop that service.

**The page loads but has no styling**
Open `config/config.php` and hard-code the folder name:
```php
define('BASE_URL', '/research-collaboration-hub/');
```

**"Access denied for user 'root'"**
Put your MySQL password in `config/config.php`:
```php
define('DB_PASS', 'your_password_here');
```

**Uploads do not save**
Right-click the `uploads` folder → Properties → uncheck **Read-only** → Apply.

**A download link says the file is not available**
Normal for the seeded demo papers — they are database records with no real PDF attached.
Upload your own file and the download works.

---

## Part 5 — Folder map

```
research-collaboration-hub/
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
├── admin/ ................... admin panel (6 pages + nav)
├── assets/
│   ├── css/style.css ........ the whole design system
│   ├── js/main.js ........... sidebar, tabs, filters, validation
│   └── img/logo.jpg
├── config/
│   ├── config.php ........... credentials, paths, session bootstrap
│   └── database.php ......... PDO connection + query helpers
├── includes/
│   ├── header.php / footer.php
│   ├── functions.php ........ auth, CSRF, uploads, formatting, matching, versioning
│   └── icons.php ............ inline SVG icon set (works offline)
├── database/
│   └── rch_uiu.sql .......... schema + demo data (import this)
└── uploads/ ................. avatars, covers, publications, project files, events
```

---

## Part 6 — Technical summary

- **PDO with prepared statements** everywhere. No query concatenates user input.
- **Password hashing** with `password_hash()` / `password_verify()` (bcrypt).
- **CSRF tokens** on every form that changes data.
- **Output escaping** with `htmlspecialchars()` on everything from the database.
- **Role-based access control** — `requireLogin()` and `requireRole('admin')`.
- **File upload validation** — extension whitelist, size limit, randomised stored
  filenames, and an `.htaccess` in `uploads/` that blocks execution.
- **Downloads proxied** through `download.php`, so real paths are never exposed.
- **Normalised schema** — 29 tables with foreign keys and `ON DELETE CASCADE`.
- **Matching algorithm** — Jaccard similarity over research interests plus a
  same-department bonus (`matchScore()` in `includes/functions.php`).
- **Version control** — append-only `publication_versions` table; old files are
  never overwritten.

---

*United International University · Collaborate · Innovate · Impact*
