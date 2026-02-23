# DR Restore Drill Evidence (P1)

## Drill scope
- Backup source: `scripts/ops/backup.sh --full`
- Restore target: staging environment
- Restore command: `scripts/ops/restore.sh <backup_dir>`

## Acceptance targets
- RTO target: `<= 60 minutes`
- RPO target: `<= 15 minutes` for exam/proctoring events

## Verification checklist (fill during drill)
- [ ] Database restored from compressed SQL backup.
- [ ] Upload artifacts restored (`storage/uploads`).
- [ ] Integration log restored for replay verification.
- [ ] `/health` returns non-down status post-restore.
- [ ] Sample student login and exam resume flow validated.

## Captured metrics (fill during drill)
- Observed RTO: `TBD`
- Observed RPO: `TBD`

## Signoff
- Drill date: `TBD`
- Incident commander: `TBD`
- SRE/Operations approver: `TBD`
