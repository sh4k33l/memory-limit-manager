# GitHub Quick Start - Copy & Paste Commands

**Quick reference for adding Memory Limit Manager to GitHub**

---

## Prerequisites

- Create repository on GitHub: https://github.com/new
  - Name: `memory-limit-manager`
  - Public repository
  - License: GNU General Public License v2.0
  - Don't initialize with README

---

## Commands to Run (Copy & Paste)

Open Terminal and run these commands one by one:

### 1. Navigate to Plugin Folder

```bash
cd /Users/muhammadshakeel/Downloads/LearnDash/memory-limit-manager
```

### 2. Initialize Git

```bash
git init
```

### 3. Check Files (.gitignore already created)

```bash
git status
```

### 4. Stage All Files

```bash
git add .
```

### 5. Make First Commit

```bash
git commit -m "Initial commit: Memory Limit Manager v1.0.0"
```

### 6. Connect to GitHub

**⚠️ Replace `YOUR-USERNAME` with your actual GitHub username:**

```bash
git remote add origin https://github.com/YOUR-USERNAME/memory-limit-manager.git
```

**Example (if your username is shak33l):**
```bash
git remote add origin https://github.com/shak33l/memory-limit-manager.git
```

### 7. Set Branch Name

```bash
git branch -M main
```

### 8. Push to GitHub

```bash
git push -u origin main
```

**Note:** You'll be asked for username and password:
- Username: Your GitHub username
- Password: Use a Personal Access Token (not your actual password)
  - Get token at: https://github.com/settings/tokens
  - Click "Generate new token (classic)"
  - Select scope: `repo`
  - Copy and use the token as password

---

## Verify

Visit your repository:
```
https://github.com/YOUR-USERNAME/memory-limit-manager
```

You should see all your plugin files!

---

## Future Updates - Quick Commands

When you make changes to your plugin:

```bash
# Check what changed
git status

# Stage all changes
git add .

# Commit with message
git commit -m "Description of what you changed"

# Push to GitHub
git push
```

---

## Common Scenarios

### Scenario: Fixed a bug

```bash
git add .
git commit -m "Fix: Success message not displaying"
git push
```

### Scenario: Added new feature

```bash
git add .
git commit -m "Add: Memory usage monitoring feature"
git push
```

### Scenario: Updated documentation

```bash
git add .
git commit -m "Docs: Update README with new instructions"
git push
```

### Scenario: Released new version

```bash
# After updating version numbers in files
git add .
git commit -m "Release v1.0.1"
git push

# Then create a release on GitHub:
# https://github.com/YOUR-USERNAME/memory-limit-manager/releases/new
```

---

## That's It!

For detailed explanations, see: `GITHUB-GUIDE.md`

---

**Your GitHub Repository URL:**
```
https://github.com/YOUR-USERNAME/memory-limit-manager
```
