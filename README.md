# WP Multisite WaaS

<p align="center">
  <img src="https://raw.githubusercontent.com/wpallstars/wp-multisite-waas/main/assets/images/logo.png" alt="WP Multisite WaaS Logo" width="300">
</p>

<p align="center">
  <strong>The Complete Network Solution for transforming your WordPress Multisite into a Website as a Service (WaaS) platform.</strong>
</p>

<p align="center">
  <a href="http://www.gnu.org/licenses/gpl-2.0.html"><img src="https://img.shields.io/badge/License-GPL%20v2-blue.svg" alt="License: GPL v2"></a>
  <a href="https://wordpress.org/"><img src="https://img.shields.io/badge/WordPress-6.7.1%20Tested-green.svg" alt="WordPress: 6.7.1 Tested"></a>
  <a href="https://php.net/"><img src="https://img.shields.io/badge/PHP-7.4.30%2B-purple.svg" alt="PHP: 7.4.30+"></a>
  <a href="https://github.com/superdav42/wp-multisite-waas/releases"><img src="https://img.shields.io/github/v/release/superdav42/wp-multisite-waas" alt="Latest Release"></a>
</p>

## 🌟 Overview

**WP Multisite WaaS** helps you transform your WordPress Multisite installation into a powerful Website as a Service (WaaS) platform. This plugin enables you to offer website creation, hosting, and management services to your customers through a streamlined interface.

This plugin was formerly known as WP Ultimo and is now community maintained.

## ✨ Key Features

- **Site Creation** - Allow customers to create their own sites in your network
- **Domain Mapping** - Support for custom domains with automated DNS verification
- **Payment Processing** - Integrations with popular payment gateways like Stripe and PayPal
- **Plan Management** - Create and manage subscription plans with different features and limitations
- **Template Sites** - Easily clone and use template sites for new customer websites
- **Customer Dashboard** - Provide a professional management interface for your customers
- **White Labeling** - Brand the platform as your own
- **Hosting Integrations** - Connect with popular hosting control panels like cPanel, RunCloud, and more

## 📋 Requirements

- WordPress Multisite 5.3 or higher
- PHP 7.4.30 or higher
- MySQL 5.6 or higher
- HTTPS enabled (recommended for secure checkout)

## 🔧 Installation

There are two recommended ways to install WP Multisite WaaS:

### Method 1: Using the pre-packaged release (Recommended)

1. Download the latest release ZIP from the [Releases page](https://github.com/superdav42/wp-multisite-waas/releases)
2. Log in to your WordPress Network Admin dashboard
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the downloaded ZIP file and click "Install Now"
5. Network Activate the plugin through the 'Plugins' menu in WordPress
6. Follow the step-by-step Wizard to set the plugin up

### Method 2: Using Git and Composer (For developers)

This method requires command-line access to your server and familiarity with Git and Composer.

1. Clone the repository to your plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/superdav42/wp-multisite-waas.git
   cd wp-multisite-waas
   ```

2. Install the required dependencies using Composer:
   ```bash
   composer install
   ```

3. Network Activate the plugin in your WordPress Network Admin dashboard
4. Follow the setup wizard to complete the installation

## 🔍 Common Installation Issues

<details>
<summary><strong>"Failed opening required [...]/vendor/autoload_packages.php"</strong></summary>
<p>This error occurs when the required vendor files are missing. This typically happens when:</p>
<ul>
  <li>You've downloaded the repository directly from GitHub without using a release package</li>
  <li>The composer dependencies haven't been installed</li>
</ul>
<p><strong>Solution:</strong> Use the pre-packaged release from the <a href="https://github.com/superdav42/wp-multisite-waas/releases">Releases page</a> or run <code>composer install</code> in the plugin directory.</p>
</details>

<details>
<summary><strong>"Cannot declare class ComposerAutoloaderInitWPUltimoDependencies, because the name is already in use"</strong></summary>
<p>This error usually occurs when updating from an older version of WP Ultimo or when multiple versions of the plugin are installed.</p>
<p><strong>Solution:</strong> Deactivate and remove any older versions of WP Ultimo or WP Multisite WaaS before activating the new version.</p>
</details>

<details>
<summary><strong>"Class 'WP_Ultimo\Database\Sites\Site_Query' not found"</strong></summary>
<p>This error can occur if the plugin's autoloader isn't properly loading all the necessary classes.</p>
<p><strong>Solution:</strong> Use the pre-packaged release from the <a href="https://github.com/superdav42/wp-multisite-waas/releases">Releases page</a> which includes all required files.</p>
</details>

## 🚀 Contributing

We welcome contributions to WP Multisite WaaS! Here's how you can contribute effectively:

### Development Workflow

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Run `npm install` and `composer install` to set up dependencies
4. Make your changes
5. Before committing, run `npm run build` to:
   - Generate translation POT files
   - Minify CSS and JS assets
   - Process and optimize other assets
6. Commit your changes (`git commit -m 'Add some amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

### Pull Request Guidelines

When submitting pull requests, please:

1. Include a clear description of the changes and their purpose
2. Reference any related issues using GitHub's issue linking syntax (#123)
3. Ensure your code follows the existing style and conventions
4. Include screenshots or GIFs for UI changes if applicable
5. Make sure all tests pass (if available)
6. Update documentation as needed

### Release Process

Releases are automated using GitHub Actions workflows that trigger when a version tag is pushed.

**Tagging Convention:** To trigger a new release build, push a tag following the semantic versioning format:

```bash
git tag v2.3.5  # For example, for version 2.3.5
git push origin v2.3.5
```

The tag must begin with "v" followed by the version number (v*.*.*)

This will automatically:
1. Build the plugin (run `npm run build`)
2. Create a properly packaged ZIP file
3. Create a GitHub release with the ZIP attached

When preparing for a release:
1. Update the version number in the main plugin file and readme.txt
2. Update the changelog in readme.txt
3. Create and push the appropriate version tag

## 🆘 Support

For support, please open an issue on the [GitHub repository](https://github.com/superdav42/wp-multisite-waas/issues).

## ⚠️ Upgrade Notice

We recommend running this in a staging environment before updating your production environment.

## 📝 Recent Changes

### Version [2.3.4] - Released on 2024-01-31
- Fixed: Unable to checkout with any payment gateway
- Fixed: Warning Undefined global variable $pagenow

### Version [2.3.3] - Released on 2024-01-29
- Improved: Plugin renamed to WP Multisite WaaS
- Removed: Enforcement of paid license
- Fixed: Incompatibilities with WordPress 6.7 and i18n timing
- Improved: Reduced plugin size by removing many unnecessary files and shrinking images

For the complete changelog, please see [readme.txt](readme.txt).

## 👥 Contributors

WP Multisite WaaS is an open-source project with contributions from:
- [aanduque](https://github.com/aanduque)
- [superdav42](https://github.com/superdav42)
- [And the community](https://github.com/superdav42/wp-multisite-waas/graphs/contributors)

## 📄 License

WP Multisite WaaS is licensed under the GPL v2 or later.

Copyright © 2024 [WP Multisite WaaS Contributors](https://github.com/superdav42/wp-multisite-waas/graphs/contributors)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA 