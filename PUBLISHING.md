# Publishing MediaMan UI Components to npm

This guide explains how to publish the MediaMan UI components (@mediaman/core, @mediaman/vue, @mediaman/react) to npm.

## Prerequisites

1. **npm account**: Create an account at https://www.npmjs.com/signup
2. **npm authentication**: Login to npm from command line
   ```bash
   npm login
   ```
3. **Package scope**: The packages use the `@mediaman` scope. Ensure you have permission to publish to this scope.

## Package Structure

```
packages/
├── core/          → @mediaman/core
├── vue/           → @mediaman/vue
└── react/         → @mediaman/react
```

## Publishing Steps

### 1. Install Dependencies

```bash
# Install root dependencies and all workspace dependencies
npm run install:all
```

### 2. Build All Packages

```bash
# Build all packages
npm run build

# Or build individually
npm run build:core
npm run build:vue
npm run build:react
```

### 3. Version Bump (Optional)

Update version numbers across all packages:

```bash
# Patch version (1.0.0 → 1.0.1)
npm run version:patch

# Minor version (1.0.0 → 1.1.0)
npm run version:minor

# Major version (1.0.0 → 2.0.0)
npm run version:major
```

### 4. Publish to npm

```bash
# Publish all packages at once (recommended)
npm run publish:all

# Or publish individually
npm run publish:core
npm run publish:vue
npm run publish:react
```

## Publishing Individual Packages

If you want to publish packages separately:

### @mediaman/core

```bash
cd packages/core
npm run build
npm publish --access public
```

### @mediaman/vue

```bash
cd packages/vue
npm run build
npm publish --access public
```

### @mediaman/react

```bash
cd packages/react
npm run build
npm publish --access public
```

## Important Notes

### Package Dependencies

- **@mediaman/vue** and **@mediaman/react** depend on **@mediaman/core**
- Publish **@mediaman/core** first before publishing Vue or React packages
- If you update core, you may need to update the version in Vue and React packages

### Access Permissions

All packages are published with `--access public` flag because they use the `@mediaman` scope.

### Versioning Strategy

Follow semantic versioning (semver):
- **MAJOR** (1.0.0 → 2.0.0): Breaking changes
- **MINOR** (1.0.0 → 1.1.0): New features, backwards compatible
- **PATCH** (1.0.0 → 1.0.1): Bug fixes, backwards compatible

### Pre-publish Checklist

Before publishing, ensure:

- [ ] All tests pass
- [ ] Build succeeds (`npm run build`)
- [ ] README files are up to date
- [ ] Version numbers are correct
- [ ] CHANGELOG is updated (if maintaining one)
- [ ] No sensitive information in package files

## Troubleshooting

### Permission Denied

If you get a permission error:
```bash
npm login
# Enter your npm credentials
```

### Package Already Exists

If the version already exists on npm:
```bash
# Bump the version
cd packages/core  # or vue/react
npm version patch
```

### Build Errors

If build fails:
```bash
# Clean and rebuild
npm run clean
npm run install:all
npm run build
```

## Automated Publishing with GitHub Actions

You can automate publishing using GitHub Actions. Create `.github/workflows/publish.yml`:

```yaml
name: Publish to npm

on:
  release:
    types: [created]

jobs:
  publish:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'
          registry-url: 'https://registry.npmjs.org'

      - name: Install dependencies
        run: npm run install:all

      - name: Build packages
        run: npm run build

      - name: Publish to npm
        run: npm run publish:all
        env:
          NODE_AUTH_TOKEN: ${{ secrets.NPM_TOKEN }}
```

Add your npm token as a GitHub secret named `NPM_TOKEN`.

## Development Workflow

### Local Development

```bash
# Start development mode (watches for changes)
npm run dev:core     # in one terminal
npm run dev:vue      # in another terminal
npm run dev:react    # in another terminal
```

### Linking Packages Locally

To test packages locally before publishing:

```bash
# In core package
cd packages/core
npm link

# In your test project
cd /path/to/test/project
npm link @mediaman/core
```

## Support

If you encounter issues:
1. Check the [npm documentation](https://docs.npmjs.com/)
2. Review package.json files for correct configuration
3. Ensure all peer dependencies are correctly specified
4. Open an issue on GitHub if problems persist

## Next Steps After Publishing

1. Update main README with npm installation instructions
2. Announce new release on Twitter/social media
3. Update documentation website (if applicable)
4. Notify users of breaking changes (if any)
