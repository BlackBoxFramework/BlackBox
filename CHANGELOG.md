# BlackBox Framework Changelog

## 0.4.2
Minor bug fixes and enhancements. This update should not have any breaking changes.

- Fixed an ActiveRecord bug
- Improved `Input::has()` method
- Added a `Flash` class for temporary session variables.
- Reverted some changes which made BlackBox incompatible with PHP 5.5
- Fixed a cross-platform autoloading bug.

## 0.4.1
Minor bug fixes and enhancements. This update should not have any breaking changes.

- Fixed strict standards notices for `Singleton` trait and the Cache.
- Added a `delete` method to the Input class.
- Removed a `print_r` from the API Controller.