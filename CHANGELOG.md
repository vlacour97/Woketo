# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).


## [Unreleased]

## [2.0.1] - 2018-02-11
### Added
- Add possibility to retrieve the loop #133

### Fixed
- Fix wrong doc about woketo client

## [2.0.0] - 2017-08-25
### Added
- `WebSocketClient` class that allows you to dial with a standard WebSocket server
- Javascript server that proves that woketo server works with anything (not just autobahn testsuite)

### Changed
- [BC Break] Message handlers now use an `AbstractConnection` class in their methods
- **Internal:** the method `processHandcheck` is now `processHandshake` and follows `AbstractConnection` class requirements
- **Internal:** the `MessageProcessor` now needs to be aware of its quality of client or server.
- **Internal:** the `BitManipulation` class now uses PHP native functions
- **Internal:** the `BitManipulation` class uses new method names


## [1.1.0] - 2017-01-24
### Added
- Support for different handlers depending on specified URI in the request
- Add wss support

### Changed
- [BC Break] in the internal API, the "Connection" signature changed. This should not impact any user though 
- react socket minimum version required upgraded (no bc break)

## [1.0.0] - 2017-01-03
### Added
- First release
- WebSockets server
- Passes autobahn test suite
