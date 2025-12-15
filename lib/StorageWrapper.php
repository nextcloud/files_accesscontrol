<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAccessControl;

use OC\Files\Storage\Storage;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\ForbiddenException;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IWriteStreamStorage;

class StorageWrapper extends Wrapper implements IWriteStreamStorage {
	protected readonly Operation $operation;
	public readonly string $mountPoint;
	protected readonly int $mask;
	protected readonly IMountPoint $mount;

	/**
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		parent::__construct($parameters);
		$this->operation = $parameters['operation'];
		$this->mountPoint = $parameters['mountPoint'];
		$this->mount = $parameters['mount'];

		$this->mask = Constants::PERMISSION_ALL
			& ~Constants::PERMISSION_READ
			& ~Constants::PERMISSION_CREATE
			& ~Constants::PERMISSION_UPDATE
			& ~Constants::PERMISSION_DELETE;
	}

	/**
	 * @throws ForbiddenException
	 */
	protected function checkFileAccess(string $path, ?bool $isDir = null): void {
		$this->operation->checkFileAccess($path, $this->mount, is_bool($isDir) ? $isDir : $this->is_dir($path));
	}

	/*
	 * Storage wrapper methods
	 */

	/**
	 * see http://php.net/manual/en/function.mkdir.php
	 *
	 * @param string $path
	 * @return bool
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function mkdir($path): bool {
		$this->checkFileAccess($path, true);
		return $this->storage->mkdir($path);
	}

	/**
	 * see http://php.net/manual/en/function.rmdir.php
	 *
	 * @param string $path
	 * @return bool
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function rmdir($path): bool {
		$this->checkFileAccess($path, true);
		return $this->storage->rmdir($path);
	}

	/**
	 * check if a file can be created in $path
	 *
	 * @param string $path
	 * @return bool
	 */
	#[\Override]
	public function isCreatable($path): bool {
		try {
			$this->checkFileAccess($path);
		} catch (ForbiddenException $e) {
			return false;
		}
		return $this->storage->isCreatable($path);
	}

	/**
	 * check if a file can be read
	 *
	 * @param string $path
	 * @return bool
	 */
	#[\Override]
	public function isReadable($path): bool {
		try {
			$this->checkFileAccess($path);
		} catch (ForbiddenException $e) {
			return false;
		}
		return $this->storage->isReadable($path);
	}

	/**
	 * check if a file can be written to
	 *
	 * @param string $path
	 * @return bool
	 */
	#[\Override]
	public function isUpdatable($path): bool {
		try {
			$this->checkFileAccess($path);
		} catch (ForbiddenException $e) {
			return false;
		}
		return $this->storage->isUpdatable($path);
	}

	/**
	 * check if a file can be deleted
	 *
	 * @param string $path
	 * @return bool
	 */
	#[\Override]
	public function isDeletable($path): bool {
		try {
			$this->checkFileAccess($path);
		} catch (ForbiddenException $e) {
			return false;
		}
		return $this->storage->isDeletable($path);
	}

	#[\Override]
	public function getPermissions($path): int {
		try {
			$this->checkFileAccess($path);
		} catch (ForbiddenException $e) {
			return $this->mask;
		}
		return $this->storage->getPermissions($path);
	}

	/**
	 * see http://php.net/manual/en/function.file_get_contents.php
	 *
	 * @param string $path
	 * @return string|false
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function file_get_contents($path): string|false {
		$this->checkFileAccess($path, false);
		return $this->storage->file_get_contents($path);
	}

	/**
	 * see http://php.net/manual/en/function.file_put_contents.php
	 *
	 * @param string $path
	 * @param mixed $data
	 * @return int|float|false
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function file_put_contents(string $path, mixed $data): int|float|false {
		$this->checkFileAccess($path, false);
		return $this->storage->file_put_contents($path, $data);
	}

	/**
	 * see http://php.net/manual/en/function.unlink.php
	 *
	 * @param string $path
	 * @return bool
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function unlink($path): bool {
		$this->checkFileAccess($path, false);
		return $this->storage->unlink($path);
	}

	/**
	 * see http://php.net/manual/en/function.rename.php
	 *
	 * @param string $source
	 * @param string $target
	 * @return bool
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function rename($source, $target): bool {
		$isDir = $this->is_dir($source);
		$this->checkFileAccess($source, $isDir);
		$this->checkFileAccess($target, $isDir);
		return $this->storage->rename($source, $target);
	}

	/**
	 * see http://php.net/manual/en/function.copy.php
	 *
	 * @param string $path1
	 * @param string $path2
	 * @return bool
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function copy($source, $target): bool {
		$isDir = $this->is_dir($source);
		$this->checkFileAccess($source, $isDir);
		$this->checkFileAccess($target, $isDir);
		return $this->storage->copy($source, $target);
	}

	/**
	 * see http://php.net/manual/en/function.fopen.php
	 *
	 * @param string $path
	 * @param string $mode
	 * @return resource|false
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function fopen($path, $mode) {
		$this->checkFileAccess($path, false);
		return $this->storage->fopen($path, $mode);
	}

	/**
	 * see http://php.net/manual/en/function.touch.php
	 * If the backend does not support the operation, false should be returned
	 *
	 * @param string $path
	 * @param int $mtime
	 * @return bool
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function touch($path, $mtime = null): bool {
		$this->checkFileAccess($path, false);
		return $this->storage->touch($path, $mtime);
	}

	/**
	 * get a cache instance for the storage
	 *
	 * @param string $path
	 * @param Storage (optional) the storage to pass to the cache
	 * @return ICache
	 */
	#[\Override]
	public function getCache($path = '', $storage = null): ICache {
		if (!$storage) {
			$storage = $this;
		}
		$cache = $this->storage->getCache($path, $storage);
		return new CacheWrapper($cache, $this->mount, $this->operation);
	}

	/**
	 * A custom storage implementation can return a url for direct download of a give file.
	 *
	 * For now the returned array can hold the parameter url - in future more attributes might follow.
	 *
	 * @param string $path
	 * @return array{expiration: ?int, url: ?string}|false
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function getDirectDownload($path): array|false {
		$this->checkFileAccess($path, false);
		return $this->storage->getDirectDownload($path);
	}

	/**
	 * A custom storage implementation can return a url for direct download of a give file.
	 *
	 * For now the returned array can hold the parameter url - in future more attributes might follow.
	 *
	 * @param string $fileId
	 * @return array{expiration: ?int, url: ?string}|false
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function getDirectDownloadById(string $fileId): array|false {
		// We first check if something is providing direct download, to save ID to path resolution
		$data = $this->storage->getDirectDownloadById($fileId);
		if ($data === false) {
			return false;
		}

		// We would have actually a result, so lets see if the user should be able to access it
		$path = $this->getCache()->getPathById((int)$fileId);
		if ($path !== null) {
			$this->checkFileAccess($path, false);
		}

		return $data;
	}

	/**
	 * @param IStorage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath): bool {
		if ($sourceStorage === $this) {
			return $this->copy($sourceInternalPath, $targetInternalPath);
		}

		$this->checkFileAccess($targetInternalPath, $sourceStorage->is_dir($sourceInternalPath));
		return $this->storage->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	/**
	 * @param IStorage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath): bool {
		if ($sourceStorage === $this) {
			return $this->rename($sourceInternalPath, $targetInternalPath);
		}

		$this->checkFileAccess($targetInternalPath, $sourceStorage->is_dir($sourceInternalPath));
		return $this->storage->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	/**
	 * @throws ForbiddenException
	 */
	#[\Override]
	public function writeStream(string $path, $stream, ?int $size = null): int {
		if (!$this->isPartFile($path)) {
			$this->checkFileAccess($path, false);
		}

		$result = parent::writeStream($path, $stream, $size);
		if (!$this->isPartFile($path)) {
			return $result;
		}

		// Required for object storage since part file is not in the storage so we cannot check it before moving it to the storage
		// As an alternative we might be able to check on the cache update/insert/delete though the Cache wrapper
		try {
			$this->checkFileAccess($path, false);
		} catch (\Exception $e) {
			$this->storage->unlink($path);
			throw $e;
		}
		return $result;
	}

	private function isPartFile(string $path): bool {
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		return $extension === 'part';
	}

	public function getMount(): IMountPoint {
		return $this->mount;
	}
}
