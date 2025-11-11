<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAccessControl;

use OC\Files\Cache\Wrapper\CacheWrapper as Wrapper;
use OC\Files\Storage\Wrapper\Jail;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\ForbiddenException;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;

class CacheWrapper extends Wrapper {
	protected readonly int $mask;
	protected readonly ?IStorage $storage;

	public function __construct(
		ICache $cache,
		protected readonly IMountPoint $mountPoint,
		protected readonly Operation $operation,
	) {
		parent::__construct($cache);
		$this->storage = $mountPoint->getStorage();
		$this->mask = Constants::PERMISSION_ALL
			& ~Constants::PERMISSION_READ
			& ~Constants::PERMISSION_CREATE
			& ~Constants::PERMISSION_UPDATE
			& ~Constants::PERMISSION_DELETE;
	}

	#[\Override]
	protected function formatCacheEntry($entry) {
		if (isset($entry['path']) && isset($entry['permissions'])) {
			try {
				$storage = $this->storage;
				$path = $entry['path'];
				if ($storage?->instanceOfStorage(Jail::class)) {
					/** @var Jail $storage */
					$jailedPath = $storage->getJailedPath($path);
					$path = $jailedPath ?? $path;
				}
				$this->operation->checkFileAccess($path, $this->mountPoint, $entry['mimetype'] === 'httpd/unix-directory', $entry);
			} catch (ForbiddenException) {
				$entry['permissions'] &= $this->mask;
			}
		}
		return $entry;
	}
}
