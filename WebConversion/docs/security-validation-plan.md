# Security Validation Plan (SAST/DAST/Pentest)

## Scope
- API auth lifecycle, webhook signature verification, token/session management.
- Input validation and injection surfaces.
- Secrets handling and key rotation.

## Required checks before GA
1. SAST in CI on each PR.
2. DAST against stage weekly.
3. Quarterly external pentest.
4. High/Critical findings must be fixed before release.

## Evidence to attach for release gate
- SAST report link/date.
- DAST report link/date.
- Pentest report and remediation status.
- Sign-off from security owner.
