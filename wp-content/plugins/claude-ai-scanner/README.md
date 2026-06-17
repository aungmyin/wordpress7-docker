# Claude AI Scanner for WordPress 7

A comprehensive WordPress site health analyzer that uses Claude AI to scan your entire installation for security vulnerabilities, performance bottlenecks, SEO issues, and code quality problems with specific, actionable recommendations.

## Core Features

✅ **Full Site Health Analysis** — Scan entire WordPress installation  
✅ **Performance Diagnostics** — Identify bottlenecks and optimization opportunities  
✅ **Security Assessment** — Detect vulnerabilities and configuration issues  
✅ **SEO Analysis** — Check links, meta tags, structured data, and 404 risks  
✅ **Code Quality Review** — Evaluate code standards and best practices  
✅ **WordPress 7.0 Compatibility** — Ensure compatibility with latest WP version  
✅ **Detailed Recommendations** — Get specific fixes with code snippets and expected impact

## What's New in v2.0

- **Full Site Health Scanning** — Comprehensive analysis of your entire WordPress installation
- **Performance Analysis Dashboard** — Real-time metrics and optimization recommendations
- **Error Categorization** — Issues grouped by type (Security, Performance, SEO, Code Quality)
- **Specific Recommendations** — Exact steps to fix issues with code snippets
- **Impact Estimates** — See expected improvements (e.g., "30% faster load time")
- **Priority Levels** — Know which issues to fix first

## Installation

1. Activate the plugin in WordPress admin
2. Go to "AI Scanner" → "Settings"
3. Get your Claude API key from https://console.anthropic.com/
4. Paste your API key and save
5. Go back to "AI Scanner" → "Scanner" to start analyzing

## Plugin Pages

### Scanner
Scan individual plugins and themes for detailed analysis:
- Security vulnerabilities
- Code quality issues
- Documentation completeness
- WordPress 7.0 compatibility
- SEO and link health

### Site Health
Run a comprehensive analysis of your entire WordPress installation:
- Security posture assessment
- Database and filesystem review
- Active plugins analysis
- Configuration review
- Recommendations for hardening

**Analysis Includes:**
- 🔍 Security vulnerabilities and fixes
- ⚡ Performance bottlenecks
- 🔗 Broken links and 404 risks
- 📚 Documentation quality
- ⚙️ Compatibility issues

### Performance
Detailed performance optimization analysis:
- **Real-Time Metrics** — Database size, plugin count, memory usage
- **Optimization Analysis** — Database cleanup, caching strategy, code optimization
- **Resource Review** — Memory limits, upload sizes, timeouts
- **Actionable Recommendations** — Specific fixes with estimated impact

**Recommendations Cover:**
- Database optimization (indexes, cleanup queries)
- Caching strategy (object, page, browser caching)
- Image optimization (size, format, lazy loading)
- Code performance (minification, asset loading)
- Resource limits (PHP memory, timeouts)

### Settings
Configure your Claude API key and manage authentication.

## How to Use

### Get Your Claude API Key

1. Visit https://console.anthropic.com/
2. Sign up or log in with your Anthropic account
3. Create an API key in the "API Keys" section
4. Copy the key

### Configure the Plugin

1. In WordPress admin, go to **AI Scanner** → **Settings**
2. Paste your Claude API key in the input field
3. Click "Save API Key"

### Scan Your Plugins & Themes

1. Go to **AI Scanner** → **Scanner**
2. You'll see a list of installed plugins and themes
3. Click "Scan with AI" next to any plugin or theme
4. Wait for Claude to analyze it (usually 10-30 seconds)
5. Review the detailed analysis in the modal popup

## What Claude Analyzes

Each scan provides analysis in five areas:

### 1. Security
- Potential vulnerabilities
- Unsafe functions or patterns
- Security best practices compliance
- Data handling and sanitization

### 2. Code Quality
- Code structure and organization
- Adherence to WordPress coding standards
- Maintainability and readability
- Use of modern PHP features

### 3. Documentation
- Inline code documentation
- README file completeness
- API documentation
- User-facing help and guides

### 4. WordPress 7.0 Compatibility
- Deprecated function usage
- Compatibility with new WordPress 7.0 features
- Recommendations for updating to latest standards
- Opportunities to use new WordPress 7.0 APIs

### 5. SEO & Link Health
- **404 Risk Detection** — Identifies hardcoded URLs that might break
- **Redirect Analysis** — Checks for proper 301/302 redirect implementations
- **Meta Tags** — Reviews meta descriptions, keywords, and robots tags
- **Structured Data** — Analyzes Schema.org and JSON-LD implementation
- **Canonical Tags** — Verifies canonical tag usage
- **Link Rot** — Detects broken links and redirect chains
- **URL Patterns** — Identifies fragile or third-party URL dependencies
- **Redirect Chains** — Finds inefficient redirect chains that hurt SEO

## Requirements

- WordPress 7.0+
- PHP 8.0+
- Active internet connection
- Valid Claude API key

## API Key Security

- Your API key is stored securely in WordPress options
- Never share your API key publicly
- The plugin masks your key in the admin interface
- Rotate your API key regularly in Anthropic Console

## Cost Considerations

Each scan uses Claude's API and will incur costs based on your Anthropic account plan. Check your current plan at https://console.anthropic.com/

## Understanding the Reports

### Issue Categories

Each report categorizes issues by:

**🔴 Critical** — Fix immediately (security risks, data loss risks)  
**🟠 High** — Fix soon (performance degradation, broken functionality)  
**🟡 Medium** — Fix when possible (code improvements, warnings)  
**🟢 Low** — Nice to have (best practices, optimization opportunities)

### Report Sections

Each full site health report includes:

1. **Executive Summary** — Overall health score and key issues
2. **Security Assessment** — Vulnerabilities and hardening steps
3. **Performance Analysis** — Bottlenecks and optimization opportunities
4. **SEO Health** — Meta tags, links, structured data
5. **Code Quality** — Plugin conflicts, deprecated functions
6. **WordPress 7.0 Readiness** — Compatibility and upgrade path

Each issue includes:
- ✓ Specific problem description
- ✓ Root cause analysis
- ✓ Exact fix steps (with code snippets)
- ✓ Expected impact (e.g., "Reduces database size by 50MB")
- ✓ Priority level
- ✓ Difficulty to implement

### Performance Report Example

**Issue:** Large database with unoptimized queries

**Root Cause:** Post revisions not cleaned up, transients accumulating

**Fix Steps:**
```php
// Clean up post revisions
DELETE FROM wp_posts WHERE post_type = 'revision';

// Clean up transients
DELETE FROM wp_options WHERE option_name LIKE '%_transient_%';
```

**Expected Impact:** 40% faster database queries, 200MB space freed

**Priority:** High | **Difficulty:** Easy

## Support

For issues or questions:
1. Check that your API key is correct and active
2. Ensure you have sufficient credits in your Anthropic account
3. Verify your internet connection is working
4. Check WordPress error logs for detailed error messages

## Tips for Best Results

1. **Run scans regularly** — Weekly or before major updates
2. **Implement high-priority fixes first** — They have the most impact
3. **Test in staging** — Always test recommendations in a staging environment
4. **Monitor metrics** — Re-run performance scans after fixes to measure impact
5. **Keep plugins updated** — Run scanner again after updating plugins

## License

GPL v2 or later

## Author

WordPress Developer

## Changelog

### v2.0.0
- Added full site health analysis
- Added performance analysis and diagnostics
- Added detailed recommendations with code snippets
- Added issue categorization and priority levels
- Added real-time performance metrics
- Improved API integration and error handling

### v1.0.0
- Initial release
- Plugin and theme scanning
- SEO analysis
- Security checks
