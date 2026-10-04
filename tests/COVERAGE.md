# PHPUnit coverage

Run `tests/coverage.sh` from any directory. The script runs the full PHPUnit suite and writes `build/coverage/clover.xml` and `build/coverage/html/index.html`. The report includes PHP files under `application/`.

Install PCOV or Xdebug for CLI PHP first. If PCOV is built outside PHP's extension directory, set `AAM_PCOV_EXTENSION` to the absolute path of `pcov.so` when invoking the script. Arguments after the script name are passed to PHPUnit, so `tests/coverage.sh --testsuite Rest` produces a focused REST report.

The core and premium add-on suites have separate coverage reports. Run each repository's script to measure its own source and tests.
