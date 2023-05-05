<!-- <h1 align="center"><img src="/views/img/logo.png" alt="Blocks" width="500"></h1> -->

# Blocks for PrestaShop

[![PHP tests](https://github.com/Kaudaj/kjblocks/actions/workflows/php.yml/badge.svg)](https://github.com/Kaudaj/kjblocks/actions/workflows/php.yml)
[![GitHub release](https://img.shields.io/github/release/Kaudaj/kjblocks.svg)](https://GitHub.com/Kaudaj/kjblocks/releases/)
[![GitHub license](https://img.shields.io/github/license/Kaudaj/kjblocks)](https://github.com/Kaudaj/kjblocks/LICENSE.md)

## About

*Blocks* module allows you to add fully configurable blocks to render custom content in your shop, wherever you want.

## Essential features

- Blocks
- Block groups
- Many block types available through extensions
- Designed to be very extensible

## Usage

### Installation

**Get started**

From PrestaShop installation root:

```bash
cd modules
git clone https://github.com/Kaudaj/kjblocks.git
cd kjblocks
composer install
cd _dev
npm install
npm run build
cd ../../..
bin/console pr:mo install kjblocks
```

### Configuration

- `tests/php/.phpstan_bootstrap_config.php`<br>
For GrumPHP: Set PrestaShop installation path for PHPStan task.<br>
Replace default path with the root path of a stable PrestaShop environment.

### Commands

Here are some useful commands you could need during your development workflow:

- `composer grum`<br>
Run GrumPHP tasks suite.
- `composer header-stamp`<br>
Add license headers to files.
- `composer autoindex`<br>
Add index files in directories.
- `composer dumpautoload -a`<br>
Update the autoloader when you add new classes in a classmap package (`src` and `tests` folder here).
- `npm run watch`<br>
  (in `_dev` folder) Watch for changes in `_dev` folder and build automatically the assets in `views` folder. It's recommended to run it in background, in a dedicated terminal.
- `npm run lint-fix`<br>
  (in `_dev` folder) Run ESLint fixer
- `npm run prettier-fix`<br>
  (in `_dev` folder) Run Prettier formatting

### Extensions

If you want to add your own block type, start with [kjblocksexampleextension][example-extension].
It's a documented example extension which add an example type. Check this repository to know how to start!

## Compatibility

|     |     |
| --- | --- |
| PrestaShop | **>=1.7.8.0** |
| PHP        | **>=7.1** for production and **>=7.3** for development |
| Multishop | :heavy_check_mark: |

## License

[Academic Free License 3.0][afl-3.0].

## Reporting issues

You can [report issues][report-issue] in this very repository.

## Contributing

As it is an open source project, everyone is welcome and even encouraged to contribute with their own improvements!

To contribute in the best way possible, you want to follow the [PrestaShop contribution guidelines][contribution-guidelines].

## Contact

Feel free to contact us by email at info@kaudaj.com.

[report-issue]: https://github.com/Kaudaj/kjblocks/issues/new/choose
[prestashop]: https://www.prestashop.com/
[contribution-guidelines]: https://devdocs.prestashop.com/1.7/contribute/contribution-guidelines/project-modules/
[afl-3.0]: https://opensource.org/licenses/AFL-3.0
[example-extension]: https://github.com/Kaudaj/kjblocksexampleextension
