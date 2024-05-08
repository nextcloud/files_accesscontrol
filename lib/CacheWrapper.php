<?php
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
	protected Operation $operation;
	protected IStorage $storage;
	protected int $mask;

	/**
	 * @param ICache $cache
	 * @param IStorage $storage
	 * @param Operation $operation
	 */
	public function __construct(ICache $cache, IStorage $storage, Operation $operation) {
		parent::__construct($cache);
		$this->storage = $storage;
		$this->operation = $operation;

		$this->mask = Constants::PERMISSION_ALL;
		$this->mask &= ~Constants::PERMISSION_READ;
		$this->mask &= ~Constants::PERMISSION_CREATE;
		$this->mask &= ~Constants::PERMISSION_UPDATE;
		$this->mask &= ~Constants::PERMISSION_DELETE;
	}

	public const PERMISSION_CREATE = 4;
	public const PERMISSION_READ = 1;
	public const PERMISSION_UPDATE = 2;
	public const PERMISSION_DELETE = 8;

	protected function formatCacheEntry($entry) {
		if (isset($entry['path']) && isset($entry['permissions'])) {
			try {
				$this->operation->checkFileAccess($this->storage, $entry['path'], $entry['mimetype'] === 'httpd/unix-directory', $entry);
			} catch (ForbiddenException $e) {
				$entry['permissions'] &= $this->mask;
			}
		}
		return $entry;
	}
}
