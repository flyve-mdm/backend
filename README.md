# [Flyve MDM](https://flyve-mdm.com)

[![Project Status: Active - The project has reached a stable, usable state and is being actively developed.](http://www.repostatus.org/badges/latest/active.svg)](http://www.repostatus.org/#active)
[![Build Status](https://travis-ci.org/flyve-mdm/flyve-mdm-glpi.svg?branch=master)](https://travis-ci.org/flyve-mdm/flyve-mdm-glpi)

Flyve MDM is a mobile device management software that enables you to secure and manage all the mobile devices of your business via a unique web-based console (MDM).

To get started, check out <https://flyve-mdm.com/>!

# Table of contents

* [Installation](#Installation)
* [Bugs and features requests](#Bugs-and-features-requests)
* [Contributing](#Contributing)
* [Community](#Community)
* [Versioning](#Versioning)
* [Roadmap](#Roadmap)
* [Disclaimer](#Disclaimer)
* [Creators](#Creators)
* [Copyright and license](#Copyright and license)


# Overview

Flyve MDM Plugin for GLPi is a subproject of Flyve MDM. Flyve MDM is a mobile device management software.

It provides a standalone web based administration and a interface integrated in GLPI. The later one may be the preferred by those who use GLPI for asset maangement and ticketing.

# Requirements


|GLPI|9.1.1|9.1.2|9.1.3|9.2.0|
|:---|-----|-----|-----|-----|
|Flyve MDM|:x:|:x:|1.x.x|:x:|
|FusionInventory|9.1-1.1|9.1-1.1|9.1-1.1|:x:|

# Installation

Read the [installation instructions in the wiki](https://github.com/flyve-mdm/flyve-mdm-glpi/wiki/Installation)

# Bugs and features requests

You have a bug or a feature request? Please first search for existing and closed issues. If your problem or idea is not addressed yet, [please open a new issue](http://github.com/flyve-mdm/flyve-mdm-glpi/issues/new).

# Contributing

Please read our [contributing guidelines](/.github/CONTRIBUTING.md). Included are directions for opening issues, coding standards, and notes on development.

If you are not a developer you may contribute the project by [translating it](https://www.transifex.com/flyve-mdm/), by submitting documentation or by testing the product.

## Tests

* Go to the folder containing GLPi
* Run composer install
* Run php tools/cliinstall.php --tests --user=database-user --pass=database-pass --db=glpi-test
* Go to plugins/flyvemdm
* Run php tools/cliinstall.php --tests
* Run phpunit

# Community

Get updates on Flyve MDN's development and chat with the project maintainers and community members.

* Follow [@FlyveMDM on Twitter](https://twitter.com/flyvemdm).
* Like [Flyve MDM on Facebook](https://www.facebook.com/Flyve-MDM-1625450937768377/).
* Read and subscribe to [the Teclib' blog](http://www.teclib-edition.com/en/communities/blog-posts/).
* Join [the IRC channel](irc://irc.freenode.org/flyve-mdm).
* Implementation help may be found at Stack Overflow (tagged [`flyve-mdm`](https://stackoverflow.com/questions/tagged/flyve-mdm)).
* [Flyve MDM on Wikipedia](https://fr.wikipedia.org/wiki/Flyve_MDM).

# Versioning

In order to provide transparency on our release cycle and to maintain backward compatibility, Flyve MDM is maintained under [the Semantic Versioning guidelines](http://semver.org/). We are committed to following and complying with the rules, the best we can.

See [the tags section of our GitHub project](http://github.com/flyve-mdm/flyve-mdm-glpi/tags) for changelogs for each release version of Flyve MDM. Release announcement posts on [the official Teclib' blog](http://www.teclib-edition.com/en/communities/blog-posts/) contain summaries of the most noteworthy changes made in each release.

# Roadmap

We are eager to build together the best software in the IT industry, for mobile device management. In order to help you prioritize what to focus on and to share insights on our priorities, we have published Flyve MDM’s Platform Roadmap. This roadmap is a rough estimate of what we are planning on building in the near-, medium-, and long-term.

Placement on the roadmap represents our estimate of when each project will enter full production release. Some larger features will land in your hands through Early Access programs in advance of their full release.

The items in the roadmap are subject to change or delay, so you should not use this for major planning purposes. We will do our best to keep the roadmap updated, and we will review the roadmap monthly to ensure it reflects our most up-to-date features inventory.

You can follow the progress on the opened and closed issues, opened and closed merge requests, percentage of completion and remaining days, in the following links of every milestone.

# Disclaimer

This roadmap is for informational purposes only, so you shouldn't rely on this information for major purchasing or planning purposes. Just like all projects, the items in the roadmap are subject to change or delay, and the continued development or release of a project on the roadmap is at the sole discretion of Teclib'.

# Creators

## Thierry Bugier Pineau

* <https://github.com/btry>

# Copyright and license

Code and documentation copyright 2015-2017 Teclib'. Code released under [the AGPL v3 license](/LICENSE.md). Docs released under [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/).
