<!--
  - SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Files access control

[![REUSE status](https://api.reuse.software/badge/github.com/nextcloud/files_accesscontrol)](https://api.reuse.software/info/github.com/nextcloud/files_accesscontrol)

Nextcloud's Files access control app enables administrators to protect data from unauthorized access or modifications.

## How it works
The administrator can create and manage a set of rule groups. Each of the rule groups consists of one or more rules. If all rules of a group hold true, the group matches the request and access is being denied or the upload is blocked. The rules criteria range from IP address, mimetype and request time to group membership, tags, user agent and more.

An example would be to deny access to MS Excel/XLSX files owned by the "Human Resources" group accessed from an IP not on the internal company network or to block uploads of files bigger than 512 mb by students in the "1st year" group.

Learn more about File Access Control on [https://nextcloud.com/workflow](https://nextcloud.com/workflow) and in the [Files access control documentation](https://docs.nextcloud.com/server/stable/go.php?to=admin-files-access-control)

![Screenshot](https://raw.githubusercontent.com/nextcloud/files_accesscontrol/master/screenshots/flow.png)
