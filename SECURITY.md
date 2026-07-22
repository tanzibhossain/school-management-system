# Security Policy

This project handles real student, guardian, and staff personal data,
academic records, and payment gateway credentials (bKash, SSLCommerz,
Stripe, PayPal). Please report security issues responsibly rather than
opening a public issue.

## Reporting a Vulnerability

**Do not open a public GitHub issue for security vulnerabilities.**

Instead, report it privately using one of these channels:

- **GitHub Private Vulnerability Reporting** (preferred): go to the
  [Security tab](https://github.com/tanzibhossain/school-management-system/security/advisories/new)
  of this repository and click "Report a vulnerability."
- **Email**: [md.tanzib.hossain@gmail.com](mailto:md.tanzib.hossain@gmail.com)
  with a description of the issue, steps to reproduce, and its potential
  impact.

Please include, where possible:

- The module/file/endpoint affected
- Steps to reproduce (a minimal repro is ideal)
- The potential impact (e.g. data exposure, privilege escalation, injection)
- Any suggested fix, if you have one

## What to Expect

- Acknowledgement within a few days of your report.
- An assessment of severity and, if confirmed, a target timeline for a fix.
- Credit in the fix's release notes, if you'd like it (or full anonymity,
  your choice).
- Please allow a reasonable window to ship and release a fix before any
  public disclosure.

## Scope

This is a self-hosted application — each deployment is operated by the
school running it. Vulnerabilities in this project's own code (Laravel
application code, migrations, Blade views, configuration defaults) are in
scope. Misconfiguration of a specific deployment (weak `.env` secrets,
exposed MinIO/MySQL/Redis ports, missing HTTPS in production, etc.) is a
deployment concern, not a vulnerability in the project itself — though
you're welcome to flag defaults you think are unsafe out of the box.

## Supported Versions

This project does not yet maintain multiple parallel release lines.
Security fixes are made against the latest release; upgrade to the most
recent tagged version to receive them.

| Version | Supported |
|---------|-----------|
| Latest release (`main`) | ✅ |
| `dev` (pre-release) | Best-effort |
| Older tagged releases | ❌ |
