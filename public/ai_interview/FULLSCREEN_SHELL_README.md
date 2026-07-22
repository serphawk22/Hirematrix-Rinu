# Persistent fullscreen shell — what changed

## The one thing you need to do
Point whatever links into this app ("Start Interview" button on the job
board, etc.) at:

    ai_interview/shell.php

instead of `ai_interview/index.php`. That's it — everything else below
happens automatically.

If something still links straight to `index.php` (or any other page in
the flow) you don't have to hunt it down: every page now auto-detects
that it was opened as a real top-level page and silently bounces itself
into `shell.php` on load, so it still gets the persistent-fullscreen
behaviour.

## What `shell.php` is
`shell.php` is a new, tiny page that is the *only* page in this app that
should ever be a real browser navigation. It shows the "Begin Interview"
overlay, requests fullscreen once, and then loads the entire existing
flow — `index.php`, `start.php`, `exam.php`, `results.php`, `coding.php`,
`coding_results.php`, `interview.php`, `interview_results.php` — inside
a full-viewport `<iframe>`.

Nothing about those eight pages' own logic changed: same PHP, same DB
queries, same `<form>` POSTs, same `window.location.href` redirects
between steps, same webcam/proctoring/timer/editor scripts. They still
navigate exactly like a normal multi-page site — just inside the iframe
instead of the real browser window.

## Why this actually fixes it
Fullscreen is a property of whichever document called
`requestFullscreen()`. In the old setup, every page called it for
itself, so every full page load (every step of the flow) started a new
document with no fullscreen — hence "only fullscreen after I click
something on *this* page."

Now, `shell.php`'s document is the one that calls `requestFullscreen()`,
and `shell.php` never reloads — only the iframe inside it navigates.
So fullscreen genuinely never drops between steps, with exactly one
click, ever, for the whole interview.

Esc / Alt+Tab / closing the lid / minimizing still do what browsers
always let them do (see the big comment at the top of
`js/fullscreen-lock.js` — that's a hard browser security limit, not a
bug), but now that's detected once, centrally, in the shell, and the
existing exit-confirm modal ("Resume Interview" / "End Interview")
still pops up and blocks the page exactly as before.

## Fixed after first pass: false "you switched away" popups on every click
The first version of the shell listened for `window.blur` on the shell
window to detect real tab/app switches. It turns out clicking *anywhere
inside the iframe* also fires a native `blur` event on the parent window
(focus moving from the parent into its own child frame looks the same
to the browser as focus leaving). That made the exit-confirm modal pop
up on basically every click.

Fixed in `js/fullscreen-lock.js`: the blur handler now waits a tick and
checks `document.hasFocus()`, which stays `true` as long as focus is
anywhere inside the shell's own window - including its iframe. It only
counts as a real violation when focus has genuinely left the browser
window (alt-tab, another app, another tab).

## Second fix: native confirm()/alert()/prompt() and permission prompts also force fullscreen to drop
Turns out the blur false-positive wasn't the only false alarm. **Any**
native browser dialog - `confirm()`, `alert()`, `prompt()`, and a
camera/microphone permission prompt - forces the whole tab out of
fullscreen. It's the same browser security rule that stops a page from
auto-fullscreening itself: the browser won't let a page show a trusted
dialog while pretending to be "fullscreen," so it drops fullscreen the
instant any of those appear, anywhere in the tab (including inside the
iframe). The shell then sees that as a real fullscreenchange-exit and
pops its own "You exited fullscreen mode" modal on top.

Two concrete triggers this hit:
- **`interview.php`'s "End Interview" button** used `confirm('End
  interview and generate your report?')` - clicking it always dropped
  fullscreen first, which is what you saw.
- **The camera/microphone permission prompt** proctoring.js triggers
  the first time it calls `getUserMedia()` mid-interview.

Fixed both ways:
1. Added `window.AiInterviewDialog.confirm(message)` /
   `.alert(message)` to `js/fullscreen-lock.js` - plain-DOM, non-native
   replacements for `confirm()`/`alert()`/`prompt()` that never touch
   fullscreen. Swapped in wherever the flow used a native dialog:
   `interview.php` (End Interview), `coding.php` (Reset code),
   `results.php` (copy-link fallback), `js/prevent-back.js` (back
   button warning), `js/proctoring.js` (both `alert()` calls). If you
   add new confirmations anywhere in the flow later, use
   `AiInterviewDialog` instead of the native versions.
2. `shell.php` now sets `requestMediaPermissionsOnStart: true`. On the
   "Begin Interview" click, it requests camera+mic permission (and
   immediately releases the stream) *before* requesting fullscreen -
   so that permission prompt happens up front, not mid-interview.
   Browsers remember the grant per origin, so proctoring.js's later
   `getUserMedia()` calls resolve silently with no repeat prompt (and
   therefore no more forced fullscreen-exit). If the candidate denies
   permission at that point, it just proceeds to fullscreen anyway and
   proctoring.js will ask again later, same as before this change.

## Files touched

- **`shell.php`** (new) — the persistent shell + iframe.
- **`js/fullscreen-lock.js`** — unchanged in every page that uses it
  standalone. New behaviour: it now detects whether it's running inside
  `shell.php`'s iframe (`window.top !== window.self`). If so, it hands
  its candidate/job session info up to the shell via `postMessage` and
  steps aside — no start overlay, no `requestFullscreen()` calls, no
  fullscreen/blur/visibility listeners of its own, since the shell
  already owns all of that. If a page is ever opened standalone (direct
  URL, testing), it behaves exactly as it did before this change.
- **`index.php`, `start.php`, `exam.php`, `results.php`, `coding.php`,
  `coding_results.php`, `interview.php`, `interview_results.php`** —
  each got one small inline `<script>` added at the very top of
  `<head>`: if the page is opened as a real top-level navigation, it
  redirects to `shell.php?p=<thispage>` (carrying over its query
  string) instead of rendering standalone. No-op when already inside
  the shell's iframe.
- **`results.php`, `coding_results.php`, `interview_results.php`** —
  their "End Interview" button's final redirect
  (`candidate/applications`) now targets `window.top` instead of
  `window.location`, so ending the interview breaks out of the shell
  and lands you back on the real job-board page, rather than navigating
  just the iframe.

## Testing checklist
1. Open `shell.php` fresh (not `index.php`). Click "Begin Interview".
2. Fill the form, submit into `start.php` → the page inside the iframe
   changes, the *browser* stays fullscreen the whole time (no flash,
   no re-click).
3. Walk the whole flow through to `interview_results.php` — fullscreen
   should never drop on its own at any step.
4. Press Esc mid-flow — the exit-confirm modal should appear over the
   whole screen (covering the iframe) with "Resume" / "End Interview".
5. Click "End Interview" from a results page — it should take you all
   the way out to `candidate/applications`, not just reload the iframe.
6. Open `exam.php` (or any inner page) directly by URL — it should
   immediately redirect you into `shell.php` instead of rendering bare.
