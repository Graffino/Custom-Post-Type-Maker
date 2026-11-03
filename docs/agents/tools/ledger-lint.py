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
"""
import argparse, os, re, sys

ROOT = os.getcwd()
AG = os.path.join(ROOT, "docs", "agents")
KINDS = {  # prefix: (main file, topic dir, required fields, allowed statuses)
    "D": ("DECISIONS.md", "decisions", ["Status", "Rule", "Why", "Evidence"],
          ["approved", "proposed", "superseded-by"]),
    "PATTERN": ("PATTERNS.md", "patterns", ["Status", "How", "Example"],
                ["approved", "candidate", "superseded-by"]),
    "C": ("CONTRACTS.md", "contracts", ["Status", "Interface", "Evidence"],
          ["approved", "proposed", "superseded-by"]),
    "Q": ("OPEN-QUESTIONS.md", "questions", ["Status"],
          ["open", "investigating", "answered", "deferred"]),
}
SPLIT_AT = 150
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
    main, tdir, *_ = KINDS[prefix]
    out = []
    if os.path.exists(os.path.join(AG, main)):
        out.append(os.path.join(AG, main))
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


def lint(fix=False):
    errors, warns, all_ids = [], [], {}
    for prefix, (main, tdir, required, statuses) in KINDS.items():
        paths = files_for(prefix)
        if not paths:
            continue
        entries = []
        for p in paths:
            es, lines = parse(p)
            own = [e for e in es if not e["repo"]]  # consumed contracts carry a repo prefix
            entries += own
            if p.endswith(main) and len(lines) > SPLIT_AT and len(paths) == 1:
                warns.append(f"{rel(p)}: {len(lines)} lines — split into {tdir}/<topic>.md (see SKILL step 3)")
            for e in es:
                if e["repo"]:
                    if not e["fields"].get("Link"):
                        errors.append(f"{rel(p)}:{e['line']} consumed {e['repo']}:{e['id']} has no **Link:**")
                    continue
                if not e["id"].startswith(prefix + "-"):
                    errors.append(f"{rel(p)}:{e['line']} {e['id']} is in the {main} ledger")
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
        n = len(open(state, encoding="utf-8").read().splitlines())
        if n > SPLIT_AT:
            errors.append(f"{rel(state)}: {n} lines — STATE.md stays at {SPLIT_AT} or fewer (an index, not a narrative)")
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


def index_lines(prefix, entries):
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
        key = e["file"] if not e["file"].endswith(main) else ""
        groups.setdefault(key, []).append(e)
    out = []
    for key in sorted(groups, key=lambda k: (k != "", k)):
        if key:
            name = os.path.splitext(os.path.basename(key))[0]
            out.append(f"\n### [{name.replace('-', ' ').capitalize()}]({tdir}/{os.path.basename(key)})")
        out += [line(e) for e in groups[key]]
    return "\n".join(out).strip("\n") + "\n"


def index_check(prefix, entries, errors, fix):
    main = os.path.join(AG, KINDS[prefix][0])
    if not os.path.exists(main):
        return
    text = open(main, encoding="utf-8").read()
    m = re.search(r"^## Index\n(.*?)(?=^---\s*$|^## (?!Index)|\Z)", text, re.S | re.M)
    want = index_lines(prefix, entries)
    if not m:
        errors.append(f"{rel(main)}: no '## Index' section")
        return
    if m.group(1).strip() != want.strip():
        if fix:
            text = text[:m.start(1)] + want + "\n" + text[m.end(1):].lstrip("\n")
            open(main, "w", encoding="utf-8").write(text)
            print(f"rebuilt index in {rel(main)}")
        else:
            errors.append(f"{rel(main)}: index out of date — run with --fix")


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
