<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\GroupFolders\Trash {
	class TrashBackend {
		public function setupTrashFolder(string $class, string $value, $closure) {
		}
	}
}

namespace OCA\GroupFolders\Mount {
	abstract class GroupMountPoint implements \OCP\Files\Mount\IMountPoint {
	}
}
