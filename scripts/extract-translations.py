#!/usr/bin/env python3
"""
Conservative __() extraction for the Blade admin/portal/public views and
controller flash messages. Only rewrites unambiguous literals:

  - >Plain text<               ->  >{{ __('Plain text') }}<
  - placeholder="..." title="" ->  placeholder="{{ __('...') }}"
  - @section('title', '...')   ->  @section('title', __('...'))
  - ->with('status'|'error', '...') -> wrapped in __()

Strings containing $, {, }, <, >, &, ", ' or @ are left alone (interpolation,
entities and quoting risks) — those get a manual pass. Idempotent: lines already
containing __( for the match are skipped.
"""

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
VIEW_DIRS = [ROOT / 'resources' / 'views']
PHP_DIRS = [ROOT / 'app' / 'Http' / 'Controllers', ROOT / 'app' / 'Modules']

# Plain translatable text: starts with a letter, safe charset, ends non-space.
TEXT = r"[A-Za-z][A-Za-z0-9 ,.:()/%–—-]*[A-Za-z0-9.)%]"

RE_TAG_TEXT = re.compile(r">(\s*)(" + TEXT + r")(\s*)<")
RE_ATTR = re.compile(r"(\s(?:placeholder|title))=\"(" + TEXT + r")\"")
RE_SECTION = re.compile(r"@section\('title',\s*'(" + TEXT + r")'\)")
RE_FLASH = re.compile(r"(->with\(\s*'(?:status|error)'\s*,\s*)'(" + TEXT + r")'")

SKIP_WORDS = {'px', 'em', 'rem', 'ltr', 'rtl', 'en', 'bn', 'ok', 'id', 'na', 'utf-8'}


def wrap_blade(m: re.Match) -> str:
    text = m.group(2)
    if text.lower() in SKIP_WORDS or len(text) < 3:
        return m.group(0)
    return f">{m.group(1)}{{{{ __('{text}') }}}}{m.group(3)}<"


def transform_blade(src: str) -> str:
    out_lines = []
    in_script = False
    in_style = False
    in_comment = False
    in_php = False
    for line in src.splitlines(keepends=True):
        low = line.lower()
        stripped = line.strip()
        # @php ... @endphp blocks are raw PHP — wrapping text inside string
        # literals there breaks the quoting. Skip them entirely.
        if stripped.startswith('@php') and '@endphp' not in stripped:
            in_php = True
        if in_php:
            if '@endphp' in stripped:
                in_php = False
            out_lines.append(line)
            continue
        if '@php' in line:  # single-line @php(...) or @php ... @endphp
            out_lines.append(line)
            continue
        if '{{--' in line and '--}}' not in line:
            in_comment = True
            out_lines.append(line)
            continue
        if in_comment:
            if '--}}' in line:
                in_comment = False
            out_lines.append(line)
            continue
        if '<script' in low:
            in_script = True
        if '<style' in low:
            in_style = True
        if in_script or in_style or '{{--' in line:
            if '</script' in low:
                in_script = False
            if '</style' in low:
                in_style = False
            out_lines.append(line)
            continue
        if '__(' not in line:
            line = RE_TAG_TEXT.sub(wrap_blade, line)
            line = RE_ATTR.sub(lambda m: f"{m.group(1)}=\"{{{{ __('{m.group(2)}') }}}}\"", line)
            line = RE_SECTION.sub(lambda m: f"@section('title', __('{m.group(1)}'))", line)
        out_lines.append(line)
    return ''.join(out_lines)


def transform_php(src: str) -> str:
    return RE_FLASH.sub(lambda m: f"{m.group(1)}__('{m.group(2)}')", src)


def run() -> None:
    changed = 0
    for base in VIEW_DIRS:
        for f in base.rglob('*.blade.php'):
            src = f.read_text(encoding='utf-8')
            new = transform_blade(src)
            if new != src:
                f.write_text(new, encoding='utf-8')
                changed += 1
    for base in PHP_DIRS:
        for f in base.rglob('*.php'):
            if 'database' in f.parts or 'migrations' in f.parts:
                continue
            src = f.read_text(encoding='utf-8')
            new = transform_php(src)
            if new != src:
                f.write_text(new, encoding='utf-8')
                changed += 1
    print(f"transformed {changed} files")


if __name__ == '__main__':
    sys.exit(run())
