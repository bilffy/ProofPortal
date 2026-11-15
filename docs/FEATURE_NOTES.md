## Completed changes

- Added a root `.claudeignore` excluding `vendor/`, `node_modules/`, `storage/`, build output, lock files, `.env*`, logs, IDE/OS files, test artifacts, `public/proofing-assets/` and minified assets.
- Noted that Claude Code does not read `.claudeignore` natively, so permission deny rules in `.claude/settings.json` are the supported way to block paths.
- Created `docs/FEATURE_NOTES.md` as the running log of completed changes; `CLAUDE.md` left unchanged because no architecture or UI convention changed.
