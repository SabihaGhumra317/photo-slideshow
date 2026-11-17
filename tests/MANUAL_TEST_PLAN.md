# Manual test plan

Populate **Actual** and **Pass/Fail** after personally testing in WampServer. Expected results are specifications, not claimed observed results.

| Test case | Steps | Expected | Actual | Pass/Fail |
|---|---|---|---|---|
| Wamp boot | Import SQL; configure `.env`; browse index | Page loads with no PHP error | — | Not run |
| Visitor create | Enter unique name and consent | Recovery code shown once | — | Not run |
| Consent required | Submit without consent | Browser refuses / server rejects | — | Not run |
| Same name different identity | New browser, same name | New identity; no photo access across visitors | — | Not run |
| Return by code | New browser, enter six-character code | Identity restored, prior photos shown | — | Not run |
| Wrong code | Try incorrect code repeatedly | Failure; throttled after five attempts | — | Not run |
| Upload pending | Submit JPG with valid consent | New photo pending; not on TV | — | Not run |
| Existing visitor upload | Repeat upload after earlier approval | Second photo pending, not automatically approved | — | Not run |
| Admin login | Use configured password | Dashboard opens | — | Not run |
| Admin wrong password | Incorrect credentials | Login rejected; throttle after eight failures | — | Not run |
| Admin approve | Click Approve on pending photo | Photo appears on TV in ~3 sec | — | Not run |
| Older pending approved late | Approve older pending ID after newer image | TV picks up newly approved older ID | — | Not run |
| Admin reject | Reject pending photo | Never shows on TV | — | Not run |
| Hide/unhide | Hide approved photo, then unhide | TV removes then restores after next poll | — | Not run |
| Change duration | Set 3 seconds; watch TV | Following slide intervals ≈3 seconds | — | Not run |
| Invalid extension/MIME | Rename TXT to JPG and upload | Real MIME validation rejects | — | Not run |
| Oversized upload | Select file >5 MiB | Friendly error, no new photo | — | Not run |
| First upload >3 files | New visitor selects four files | Rejected | — | Not run |
| Max 20 quota | Upload to 20 using separate one-photo requests; try one more | Rejected without exceeding 20 | — | Not run |
| Delete | Delete approved photo from My Photos | File and DB row removed; TV drops it | — | Not run |
| Withdraw consent | Click withdraw and confirm | All photos removed; no more public display | — | Not run |
| CSRF | POST action without valid token | 403 / rejected | — | Not run |
| Concurrency | Submit from two tabs for same visitor, near 20 limit | Locked transaction prevents >20; filenames unique | — | Not run |
| Expiry | Set retention to 1 day; insert old test photo, run cleanup CLI | Old photo and DB row removed | — | Not run |
| Languages | Switch English/Hindi/Gujarati | Visitor UI changes; cookie saves language | — | Not run |
| Theme | Toggle light/dark; reload | Theme persists | — | Not run |
| Reduced motion | Enable OS reduced-motion | Animations disabled | — | Not run |
| Mobile layout | Open in narrow viewport | Usable upload/admin pages, no horizontal scrolling | — | Not run |


### Visitor logout and switching profiles

| Test case | Steps | Expected | Actual | Pass/Fail |
|---|---|---|---|---|
| Visitor logout | Login as visitor A, click Log out visitor | Visitor upload panel disappears; register and restore-code forms return | | |
| Different visitor | Logout A, create B | B gets unique recovery code, cannot view A's private photo list | | |
| Restore previous | Logout B, enter A's visitor code | A's photos and profile restored | | |
| Admin independence | Login as admin and visitor, logout visitor | Admin remains logged in; admin logout still works | | |
| CSRF/method protection | GET visitor-logout.php, then POST invalid CSRF | GET returns 405; invalid CSRF cannot log out the visitor | | |

### First-time versus returning visitor upload limits

| Test case | Steps | Expected | Actual | Pass/Fail |
|---|---|---|---|---|
| First request 3 photos | Register new visitor, upload 3 photos | All 3 submitted as pending | — | Not run |
| First request 4 photos | Register new visitor, select 4 photos | Client and PHP reject over limit | — | Not run |
| Second request 2 photos | After successful first upload, try 2 photos | Client and PHP reject; only one permitted | — | Not run |
| Returning visitor 2 photos | Logout and restore visitor using code; try 2 photos | Rejected, even after page reload | — | Not run |
| Returning visitor 1 photo | Restore identity, submit single photo | One new pending photo | — | Not run |
| Two-tab concurrency | First upload simultaneously from two tabs | One first-time request gets up to 3; second gets 1 limit | — | Not run |
| Rejected photos don't reset | Upload photos, admin rejects, attempt multi-upload again | 1 photo per request | — | Not run |
