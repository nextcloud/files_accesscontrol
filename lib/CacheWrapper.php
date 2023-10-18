<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\FilesAccessControl;

use OC\Files\Cache\Wrapper\CacheWrapper as Wrapper;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\ForbiddenException;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;

class CacheWrapper extends Wrapper {
	protected Operation $operation;
	protected IStorage $storage;
	protected IMountPoint $mountPoint;
	protected int $mask;

	/**
	 * @param ICache $cache
	 * @param IStorage $storage
	 * @param Operation $operation
	 */
	public function __construct(ICache $cache, IStorage $storage, Operation $operation, IMountPoint $mountPoint) {
		parent::__construct($cache);
		$this->storage = $storage;
		$this->operation = $operation;
		$this->mountPoint = $mountPoint;

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
				$this->operation->checkFileAccess($this->storage, $entry['path'], $this->mountPoint, $entry['mimetype'] === 'httpd/unix-directory', $entry);
			} catch (ForbiddenException $e) {
				$entry['permissions'] &= $this->mask;
			}
		}
		return $entry;
	}
}
