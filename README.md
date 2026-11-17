Administrator username and password (November 2026 update)
Username: `sabiha`.
Set the password you requested: After extracting the ZIP over your current project
(preserving `.env`, `uploads` and MySQL), open
http://localhost/photo-slideshow/admin/configure-login.php on your WampServer
computer. Enter `[Set Password Locally]` in the password field and click Save new admin login.
Then use http://localhost/photo-slideshow/admin/login.php.
This is a one-time browser setup. It replaces the existing admin username/password
in the database without needing Windows `.bat` scripts. It protects the operation
with localhost-only access and CSRF checking. The setup page locks itself after a
successful update and attempts to delete itself. A password hash, not the raw
password, is stored in MySQL.
For a fresh install, the automatic database setup starts with username `sabiha`
and a generated initial password, which you can replace on the one-time page.
Security warning: `[Set Password Locally]` is weak and publicly known in this chat; use it
only for an offline/local school demo. Do not expose this WampServer instance to
other computers or the public internet with these credentials.
---
PhotoFlow — Visitor Photo Slideshow
Visitors enter a name and consent to public display, then upload up to three JPG/PNG/WEBP photos on their first successful upload request; thereafter only one photo per request (including restored visitors). Every photo is pending until the admin explicitly approves it, including new photos from returning visitors. The TV display polls every ~2.5 seconds and shows newly approved photos first. Visitors can view statuses, delete photos and withdraw consent.
Stack: WampServer, Apache, PHP 8.x, PDO, MySQL 8/MariaDB, HTML/CSS, vanilla JS; no Composer dependency or framework.
Quick start on WampServer (Windows) — no file edits required on a default install
Start WampServer (Apache + MySQL, green Wamp icon).
Extract the ZIP so the project is exactly `C:\wamp64\www\photo-slideshow\`.
Open http://localhost/photo-slideshow/. On first launch, the local setup page
automatically creates `.env` with a cryptographically random `APP_SECRET`, creates
`photo_slideshow` and imports all tables. It creates the `admin` account with a
random password displayed exactly once on the setup page. Save it.
Click Open visitor page, or go to Admin login and use the shown credentials.
Open `http://localhost/photo-slideshow/display.php` on the TV.
The automatic installer expects fresh WampServer MySQL defaults: localhost, `root`,
empty MySQL password, and PHP 8 with `gd`, `fileinfo`, and `pdo_mysql` enabled. If you
changed MySQL credentials, configure `.env` to match; software cannot discover your
private password. If Apache cannot write to the project folder, grant appropriate
local file permissions or create `.env` from `.env.example` manually. The setup page
is restricted to requests originating on the WampServer computer (`localhost`).
Existing databases and admin accounts are not reset.
To support 5 MB photos, set `upload_max_filesize=6M` and `post_max_size=32M` in
WampServer's active `php.ini` if your defaults are lower; restart Apache. Schedule
`scripts/cleanup.php` via Windows Task Scheduler for retention enforcement.
Security: The one-time generated admin password is deliberately not a known
shared default (such as `admin12345`). Keep it private and do not publish your local
WAMP server online. The generated `.env` is ignored by Git.
Admin approval demo
Visitor enters name, ticks consent; saves one-time six-character visitor code.
Visitor selects photos and clicks Send approval request; success says submitted for admin approval.
Admin logs in as `admin` using the password you configured in `.env`.
Admin sees new pending photo cards; clicks Approve. Only now will the TV show them (~2.5 seconds with a healthy connection).
A returning visitor uploads another photo: it also enters pending; earlier approvals do not exempt new uploads.
Admin can reject, hide, unhide, delete, change slide duration and retention days. Visitor can delete or withdraw consent from My photos.
Git
`.env` and the photo files are ignored in `.gitignore`. Do not commit real visitor photos, personal secrets, or sample plaintext passwords.
Security notes / limitations
Photo URLs under `/uploads/` are public if known; server-side uploads `.htaccess` disables script execution. This is a local demonstration system and is not production-audited. Serve over HTTPS and move files out of document root for public internet use. Short recovery codes are vulnerable to guessing and rely on IP throttling; consider longer codes and account verification for production. The display is eventually consistent with admin deletion due to polling. Browser/network failures may delay changes. Privacy consent text is versioned; withdrawal cannot recall third-party copies or browser caches.
Screenshots
Visitor upload: `[add screenshot here]`
Admin pending requests: `[add screenshot here]`
TV display: `[add screenshot here]`
Demo link: `[insert deployed demo URL here]`
Tests
Run PHP scripts in `tests/` from the CLI (test scripts do not require database access). Follow `tests/MANUAL_TEST_PLAN.md` for browser/database end-to-end checks. See `ASSUMPTIONS.md` and `ARCHITECTURE.md` for rationale and Mermaid diagrams.
Git commits by phase
`Initialize WampServer project structure and database schema`
`Add visitor identity, consent, translations and theme`
`Implement secure photo uploads with pending moderation status`
`Add live slideshow polling for approved photos`
`Build authenticated admin approval and visitor photo management`
`Polish responsive accessible interface and animations`
`Add tests documentation and deployment instructions`
Switching visitors / logging out (new)
Once a visitor has signed in or restored an identity, the header displays Log out visitor
(translated into Hindi and Gujarati according to the selected language). Click it to
clear the HttpOnly visitor identity cookie on this browser. The site returns to the
new visitor registration and returning visitor code form. This does not delete
photos, withdraw consent, or log out the administrator; a visitor can restore their
existing identity later using the six-character recovery code.
For testing multiple visitors on one computer: sign in as Visitor A, upload and submit
photos for admin approval, click Log out visitor, then register Visitor B. The
admin logout remains separate and is available from the admin dashboard.
Upgrade existing installations safely: Extract this ZIP over your existing
`photo-slideshow` project, preserving your local `.env`, MySQL database, and
`uploads/` folder. Do not delete the old folder if it contains photos.
Change the existing admin password (Windows)
Start WampServer and confirm the website was initialized at least once.
Double-click `CHANGE-ADMIN-PASSWORD.bat` in the project folder.
At the prompt type the desired password (for this local demo: `[Set Password Locally]`) and press Enter.
Log out from the Admin panel, then log in as `admin` using that password.
The reset script works on an existing database without deleting visitor photos or
settings. It runs via local PHP CLI only and hashes the supplied password using
`password_hash()`; it does not store the plaintext in repository files. The chosen
password is weak for public deployments: use a long, unique password outside local
classroom testing. Existing active admin sessions may remain logged in until they
log out or expire; log out before testing the new password.
Upload batch limit — first-time versus returning visitors
New visitor, first successful upload request: Select 1–3 photos.
Any later request: Select exactly 1 photo per request (including after logging out and returning with the visitor code). A visitor may make multiple separate one-photo requests, subject to the existing 20-photo overall quota.
Each photo is still pending until the admin approves it.
The front end displays the appropriate selection limit, and the PHP upload handler enforces it even if someone bypasses JavaScript.
Earlier uploads are detected using persisted photo rows and successful upload-attempt records, so deleting photos does not normally reset the first-upload allowance.
A row lock on `visitors` and success-record insertion before commit keep simultaneous first uploads from both claiming the three-photo allowance.
No SQL migration required: uses existing `last_seen_at`, `photos`, and `upload_attempts` fields.

Admin dashboard design update
The admin dashboard includes a dedicated sidebar, administrator account indicator, pending-request count, and an accessible mobile navigation toggle. Admin actions, logout, moderation, and upload limits remain unchanged.