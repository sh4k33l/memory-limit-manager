# Release Runbook

This repository is prepared for a WordPress plugin release (`memory-limit-manager`) and GitHub push.

## 1) Push to GitHub `main`

```bash
git remote -v
# If missing, add your remote:
# git remote add origin git@github.com:<ORG>/<REPO>.git

git fetch origin
# Merge work branch into main
git checkout main
git merge --ff-only work || git merge work

git push origin main
```

## 2) Release to WordPress.org SVN

> Replace `<plugin-slug>` with your actual plugin slug.

```bash
svn checkout https://plugins.svn.wordpress.org/<plugin-slug>/ wporg-svn
cd wporg-svn
svn update

# Sync plugin files from git repo into trunk
rsync -av --delete \
  --exclude '.git' \
  --exclude '.github' \
  /workspace/memory-limit-manager/ trunk/

# Add new files and remove deleted files
svn status | awk '/^\?/ {print $2}' | xargs -r svn add
svn status | awk '/^\!/ {print $2}' | xargs -r svn rm

svn commit -m "Release 1.0.2: update Tested up to 7.0 and changelog"
svn copy trunk tags/1.0.2 -m "Tagging version 1.0.2"
```

## 3) Pre-release checks

From git repo root:

```bash
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l
```

## 4) Version consistency checklist

- `memory-limit-manager.php` header:
  - `Version:`
  - `Tested up to:`
- `readme.txt`:
  - `Stable tag:`
  - `Tested up to:`
- `CHANGELOG.md` includes the release date and notes.

