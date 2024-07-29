<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAccessControl;

use OC\Files\Cache\Cache;
use OC\Files\Storage\Storage;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Constants;
use OCP\Files\ForbiddenException;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IWriteStreamStorage;

class StorageWrapper extends Wrapper implements IWriteStreamStorage {
	protected readonly Operation $operation;
	public readonly string $mountPoint;
	protected readonly int $mask;

	/**
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		parent::__construct($parameters);
		$this->operation = $parameters['operation'];
		$this->mountPoint = $parameters['mountPoint'];

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
		$this->operation->checkFileAccess($this, $path, is_bool($isDir) ? $isDir : $this->is_dir($path));
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
	public function mkdir($path) {
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
	public function rmdir($path) {
		$this->checkFileAccess($path, true);
		return $this->storage->rmdir($path);
	}

	/**
	 * check if a file can be created in $path
	 *
	 * @param string $path
	 * @return bool
	 */
	public function isCreatable($path) {
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
	public function isReadable($path) {
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
	public function isUpdatable($path) {
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
	public function isDeletable($path) {
		try {
			$this->checkFileAccess($path);
		} catch (ForbiddenException $e) {
			return false;
		}
		return $this->storage->isDeletable($path);
	}

	public function getPermissions($path) {
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
	 * @return string
	 * @throws ForbiddenException
	 */
	public function file_get_contents($path) {
		$this->checkFileAccess($path, false);
		return $this->storage->file_get_contents($path);
	}

	/**
	 * see http://php.net/manual/en/function.file_put_contents.php
	 *
	 * @param string $path
	 * @param string $data
	 * @return bool
	 * @throws ForbiddenException
	 */
	public function file_put_contents($path, $data) {
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
	public function unlink($path) {
		$this->checkFileAccess($path, false);
		return $this->storage->unlink($path);
	}

	/**
	 * see http://php.net/manual/en/function.rename.php
	 *
	 * @param string $path1
	 * @param string $path2
	 * @return bool
	 * @throws ForbiddenException
	 */
	public function rename($path1, $path2) {
		$isDir = $this->is_dir($path1);
		$this->checkFileAccess($path1, $isDir);
		$this->checkFileAccess($path2, $isDir);
		return $this->storage->rename($path1, $path2);
	}

	/**
	 * see http://php.net/manual/en/function.copy.php
	 *
	 * @param string $path1
	 * @param string $path2
	 * @return bool
	 * @throws ForbiddenException
	 */
	public function copy($path1, $path2) {
		$isDir = $this->is_dir($path1);
		$this->checkFileAccess($path1, $isDir);
		$this->checkFileAccess($path2, $isDir);
		return $this->storage->copy($path1, $path2);
	}

	/**
	 * see http://php.net/manual/en/function.fopen.php
	 *
	 * @param string $path
	 * @param string $mode
	 * @return resource
	 * @throws ForbiddenException
	 */
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
	public function touch($path, $mtime = null) {
		$this->checkFileAccess($path, false);
		return $this->storage->touch($path, $mtime);
	}

	/**
	 * get a cache instance for the storage
	 *
	 * @param string $path
	 * @param Storage (optional) the storage to pass to the cache
	 * @return Cache
	 */
	public function getCache($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		$cache = $this->storage->getCache($path, $storage);
		return new CacheWrapper($cache, $storage, $this->operation);
	}

	/**
	 * A custom storage implementation can return an url for direct download of a give file.
	 *
	 * For now the returned array can hold the parameter url - in future more attributes might follow.
	 *
	 * @param string $path
	 * @return array
	 * @throws ForbiddenException
	 */
	public function getDirectDownload($path) {
		$this->checkFileAccess($path, false);
		return $this->storage->getDirectDownload($path);
	}

	/**
	 * @param IStorage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 * @throws ForbiddenException
	 */
	public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
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
	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		if ($sourceStorage === $this) {
			return $this->rename($sourceInternalPath, $targetInternalPath);
		}

		$this->checkFileAccess($targetInternalPath, $sourceStorage->is_dir($sourceInternalPath));
		return $this->storage->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	/**
	 * @throws ForbiddenException
	 */
	public function writeStream(string $path, $stream, ?int $size = null): int {
		if (!$this->isPartFile($path)) {
			$this->checkFileAccess($path, false);
		}

		$result = $this->storage->writeStream($path, $stream, $size);
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
}
