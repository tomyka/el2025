# Bot Protection — Honeypot Design Spec

**Date:** 2026-06-11
**Scope:** Sub-project B of the broader security audit
**Status:** Approved

---

## Problem

The registration form accepts any automated POST submission that passes rate limiting (3/min/IP from Sub-project A). Naive bots that populate all visible form fields in sequence can register accounts without any human interaction.

The app is a private sports prediction league with under 100 expected users, so heavyweight CAPTCHA solutions are disproportionate. A honeypot trap closes the remaining gap with zero friction for real users and no external dependencies.

---

## Solution: Honeypot Field

Add a hidden input field to the email registration form. Real users never see it and cannot fill it. Bots that blindly populate all form fields will fill it. The controller silently rejects any submission where the field is non-empty.

---

## Form Changes

**File:** `resources/views/auth/register.blade.php`

Add one hidden input inside the `<form>` element:

```html
<div style="position:absolute;left:-9999px;opacity:0;height:0;width:0;overflow:hidden" aria-hidden="true" tabindex="-1">
    <label for="website">Leave this blank</label>
    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
</div>
```

**Why this approach to hiding:**
- `position:absolute; left:-9999px` — moves the element off-screen; most bots do not check coordinates
- `opacity:0; height:0; width:0; overflow:hidden` — belt-and-suspenders visual hiding
- NOT `display:none` or `hidden` attribute — bots commonly skip fields marked with these
- `aria-hidden="true"` — screen readers skip it
- `tabindex="-1"` on both wrapper and input — keyboard users cannot accidentally land on it
- `autocomplete="off"` — browser autofill does not populate it
- Field name `website` — plausible to a bot scraping for any fillable field; not obviously a trap

**Scope:** Email registration form only. Google OAuth is unaffected — it goes through Google's own bot protection.

---

## Controller Changes

**File:** `app/Http/Controllers/Auth/RegisteredUserController.php`

Add honeypot check at the top of `store()`, before any validation:

```php
public function store(Request $request): RedirectResponse
{
    if (!$this->registrationIsOpen()) {
        return redirect()->route('main');
    }

    // Honeypot: real users never fill this field; bots typically do
    if ($request->filled('website')) {
        return redirect()->route('main');
    }

    $request->validate([...]);
    // ... rest unchanged
```

**Behaviour on trap:**
- Silent redirect to main route — no error message, no indication of rejection
- No DB write, no user created, no event fired
- Bot receives HTTP 302 and cannot distinguish this from a closed-registration redirect

---

## Testing

**File:** `tests/Feature/HoneypotTest.php`

Two test cases:

1. **Honeypot filled → silent reject**
   - POST `/register` with all valid fields + `website` populated
   - Assert: HTTP 302, no `User` record created

2. **Honeypot empty → registration proceeds**
   - POST `/register` with all valid fields, `website` absent
   - Assert: HTTP 302, `User` record created

---

## Out of Scope

- CAPTCHA / third-party widget (Sub-project B alternative, rejected for this scale)
- Login form honeypot (login has its own lockout via `LoginRequest`)
- Google OAuth (protected by Google's own bot detection)
- Admin validation (Sub-project C)
- Code quality refactor (Sub-project D)
