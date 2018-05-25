# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Automatically extract JSON result array based on the `result_type` attribute.
- Automatically assign the company as debtor in `company_to_user` and as creditor in `user_to_company` transaction types.
- Validation of all input parameters, throwing `InvalidArgumentException` when invalid.

### Changed 
- Supports v1.0 of the OST KIT REST API (list filters still need some attention).

## [0.9.2] - 2018-05-19
### Added
- Initial implementation of v0.9.2 of the OST KIT REST API.
- Token balance retrieval for a user.


[Unreleased]: https://github.com/realJayNay/ost-kit-php-client/compare/v0.9.2...HEAD
[0.9.2]: https://github.com/realJayNay/ost-kit-php-client/compare/...v0.9.2