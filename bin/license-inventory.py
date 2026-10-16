#!/usr/bin/env python3
"""Reproduce Composer's declared dependency licenses; not a legal clearance."""
import argparse
import hashlib
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
TARGET = ROOT / "THIRD_PARTY_DEPENDENCIES.json"


def inventory():
    lock_bytes = (ROOT / "composer.lock").read_bytes()
    lock = json.loads(lock_bytes)
    packages = []
    for group, scope in [("packages", "runtime"), ("packages-dev", "development")]:
        for package in lock.get(group, []):
            packages.append({
                "name": package["name"],
                "version": package["version"],
                "scope": scope,
                "declared_licenses": package.get("license", []),
                "source": package.get("source", {}).get("url"),
            })
    return json.dumps({
        "scope": "Composer lock metadata only; excludes JavaScript, bundled assets and services. Not a license notice bundle or a legal opinion.",
        "source_sha256": hashlib.sha256(lock_bytes).hexdigest(),
        "packages": sorted(packages, key=lambda item: item["name"]),
    }, ensure_ascii=False, indent=2) + "\n"


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--check", action="store_true")
    args = parser.parse_args()
    expected = inventory()
    if args.check:
        if not TARGET.is_file() or TARGET.read_text(encoding="utf-8") != expected:
            raise SystemExit("Dependency inventory is missing or outdated.")
        print("Composer dependency inventory matches the lock file.")
    else:
        TARGET.write_text(expected, encoding="utf-8", newline="\n")
        print("Generated THIRD_PARTY_DEPENDENCIES.json")
