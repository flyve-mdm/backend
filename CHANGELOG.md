<a name="1.1.1"></a>
## [1.1.1](https://github.com/flyve-mdm/flyve-mdm-glpi/compare/v1.1.0...v1.1.1) (2017-01-26)



<a name="1.1.1"></a>
## [1.1.1](https://github.com/flyve-mdm/flyve-mdm-glpi/compare/v1.1.0...v1.1.1) (2017-01-26)



<a name="1.1.0"></a>
# 1.1.0 (2017-01-25)


### Bug Fixes

* fix save of firstname and lastname from devices upon enrollment ([2e6e824](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/2e6e824))
* update URL of the agent for Android ([ecae936](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/ecae936))
* **api:** fix error message ([c721a07](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/c721a07))
* **api:** geolocation always rejected ([4c70781](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/4c70781))
* **documentation:** add installation instructions ([a288e1b](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/a288e1b)), closes [#4](https://github.com/flyve-mdm/flyve-mdm-glpi/issues/4)
* **documentation:** enhance installation documentation ([ad9dd6d](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/ad9dd6d))
* **documentation:** github template for issues ([d9ad78f](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/d9ad78f))
* **enrollment:** use login isntead of email to find a user ([617425f](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/617425f))
* **install:** add check for gd  extension for PHP ([64a2682](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/64a2682)), closes [#9](https://github.com/flyve-mdm/flyve-mdm-glpi/issues/9)
* **install:** prevent warning ([13a5283](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/13a5283))
* **policy:** fix error 500 when parsing a bad UPK file ([c71d434](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/c71d434))
* **user account:** use constant for default agent application download URL ([c746a9f](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/c746a9f))


### Features

* add demo mode ([949f7c2](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/949f7c2))
* **api:** expose configuration settings ([e9cd70d](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/e9cd70d))
* **ci:** add webhooks ([8c04079](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/8c04079))
* **device communication:** log inventory errors ([9fc734f](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/9fc734f))
* **enhancement:** cleanup invitations when purging a user ([63570ad](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/63570ad))
* **enrollment:** bug collector settings ([4ec7a62](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/4ec7a62))
* **enrollment:** handle version of agent sent in enrollment process ([f8be502](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/f8be502))
* **initial release:** v1.0.0 ([f85b9d4](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/f85b9d4))
* **policy:** log filesystem permission issues when saving a file or an app ([b491bcc](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/b491bcc))
* **policy:** prevent conflicting policies to be applied at the same time ([b9a881a](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/b9a881a))
* **policy:** queue mqtt mssage instead of sending it immediately ([8508b85](https://github.com/flyve-mdm/flyve-mdm-glpi/commit/8508b85))
