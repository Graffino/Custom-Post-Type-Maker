#!/usr/bin/env python3
"""Ledger lint for docs/agents (Graffino Agentic Code Setup).

Checks the decisions / patterns / contracts / open-questions ledger and rebuilds
its indexes. Stdlib only. Run from the repo root:

  python3 docs/agents/tools/ledger-lint.py           # check; exit 1 on errors
  python3 docs/agents/tools/ledger-lint.py --fix     # rebuild every "## Index"
  python3 docs/agents/tools/ledger-lint.py --next D  # next free ID (D, PATTERN, C, Q)

Ledger layout: each kind has a main file (DECISIONS.md …). Entries are headings
`## D-001 · Title` (or `###` in CONTRACTS.md). Above ~150 lines a kind may be split:
entries move to topic files in docs/agents/<kind>/<topic>.md and the main file keeps
only the index, grouped by topic.

Superseded decisions and patterns leave the always-loaded files: their index lines
go to DECISIONS-SUPERSEDED.md / PATTERNS-SUPERSEDED.md (same topic headings), and an
entry written in the main file moves there too. Entries in topic files stay put.
--fix does the moving; a check reports an entry in the wrong file.
STATE.md (team addon): at most 150 lines, each table row at most 220 characters.
"""
import argparse, os, re, sys

ROOT = os.getcwd()
AG = os.path.join(ROOT, "docs", "agents")
KINDS = {  # prefix: (main file, topic dir, required fields, allowed statuses, superseded file)
    "D": ("DECISIONS.md", "decisions", ["Status", "Rule", "Why", "Evidence"],
          ["approved", "proposed", "superseded-by"], "DECISIONS-SUPERSEDED.md"),
    "PATTERN": ("PATTERNS.md", "patterns", ["Status", "How", "Example"],
                ["approved", "candidate", "superseded-by"], "PATTERNS-SUPERSEDED.md"),
    "C": ("CONTRACTS.md", "contracts", ["Status", "Interface", "Evidence"],
          ["approved", "proposed", "superseded-by"], None),
    "Q": ("OPEN-QUESTIONS.md", "questions", ["Status"],
          ["open", "investigating", "answered", "deferred"], None),
}
SPLIT_AT = 150
STATE_ROW_MAX = 220
HEAD = re.compile(r"^(#{2,3}) (?:(?P<repo>[\w.-]+):)?(?P<id>(?:D|PATTERN|C|Q)-\d{3,})\s*·\s*(?P<title>.+?)\s*$")
FIELD = re.compile(r"^- \*\*(?P<k>[A-Za-z ]+):\*\*\s*(?P<v>.*)$")
LINK = re.compile(r"`(?P<repo>[\w.-]+):docs/agents/CONTRACTS\.md#(?P<id>C-\d{3,})`")
ANY_ID = re.compile(r"(?<![\w:#-])((?:D|PATTERN|C|Q)-\d{3,})(?![\w])")
MDLINK = re.compile(r"\]\(([^)#\s]+)(?:#[^)]*)?\)")


def find_repo(name):
    """A sibling repo: ../<name>, or ../../*/<name> (repos grouped in folders)."""
    parent = os.path.dirname(ROOT)
    cands = [os.path.join(parent, name)]
    gp = os.path.dirname(parent)
    if os.path.isdir(gp):
        cands += [os.path.join(gp, d, name) for d in sorted(os.listdir(gp))]
    for c in cands:
        if os.path.isdir(os.path.join(c, "docs", "agents")):
            return c
    return None


def files_for(prefix):
    main, tdir, *_, sup = KINDS[prefix]
    out = []
    for f in (main, sup):
        if f and os.path.exists(os.path.join(AG, f)):
            out.append(os.path.join(AG, f))
    d = os.path.join(AG, tdir)
    if os.path.isdir(d):
        out += sorted(os.path.join(d, f) for f in os.listdir(d) if f.endswith(".md"))
    return out


def parse(path):
    """Entries in one file: [{id, title, repo, fields, line, section}]"""
    entries, cur, section = [], None, ""
    lines = open(path, encoding="utf-8").read().splitlines()
    for n, line in enumerate(lines, 1):
        if line.startswith("## ") and not HEAD.match(line):
            section = line[3:].strip()
        m = HEAD.match(line)
        if m:
            cur = {"id": m["id"], "title": m["title"], "repo": m["repo"], "fields": {},
                   "line": n, "file": path, "section": section}
            entries.append(cur)
            continue
        if cur:
            f = FIELD.match(line)
            if f:
                # a value may continue on the next lines (a list): mark it present
                cur["fields"][f["k"].strip()] = f["v"].strip() or "(continued)"
            elif line.startswith("#"):
                cur = None
    return entries, lines


def rel(p):
    return os.path.relpath(p, ROOT)


def is_sup(e):
    return e["fields"].get("Status", "").strip().startswith("superseded-by")


def sup_skeleton(prefix):
    main, *_, sup = KINDS[prefix]
    kind = main.split(".")[0].capitalize()
    return (f"# {kind} superseded\n\n<!-- graffino-agentic:begin v1 -->\n"
            f"> Never obeyed; read only to explain old code or a past choice. "
            f"`ledger-lint.py --fix` keeps this file in sync with `{main}`.\n"
            f"<!-- graffino-agentic:end -->\n\n## Index\n\n---\n")


def move_entries(prefix):
    """--fix: superseded entries written in the main file go to the superseded file,
    live ones written there go back. Topic-file entries are never moved."""
    main, *_, sup = KINDS[prefix]
    if not sup:
        return
    mp, sp = os.path.join(AG, main), os.path.join(AG, sup)
    if not os.path.exists(mp):
        return
    def take(path, want_sup):
        if not os.path.exists(path):
            return []
        lines = open(path, encoding="utf-8").read().split("\n")
        es = [e for e in parse(path)[0] if not e["repo"] and is_sup(e) == want_sup]
        blocks, drop = [], set()
        for e in es:
            start, end = e["line"] - 1, e["line"]
            while end < len(lines) and not re.match(r"^#{1,2} ", lines[end]):
                end += 1
            blocks.append("\n".join(lines[start:end]).rstrip("\n"))
            drop.update(range(start, end))
        if blocks:
            kept = [l for n, l in enumerate(lines) if n not in drop]
            open(path, "w", encoding="utf-8").write("\n".join(kept).rstrip("\n") + "\n")
            print(f"moved {', '.join(e['id'] for e in es)} out of {rel(path)}")
        return blocks
    to_sup, to_main = take(mp, True), take(sp, False)
    for path, blocks in ((sp, to_sup), (mp, to_main)):
        if not blocks:
            continue
        text = open(path, encoding="utf-8").read() if os.path.exists(path) else sup_skeleton(prefix)
        open(path, "w", encoding="utf-8").write(text.rstrip("\n") + "\n\n" + "\n\n".join(blocks) + "\n")


def lint(fix=False):
    errors, warns, all_ids = [], [], {}
    for prefix, (main, tdir, required, statuses, sup) in KINDS.items():
        if fix:
            move_entries(prefix)
        paths = files_for(prefix)
        if not paths:
            continue
        entries = []
        for p in paths:
            es, lines = parse(p)
            own = [e for e in es if not e["repo"]]  # consumed contracts carry a repo prefix
            entries += own
            topics = [q for q in paths if os.path.dirname(q) != AG]
            if os.path.basename(p) in (main, sup) and len(lines) > SPLIT_AT and not topics:
                warns.append(f"{rel(p)}: {len(lines)} lines — split into {tdir}/<topic>.md (see SKILL step 3)")
            for e in es:
                if e["repo"]:
                    if not e["fields"].get("Link"):
                        errors.append(f"{rel(p)}:{e['line']} consumed {e['repo']}:{e['id']} has no **Link:**")
                    continue
                if not e["id"].startswith(prefix + "-"):
                    errors.append(f"{rel(p)}:{e['line']} {e['id']} is in the {main} ledger")
                if sup and os.path.basename(p) == main and is_sup(e):
                    errors.append(f"{rel(p)}:{e['line']} {e['id']} is superseded: it belongs in {sup} (run with --fix)")
                if sup and os.path.basename(p) == sup and not is_sup(e):
                    errors.append(f"{rel(p)}:{e['line']} {e['id']} is not superseded: it belongs in {main} (run with --fix)")
                for k in required:
                    if not e["fields"].get(k):
                        errors.append(f"{rel(p)}:{e['line']} {e['id']} misses **{k}:**")
                st = e["fields"].get("Status", "")
                if st and not any(st.startswith(s) for s in statuses):
                    errors.append(f"{rel(p)}:{e['line']} {e['id']} status '{st}' not in {statuses}")
        for e in entries:
            if e["id"] in all_ids:
                errors.append(f"{rel(e['file'])}:{e['line']} duplicate {e['id']} (first at {all_ids[e['id']]})")
            else:
                all_ids[e["id"]] = f"{rel(e['file'])}:{e['line']}"
        for e in entries:
            m = re.search(r"superseded-by\s+((?:D|PATTERN|C|Q)-\d+)", e["fields"].get("Status", ""))
            if m and m.group(1) not in {x["id"] for x in entries}:
                errors.append(f"{rel(e['file'])}:{e['line']} {e['id']} superseded-by {m.group(1)}, which does not exist")
        index_check(prefix, entries, errors, fix)
    state = os.path.join(AG, "STATE.md")
    if os.path.exists(state):
        slines = open(state, encoding="utf-8").read().splitlines()
        if len(slines) > SPLIT_AT:
            errors.append(f"{rel(state)}: {len(slines)} lines — STATE.md stays at {SPLIT_AT} or fewer (an index, not a narrative)")
        for n, line in enumerate(slines, 1):
            if line.startswith("|") and len(line) > STATE_ROW_MAX:
                errors.append(f"{rel(state)}:{n} row has {len(line)} characters — at most {STATE_ROW_MAX}; "
                              "the detail belongs in the ticket's OVERVIEW.md")
    # cross-repo contract links
    for p in files_for("C"):
        for n, line in enumerate(open(p, encoding="utf-8"), 1):
            for m in LINK.finditer(line):
                repo_dir = find_repo(m["repo"])
                if not repo_dir:
                    continue  # provider not on disk (or not set up yet): cannot check
                other = os.path.join(repo_dir, "docs", "agents")
                ids = set()
                for q in [os.path.join(other, "CONTRACTS.md")] + (
                        [os.path.join(other, "contracts", f) for f in os.listdir(os.path.join(other, "contracts"))]
                        if os.path.isdir(os.path.join(other, "contracts")) else []):
                    if os.path.exists(q):
                        ids |= {e["id"] for e in parse(q)[0] if not e["repo"]}
                if m["id"] not in ids:
                    errors.append(f"{rel(p)}:{n} link {m['repo']}:{m['id']} not found in {m['repo']}/docs/agents")
    # references to IDs that do not exist, and broken relative links
    md = [os.path.join(ROOT, "AGENTS.md")] + [
        os.path.join(dp, f) for dp, _, fs in os.walk(AG) for f in fs if f.endswith(".md")]
    for p in md:
        if not os.path.exists(p):
            continue
        for n, line in enumerate(open(p, encoding="utf-8"), 1):
            if "{{" in line:
                continue  # unfilled template line
            for ref in ANY_ID.findall(line):
                if ref not in all_ids:
                    errors.append(f"{rel(p)}:{n} refers to {ref}, which does not exist")
            for target in MDLINK.findall(line):
                if re.match(r"^[a-z]+:", target):
                    continue  # URL
                if not os.path.exists(os.path.normpath(os.path.join(os.path.dirname(p), target))):
                    errors.append(f"{rel(p)}:{n} broken link {target}")
    return errors, warns


def index_lines(prefix, entries, home, pointer=None, empty_topics=()):
    """Index text for the file `home` (main or superseded file): entries written in it
    first, then one heading per topic file. `pointer` = the superseded file, listed
    under a topic whose entries are all superseded (empty_topics)."""
    main, tdir, *_ = KINDS[prefix]
    def short(status):
        m = re.match(r"(superseded-by\s+\S+|[a-z-]+)", status.strip())
        return m.group(1) if m else ""
    def line(e):
        st = short(e["fields"].get("Status", ""))
        extra = ""
        if prefix == "PATTERN":
            extra = f" · {st}"
        elif st and st not in ("approved", "open"):
            extra = f" · _{st}_"
        if prefix == "C":
            extra = f" · {e['section'].lower() or 'provided'}" + ("" if st == "approved" else f" · _{st}_")
        return f"- {e['id']} · {e['title']}{extra}"
    groups = {}
    for e in entries:
        key = "" if os.path.dirname(e["file"]) == AG else e["file"]
        groups.setdefault(key, []).append(e)
    for t in empty_topics:
        groups.setdefault(t, [])
    out = []
    for key in sorted(groups, key=lambda k: (k != "", k)):
        if key:
            name = os.path.splitext(os.path.basename(key))[0]
            out.append(f"\n### [{name.replace('-', ' ').capitalize()}]({tdir}/{os.path.basename(key)})")
        out += [line(e) for e in groups[key]]
        if key and not groups[key] and pointer:
            out.append(f"- all superseded: see [{pointer}]({pointer})")
    return "\n".join(out).strip("\n") + "\n"


def check_one(path, want, errors, fix):
    text = open(path, encoding="utf-8").read()
    m = re.search(r"^## Index\n(.*?)(?=^---\s*$|^## (?!Index)|\Z)", text, re.S | re.M)
    if not m:
        errors.append(f"{rel(path)}: no '## Index' section")
        return
    if m.group(1).strip() != want.strip():
        if fix:
            text = text[:m.start(1)] + want + "\n" + text[m.end(1):].lstrip("\n")
            open(path, "w", encoding="utf-8").write(text)
            print(f"rebuilt index in {rel(path)}")
        else:
            errors.append(f"{rel(path)}: index out of date — run with --fix")


def index_check(prefix, entries, errors, fix):
    main, *_, sup = KINDS[prefix]
    mp = os.path.join(AG, main)
    if not os.path.exists(mp):
        return
    if not sup:
        check_one(mp, index_lines(prefix, entries, mp), errors, fix)
        return
    live = [e for e in entries if not is_sup(e)]
    gone = [e for e in entries if is_sup(e)]
    live_topics = {e["file"] for e in live}
    empty = sorted({e["file"] for e in gone if os.path.dirname(e["file"]) != AG} - live_topics)
    check_one(mp, index_lines(prefix, live, mp, sup, empty), errors, fix)
    sp = os.path.join(AG, sup)
    if gone and not os.path.exists(sp):
        if fix:
            open(sp, "w", encoding="utf-8").write(sup_skeleton(prefix))
        else:
            errors.append(f"{rel(sp)} is missing: {len(gone)} superseded entries need it (run with --fix)")
            return
    if os.path.exists(sp):
        check_one(sp, index_lines(prefix, gone, sp), errors, fix)


def next_id(prefix):
    nums = [int(e["id"].split("-")[-1]) for p in files_for(prefix) for e in parse(p)[0] if not e["repo"]]
    width = 3
    return f"{prefix}-{(max(nums) + 1 if nums else 1):0{width}d}"


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--fix", action="store_true")
    ap.add_argument("--next", choices=list(KINDS))
    a = ap.parse_args()
    if not os.path.isdir(AG):
        print("no docs/agents here — run from the repo root"); return 1
    if a.next:
        print(next_id(a.next)); return 0
    errors, warns = lint(a.fix)
    if a.fix:
        errors, warns = lint(False)
    for w in warns:
        print(f"! {w}")
    for e in errors:
        print(f"✗ {e}")
    print("✓ ledger ok" if not errors else f"{len(errors)} error(s)")
    return 1 if errors else 0


if __name__ == "__main__":
    sys.exit(main())
