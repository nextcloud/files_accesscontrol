<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAccessControl;

use OC\Files\Cache\Wrapper\CacheWrapper as Wrapper;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\ForbiddenException;
use OCP\Files\Storage\IStorage;

class CacheWrapper extends Wrapper {
	protected readonly int $mask;

	public function __construct(
		ICache $cache,
		protected readonly IStorage $storage,
		protected readonly Operation $operation,
	) {
		parent::__construct($cache);
		$this->mask = Constants::PERMISSION_ALL
			& ~Constants::PERMISSION_READ
			& ~Constants::PERMISSION_CREATE
			& ~Constants::PERMISSION_UPDATE
			& ~Constants::PERMISSION_DELETE;
	}

	protected function formatCacheEntry($entry) {
		if (isset($entry['path']) && isset($entry['permissions'])) {
			try {
				$this->operation->checkFileAccess($this->storage, $entry['path'], $entry['mimetype'] === 'httpd/unix-directory', $entry);
			} catch (ForbiddenException) {
				$entry['permissions'] &= $this->mask;
			}
		}
		return $entry;
	}
}
