#!/usr/bin/env python3
"""Search PlanOK OpenAPI docs by keyword, path, operationId, tag, or schema."""
import argparse, json, re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DEFAULT_DOC = ROOT / "references" / "openapi.json"

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("query", help="keyword/path/operationId/schema to search")
    ap.add_argument("--doc", default=str(DEFAULT_DOC))
    args = ap.parse_args()
    data = json.loads(Path(args.doc).read_text(encoding="utf-8"))
    q = args.query.lower()
    hits = []
    for path, item in data.get("paths", {}).items():
        for method, op in item.items():
            if method.lower() not in {"get","post","put","patch","delete","options","head"}:
                continue
            blob = " ".join([
                path, method, op.get("operationId", ""), op.get("summary", ""), op.get("description", ""),
                " ".join(op.get("tags", [])), json.dumps(op.get("parameters", []), ensure_ascii=False)
            ]).lower()
            if q in blob:
                hits.append((method.upper(), path, op.get("operationId", ""), op.get("summary") or op.get("description", "")))
    print(f"Endpoint hits: {len(hits)}")
    for m,p,oid,desc in hits[:200]:
        print(f"{m:6} {p} | {oid} | {desc[:160]}")
    schema_hits=[]
    for name, schema in data.get("definitions", {}).items():
        blob=(name+" "+json.dumps(schema, ensure_ascii=False)).lower()
        if q in blob:
            schema_hits.append(name)
    print(f"\nSchema hits: {len(schema_hits)}")
    for name in schema_hits[:200]:
        print(name)

if __name__ == "__main__":
    main()
