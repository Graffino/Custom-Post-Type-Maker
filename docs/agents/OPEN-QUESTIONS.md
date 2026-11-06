# Open questions — Custom-Post-Type-Maker

<!-- graffino-agentic:begin v1 -->
> Unknowns. Work that depends on an open question is out of scope until it is
> answered. Only the user answers; an agent never answers by guessing. An agent may
> write a `Proposed answer` the evidence supports; the question stays
> `investigating` until the user confirms it.
> Answered entries stay, with the answer and date, and usually become a D- or PATTERN-.
<!-- graffino-agentic:end -->

## Index
- Q-001 · Does the repo get an automated test suite, and which runner? · _answered_
- Q-002 · How do we run WordPress locally for checks and screenshots? · _answered_
- Q-003 · Who may manage definitions: only users with manage_options, or also Editors? · _answered_

---

## Q-001 · Does the repo get an automated test suite, and which runner?
- **Status:** answered 2026-11-03
- **Blocks:** "tests first" in /build. Until then, each behaviour is checked by hand.
- **Who answers:** Zeno
- **Answer:** Playground smoke tests in `tests/`, run locally with `tests/run.sh`. No CI for now (Zeno, 2026-11-03).
- **Recorded as:** D-011

## Q-002 · How do we run WordPress locally for checks and screenshots?
- **Status:** answered 2026-11-03
- **Blocks:** screenshots of admin screens, the activation check with WP_DEBUG, `php -l` and `phpcs` (PHP is not installed on this Mac). WordPress Playground through `npx @wp-playground/cli` is a candidate; on 2026-11-03 the auto mode classifier blocked it until Zeno approves it.
- **Who answers:** Zeno
- **Answer:** Use WordPress Playground (`npx @wp-playground/cli`). Zeno allowed it on 2026-11-03.
- **Recorded as:** AGENTS.md section 2

## Q-003 · Who may manage definitions: only users with manage_options, or also Editors?
- **Status:** answered 2026-11-03
- **Blocks:** the capability fix in the next release. The menu needs `manage_options` (class-cptm.php:173), but `cptm` and `cptm_tax` use `capability_type` 'page' (class-cptm.php:119, 151). So an Editor can open `edit.php?post_type=cptm` directly and change post types.
- **Who answers:** Zeno
- **Answer:** Admins only (`manage_options`).
- **Recorded as:** D-008
