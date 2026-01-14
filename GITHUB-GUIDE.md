# How to Add Memory Limit Manager to GitHub

Complete guide to publishing your WordPress plugin on GitHub.

---

## Prerequisites

Before you begin, ensure you have:
- [ ] Git installed on your computer
- [ ] A GitHub account (create one at https://github.com)
- [ ] Terminal/Command Line access

---

## Step 1: Create a GitHub Repository

### Via GitHub Website

1. **Go to GitHub:** https://github.com
2. **Sign in** to your account
3. **Click** the "+" icon in the top right
4. **Select** "New repository"

### Repository Settings

Fill in the following:

- **Repository name:** `memory-limit-manager`
- **Description:** `A WordPress plugin to easily manage memory limits (WP_MEMORY_LIMIT and WP_MAX_MEMORY_LIMIT) through an intuitive admin interface.`
- **Visibility:** Choose one:
  - ✅ **Public** - Anyone can see this repository (recommended for open source)
  - ⬜ **Private** - Only you can see this repository
- **Initialize repository:**
  - ⬜ Don't check "Add a README file" (you already have one)
  - ⬜ Don't add .gitignore yet (we'll create it)
  - ✅ Choose a license: **GNU General Public License v2.0**

4. **Click** "Create repository"

---

## Step 2: Initialize Git in Your Plugin Folder

### Open Terminal

**Mac/Linux:**
- Open Terminal application
- Navigate to your plugin folder:

```bash
cd /Users/muhammadshakeel/Downloads/LearnDash/memory-limit-manager
```

**Windows:**
- Open Command Prompt or Git Bash
- Navigate to your plugin folder:

```bash
cd C:\path\to\memory-limit-manager
```

### Initialize Git Repository

Run these commands one by one:

```bash
# Initialize git repository
git init

# Check git status
git status
```

---

## Step 3: Create .gitignore File

Create a `.gitignore` file to exclude unnecessary files from GitHub.

### Create the file:

**Mac/Linux:**
```bash
nano .gitignore
```

**Windows:**
```bash
notepad .gitignore
```

### Add this content:

```
# WordPress
*.log
wp-config.php
wp-content/advanced-cache.php
wp-content/backup-db/
wp-content/backups/
wp-content/cache/
wp-content/upgrade/
wp-content/uploads/

# Plugin specific
/backups/
wp-config-backup-*.php

# Development
.DS_Store
.idea/
.vscode/
*.swp
*.swo
*~
.project
.settings/
nbproject/

# Composer
/vendor/
composer.lock

# Node
node_modules/
npm-debug.log
yarn-error.log
package-lock.json

# OS Files
Thumbs.db
Desktop.ini
$RECYCLE.BIN/

# Temporary files
*.tmp
*.temp
.cache

# Testing
phpunit.xml
/tests/
/test-results/

# Logs
error_log
debug.log
```

Save and close the file.

---

## Step 4: Add Files to Git

### Stage All Files

```bash
# Add all files to staging area
git add .

# Check what will be committed
git status
```

You should see:
- `new file: memory-limit-manager.php`
- `new file: README.md`
- `new file: readme.txt`
- `new file: CHANGELOG.md`
- `new file: includes/class-admin.php`
- `new file: includes/class-config-handler.php`
- `new file: assets/css/admin.css`
- `new file: assets/js/admin.js`
- And other plugin files

---

## Step 5: Make Your First Commit

### Commit the Files

```bash
git commit -m "Initial commit: Memory Limit Manager v1.0.0"
```

This creates your first commit with all plugin files.

---

## Step 6: Connect to GitHub

### Add Remote Repository

Replace `YOUR-USERNAME` with your actual GitHub username:

```bash
git remote add origin https://github.com/YOUR-USERNAME/memory-limit-manager.git
```

**Example:**
```bash
git remote add origin https://github.com/shak33l/memory-limit-manager.git
```

### Verify Remote

```bash
git remote -v
```

You should see:
```
origin  https://github.com/YOUR-USERNAME/memory-limit-manager.git (fetch)
origin  https://github.com/YOUR-USERNAME/memory-limit-manager.git (push)
```

---

## Step 7: Push to GitHub

### Push Your Code

```bash
# Rename default branch to main (if needed)
git branch -M main

# Push to GitHub
git push -u origin main
```

### Authentication

You'll be prompted for GitHub credentials:

**Option 1: Personal Access Token (Recommended)**
1. Go to https://github.com/settings/tokens
2. Click "Generate new token" → "Generate new token (classic)"
3. Give it a name: "Memory Limit Manager"
4. Select scopes: Check "repo"
5. Click "Generate token"
6. Copy the token (you won't see it again!)
7. Use the token as your password when pushing

**Option 2: GitHub CLI**
```bash
# Install GitHub CLI first
gh auth login
```

---

## Step 8: Verify on GitHub

1. Go to: `https://github.com/YOUR-USERNAME/memory-limit-manager`
2. You should see all your plugin files
3. README.md will be displayed on the main page

---

## Step 9: Add Repository Topics (Tags)

On your GitHub repository page:

1. Click the ⚙️ (gear icon) next to "About"
2. Add topics:
   - `wordpress`
   - `wordpress-plugin`
   - `php`
   - `memory-management`
   - `wp-config`
   - `memory-limit`
   - `wordpress-admin`
3. Add the website URL: `https://muhammadshakeel.com/memory-limit-manager/`
4. Save changes

---

## Step 10: Create a Release (Optional but Recommended)

### Why Create a Release?
- Makes it easy for users to download specific versions
- Provides a changelog for each version
- Creates downloadable ZIP files automatically

### How to Create a Release

1. **Go to your repository** on GitHub
2. **Click** "Releases" (right sidebar)
3. **Click** "Create a new release"
4. **Fill in the form:**
   - **Tag version:** `v1.0.0` or `1.0.0`
   - **Release title:** `Version 1.0.0 - Initial Release`
   - **Description:** Copy from CHANGELOG.md
5. **Click** "Publish release"

---

## Common Git Commands You'll Need

### Checking Status
```bash
git status                    # Check current status
git log                       # View commit history
git log --oneline            # Compact commit history
```

### Making Changes
```bash
git add .                     # Stage all changes
git add filename.php         # Stage specific file
git commit -m "Message"      # Commit changes
git push                     # Push to GitHub
```

### Viewing Changes
```bash
git diff                     # See unstaged changes
git diff --staged           # See staged changes
```

### Updating from GitHub
```bash
git pull                     # Pull latest changes
```

### Branches
```bash
git branch                   # List branches
git branch feature-name     # Create new branch
git checkout feature-name   # Switch to branch
git merge feature-name      # Merge branch
```

---

## Workflow for Future Updates

### When You Make Changes to Your Plugin:

```bash
# 1. Check what changed
git status

# 2. Stage the changes
git add .

# 3. Commit with descriptive message
git commit -m "Fix: Correct success message display"

# 4. Push to GitHub
git push
```

### For Version Updates:

```bash
# 1. Update version in files:
#    - memory-limit-manager.php (Version: 1.0.1)
#    - readme.txt (Stable tag: 1.0.1)
#    - CHANGELOG.md (add new entry)

# 2. Commit and push
git add .
git commit -m "Release v1.0.1"
git push

# 3. Create new release on GitHub
#    - Tag: v1.0.1
#    - Title: Version 1.0.1
#    - Description: Changelog for this version
```

---

## Best Practices

### Commit Messages

Use clear, descriptive commit messages:

**Good Examples:**
```
Initial commit: Memory Limit Manager v1.0.0
Fix: Success message not displaying on settings page
Add: Support for WordPress 6.5
Update: Improve memory validation logic
Docs: Update README with troubleshooting section
```

**Bad Examples:**
```
update
fixed stuff
changes
test
```

### Commit Often

- Commit after completing each feature
- Commit before making major changes
- Small, focused commits are better than large ones

### Never Commit

- ❌ wp-config.php files
- ❌ Backup files
- ❌ Personal API keys or passwords
- ❌ Large binary files
- ❌ IDE-specific files (.DS_Store, .idea/)

---

## Troubleshooting

### "Permission denied (publickey)"

**Solution:**
Set up SSH keys or use HTTPS with Personal Access Token

### "fatal: not a git repository"

**Solution:**
Run `git init` first

### "Updates were rejected"

**Solution:**
```bash
git pull origin main --rebase
git push
```

### "Your branch is ahead of origin/main"

**Solution:**
```bash
git push
```

### Accidentally Committed Sensitive Files

**Solution:**
```bash
# Remove from git but keep local file
git rm --cached filename

# Add to .gitignore
echo "filename" >> .gitignore

# Commit
git add .gitignore
git commit -m "Remove sensitive file"
git push
```

---

## Adding a GitHub README Badge

Add these badges to your README.md:

```markdown
# Memory Limit Manager

[![WordPress Plugin Version](https://img.shields.io/badge/WordPress-v1.0.0-blue.svg)](https://wordpress.org/plugins/memory-limit-manager/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)

A WordPress plugin to easily manage memory limits...
```

---

## Useful GitHub Features

### 1. GitHub Pages
- Host plugin documentation
- URL: `https://YOUR-USERNAME.github.io/memory-limit-manager/`

### 2. Issues
- Let users report bugs
- Track feature requests
- Manage tasks

### 3. Wiki
- Create extended documentation
- How-to guides
- Developer notes

### 4. Actions
- Automate testing
- Auto-deploy
- Code quality checks

---

## Next Steps

After publishing to GitHub:

1. ✅ Add GitHub URL to your plugin header
2. ✅ Add GitHub link to readme.txt
3. ✅ Update website with GitHub link
4. ✅ Submit to WordPress.org (mention GitHub repo)
5. ✅ Share on social media

---

## Resources

- [GitHub Documentation](https://docs.github.com)
- [Git Documentation](https://git-scm.com/doc)
- [GitHub Desktop](https://desktop.github.com/) - GUI alternative
- [Git Basics Tutorial](https://www.youtube.com/results?search_query=git+basics+tutorial)

---

## Need Help?

If you encounter issues:
- Check [GitHub Community](https://github.community/)
- Review [Git documentation](https://git-scm.com/doc)
- Search for your error message on Google
- Contact me if you need assistance

---

**Congratulations!** 🎉 Your plugin is now on GitHub!

Your repository URL will be:
**https://github.com/YOUR-USERNAME/memory-limit-manager**

---

*Last Updated: January 2026*
