# Architecture

## Rationale
Apache on WampServer provides a familiar PHP/MySQL local environment. PDO with native prepared statements prevents SQL parameter interpolation; vanilla JS provides async uploads and near-realtime polling without build tools. MySQL InnoDB supplies transactions and row locks.

## Entity relationships
```mermaid
erDiagram
  visitors ||--o{ photos : uploads
  visitors ||--o{ consents : grants
  visitors ||--o{ upload_attempts : makes
  consents ||--o{ photos : authorizes
  visitors { bigint id PK
    varchar display_name
    char token_hash UK
    char code_hash UK }
  photos { bigint id PK
    bigint visitor_id FK
    bigint consent_id FK
    enum status
    tinyint is_hidden
    datetime approved_at }
  consents { bigint id PK
    bigint visitor_id FK
    datetime withdrawn_at }
  settings { varchar setting_key PK }
  admins { bigint id PK
    varchar username UK }
  upload_attempts { bigint id PK }
  login_attempts { bigint id PK }
```

## System flow
```mermaid
flowchart TD
  V[Visitor browser] -->|Name + opt-in| ID[Identity and consent]
  ID -->|HttpOnly cookie| V
  V -->|POST files + CSRF| U[PHP upload API]
  U -->|Validate and re-encode| F[Image folder]
  U -->|Transaction| P[(MySQL photos: pending)]
  A[Admin login] --> D[Admin dashboard]
  D -->|approve or reject| P
  P -->|approved and visible only| S[Slides API]
  S -->|poll every 2.5 s| T[TV browser]
  V -->|Delete or withdraw consent| P
```

## Upload sequence
```mermaid
sequenceDiagram
  participant V as Visitor
  participant H as PHP Handler
  participant DB as MySQL
  participant FS as Filesystem
  participant A as Admin
  participant TV as TV Display
  V->>H: POST photos + CSRF + cookie
  H->>DB: Check consent, rate limit, lock visitor row
  H->>FS: Validate and re-encode random image filenames
  H->>DB: INSERT photo status=pending
  DB-->>H: Commit transaction
  H-->>V: Awaiting approval
  A->>DB: Approve photo (status=approved)
  TV->>DB: GET api/slides.php via PHP
  DB-->>TV: Only approved, unhidden, consent-active photos
  TV-->>TV: Interrupt current slide, queue new photo
```

## Important constraints
- The admin dashboard accepts/rejects each photo individually. Every subsequent upload is another request.
- The slideshow polls the **full approved state**, not just new IDs, so approving an old pending photo or deleting an approved photo takes effect. Response is capped at 500 slides for demo scale.
- No raw access token, recovery code or admin password is stored in the database; only SHA-256/HMAC or password hashes.
- Cookies use HttpOnly and SameSite=Lax. CSRF is checked on all mutating forms; admin sessions are regenerated on login.
- Input files are MIME + `getimagesize()` checked and decoded/re-encoded by GD, stripping EXIF. Random names and Apache script execution restrictions reduce upload risk.
- MySQL transactions + per-visitor `FOR UPDATE` lock prevent quota races and duplicate writes. Files are not transactional; process crashes can produce orphaned images, requiring cleanup.
- Deletion/withdrawal removes rows and files. A TV showing an image may keep it until its next successful poll (normally <=2.5 seconds); network failure can delay removal. Cached or externally copied images cannot be recalled.
