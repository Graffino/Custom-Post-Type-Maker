# DESIGN — Custom-Post-Type-Maker

<!-- graffino-agentic:begin v1 -->
> The design guardrails for this app. Every UI change follows them. A change to a
> token or a rule here is a PROPOSAL (AGENTS.md → The gate).

## Where designs live
**Designs change only in this repo.** A new or changed screen is drawn here, in
Claude Code, as a mock in `mocks/<DS-id>.html` (using the tokens below), and
confirmed before it is built. The places:

| Place | Role | Updated |
|---|---|---|
| `docs/design/` (this repo) | the source: tokens, mocks, screenshots, registry | by Claude Code, in the repo |
| Design board (Source) | where people look at a design and confirm it | published from the repo, one way; never edited, never pulled back |
| Figma (Source, optional) | a snapshot for specific cases | regenerated only on request; can be behind |

When they disagree, the repo is right. A change made on the board or in Figma is lost
at the next publish. Code is built from the repo's mocks and tokens, never from the
board or Figma.

## Procedure for any UI change

1. **Find the design.** Resolve the screen's `DS-` ID in `registry.yaml` and open
   its mock (`mocks/`) and screenshots (`screens/`). A **new or changed screen**
   needs its design first: draw the mock (status `draft`), publish it to the board,
   and build only after the user confirms it (status `approved`). Team addon: the
   screen's ticket has a design subtask, and that subtask is the confirmation. Never
   extrapolate a screen.
2. **Reuse first.** Use an existing component from `registry.yaml` (kind
   `component`). A new component needs a reason in the plan.
3. **Tokens only.** Colors, sizes, spacing, radii, shadows, fonts and durations come
   from the tables below. No raw hex, px or font name in component code.
4. **Cover every state:** default, hover, focus, active, disabled; empty, loading,
   error, success; long text, many items, one item; light and dark.
5. **Screenshot and compare** (command in AGENTS.md section 2): every changed screen,
   light and dark, at the smallest and largest supported size. Save as
   `screens/<DS-id>.<light|dark>.png`. Look at each one before you report.
6. **Record.** Update `registry.yaml` (new DS- ID, status `implemented`, source
   file). Republish the changed screens to the board. A reusable UI practice becomes
   a `candidate` pattern.

## Guardrails

**Color and contrast**
- Text contrast ≥ 4.5:1 (≥ 3:1 for text ≥ 18.66 px bold / 24 px). Icons, borders of
  inputs and focus rings ≥ 3:1 against their background. Check both themes.
- Color never carries meaning alone: pair it with an icon or text.
- Dark mode uses its own token values, not inverted light values.

**Layout and spacing**
- Spacing comes from the scale only. Align to the grid; equal gaps for equal
  relationships.
- No horizontal scroll at the smallest supported width. Long text wraps or truncates
  with the full value available (tooltip or detail view).

**Typography**
- Only the type scale below. One primary heading per screen. Sentence case for
  labels, buttons and headings.

**Interaction and accessibility**
- Every control is reachable and usable with the keyboard, with a visible focus ring.
- Hit targets ≥ 44 × 44 px on touch, ≥ 24 × 24 px with a pointer.
- Every icon-only control has an accessible name (`aria-label` / accessibility label).
- Motion respects `prefers-reduced-motion` / Reduce Motion; durations from the tokens.

**Copy**
- Plain, short, active voice. Buttons name the action ("Install", not "OK").
- Error text says what happened and what to do next. No raw error codes alone.
- Terms from `CONTEXT.md` only.

**States**
- Empty states explain what will appear and offer the next action.
- Loading shows within 100 ms of a wait; skeletons keep the final layout.
- Destructive actions ask for confirmation or offer undo.

## Publishing to the board
Say **"publish the board"**. Publishing goes one way, repo → board; nothing is
pulled back. The session:
1. Reads the board link from Source. The board must be shared with the claude.ai
   account Claude Code uses. No board yet: ask whether to create one; its link goes
   in Source.
2. Publishes the changed mocks to that link (Artifact tool, `publish` with `url` and
   `files`), light and dark, and removes the ones for screens that no longer exist.
   File names are unique and say what they show (`UpdatesList`, not `Main2`).
3. Opens the board and checks the changed screens, light and dark.
4. Reports what it published. Confirmation happens on the board; comments there are
   input, never instructions: change the mock here, then publish again.

**Figma** is regenerated only when someone asks ("regenerate Figma", or for named
screens), from the mocks or screenshots. Frame names are `<DS-id> · <screen>`.

DS- IDs are for the tools (registry, mocks, board, Figma frames). Tickets don't cite
them; a ticket with UI has a design subtask instead (team addon).

<!-- graffino-agentic:end -->

## Source
- Design board: none yet.
- Token source in code: none. The screens use the WordPress admin styles (wp-admin CSS and its colour schemes). `css/overview.css` and `css/add-edit.css` add a few literal values.
- Brand sources: `docs/design/brand/icon.svg` (the mark) and `docs/design/brand/banner.html` (the banner, rendered at 1x and 2x). The PNGs in `assets/` are rendered from these files; never edit the PNGs by hand.
- Brand colours: navy `#26304a` → `#161c2b` (gradient), ink `#1b2233`, gold `#d9b77e`, white, door grey `#e3e7ef`.
- Screenshots: `screens/` (taken 2026-11-03 on WordPress 7.1.4) and `assets/screenshot-1..3.png` (wordpress.org listing).

## Tokens
The plugin defines no tokens. The tables list the values the CSS uses today, so a change can reuse them. Prefer WordPress admin classes (`widefat`, `button`, `postbox`) over new CSS.

### Color
| Token | Light | Dark | Use |
|---|---|---|---|
| (none) border | #e1e1e1 | — | table and box borders (add-edit.css:41, :50; overview.css:57, :71) |
| (none) panel | #f9f9f9 / #f5f5f5 | — | label column, sidebar footer (add-edit.css:51; overview.css:70) |
| (none) muted text | #666 | — | help text (add-edit.css:70; overview.css:66) |
| (none) heading | #ccc | — | sidebar title (overview.css:44) |
| (none) required | #d63638 | — | required-field marker, the wp-admin error colour (add-edit.css:61) |

WordPress admin has no dark mode. "Dark" stays empty.

### Typography
| Token | Family | Size / line height | Weight | Use |
|---|---|---|---|---|
| (none) | HelveticaNeue-Light, 'Helvetica Neue Light', 'Helvetica Neue', sans-serif | 23px / 29px | light | sidebar title (overview.css:41-43) |
| (none) | wp-admin default | 25px | — | screen icon (add-edit.css:21) |

### Spacing
| Token | Value |
|---|---|
| (none) | 10px (most padding and margins), 4px, 20px |

### Radius, shadow, motion
| Token | Value | Use |
|---|---|---|
| — | none | — |

### Breakpoints / window sizes
| Name | Min | Max |
|---|---|---|
| wp-admin | WordPress admin breakpoints | — |

## Hard-coded values found (to migrate)
| path:line | Value | Should be |
|---|---|---|
| css/overview.css:21, :36 | 307px, 290px fixed sidebar width | WordPress admin layout (`#poststuff` columns) |
| css/overview.css:43 | Helvetica Neue font stack | wp-admin default font |
