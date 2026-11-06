# Decisions superseded — Custom-Post-Type-Maker

<!-- graffino-agentic:begin v1 -->
> Decisions that no longer apply. **Never obeyed**; read only to explain old code or a
> past choice. Each points to the entry that replaced it. `ledger-lint.py --fix`
> keeps this file in sync: superseded entries move here from `DECISIONS.md`, and
> their index lines move here from its index.
<!-- graffino-agentic:end -->

## Index
- D-003 · Definition names are lowercased and spaces are removed on save · _superseded-by D-010_

---

## D-003 · Definition names are lowercased and spaces are removed on save
- **Status:** superseded-by D-010
- **Date:** 2026-11-03
- **Rule:** Save `cptm_name` and `cptm_tax_name` as `sanitize_text_field( strtolower( str_replace( ' ', '', … ) ) )`.
- **Why:** Commits 77ca7e0 ("Enforces lowercase custom post name") and f733bd2 ("Better sanitize taxonomy name").
- **Rejected:** none recorded.
- **Evidence:** class-cptm.php:1051, class-cptm.php:1147
- **Source:** code, git · confidence high
