# Claude AI Scanner for WordPress 7

A WordPress plugin that uses Claude AI to analyze your plugins and themes for security, code quality, documentation, and WordPress 7.0 compatibility.

## Features

✅ **Security Analysis** — Detect potential vulnerabilities and unsafe practices  
✅ **Code Quality Review** — Evaluate code structure and best practices  
✅ **Documentation Assessment** — Check documentation completeness  
✅ **WordPress 7.0 Compatibility** — Identify deprecated functions and new opportunities  
✅ **User-Friendly Admin UI** — Easy configuration and scanning interface

## Installation

1. Activate the plugin in WordPress admin
2. Go to "AI Scanner" → "Settings"
3. Get your Claude API key from https://console.anthropic.com/
4. Paste your API key and save
5. Go back to "AI Scanner" → "Scanner" to start analyzing

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

Each scan provides analysis in four areas:

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

## Support

For issues or questions:
1. Check that your API key is correct and active
2. Ensure you have sufficient credits in your Anthropic account
3. Verify your internet connection is working

## License

GPL v2 or later

## Author

WordPress Developer
