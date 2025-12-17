# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project loosely adheres to [Semantic Versioning](https://semver.org/).

The version pattern being used is: `vMAJOR.MINOR.SPRINT_NUMBER.PATCH`.

## [Unreleased]

- New feature for the Photography page 'Others'

## [1.1.30.2] - 2025-12-18
### Added
- Improve lightbox query for subject images to use only external_subject_id value and prevent null values from showing unrelated images
- Update groups query for lightbox feature to match results with only those that start with the given search term
- Update PermissionSeeder's list of permissions to include newly-added values

### Fixed
- Remove unnecessary commas from missing school address details
- Prevent encountering a JS error after selecting job in folders section when groups tab is disabled
- Remove the 'No folders available' message if there are rows visible
- Digital Images Permissions' checkboxes now account for null initial values
- Remove deleted file import references from vite config that cause build errors

## [1.1.30.1] - 2025-12-04
### Added
- Remove lowercase validation for email in Login form
- Refactor file storage management: Introduce storage abstraction and support multiple drivers
- Add command for cleaning up school-uploaded images
- Setup scheduled commands (via laravel scheduler and cron)
- Improve Edit Profile feature for better user experience

### Fixed
- Update Dockerfile to address newer build issues
- Improve error-handling for image download request feature

## [1.1.30.0] - 2025-11-17
### Added
- Initial versioned release.
- Add VERSION file
- Add CHANGELOG.md file
- Add access to version value via config
- Update footer with version value

### Fixed
- N/A

---

### How to add a new release
1. Update the `VERSION` file with the new version (e.g. `1.1.30.1`).
2. Add a new section in this `CHANGELOG.md` under `Unreleased` and move it under the new version with the release date.
3. Commit the changes and create an annotated Git tag:

```bash
git add VERSION CHANGELOG.md
git commit -m "chore(release): vX.Y.Z.A"
git tag -a vX.Y.Z.A -m "Release vX.Y.Z.A"
git push origin main && git push origin vX.Y.Z.A
```

4. (Optional) Create a GitHub Release using the tag.

---

## Version management notes

### Manual release flow
1. Decide bump level (major/minor/patch).
2. Update `VERSION` file with the new version string.
3. Edit `CHANGELOG.md`: move or add entries under the new version with the release date.
4. Commit the change.
5. Create an annotated tag.
6. Push commit and tag.
7. Create a GitHub Release from the tag (UI or gh CLI) and include the changelog notes.

### Branching and release strategies
1. Trunk-based approach
- Merge PRs to main frequently.
- Create releases (tag) from main.
- Hotfixes: create branch from main, fix, merge to main, tag new patch release.

### Handling hotfixes, backports & multiple versions
1. Hotfix on main
- Publish patch tag vX.Y.Z.1
- If the fix must land on other branches, cherry-pick or merge accordingly.
2. For maintaining multiple release lines, keep branches for each supported minor/major version and tag releases there as appropriate.

### Changelog best practice
3. Keep `CHANGELOG.md` with Unreleased section. On release:
-  Move Unreleased bullet items into the release section with date.
- Commit the changelog update with the version bump.

---

This file is intended to be updated alongside the `VERSION` file and release tags; automation (CI) can be wired later to keep them in sync.
