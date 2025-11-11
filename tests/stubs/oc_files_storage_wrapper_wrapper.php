<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Storage\Wrapper {

	use OCP\Files\Storage\IStorage;
	use OCP\Lock\ILockingProvider;

	class Wrapper implements IStorage {
		/**
		 * @var \OCP\Files\Storage\IStorage $storage
		 */
		protected $storage;

		/**
		 * @param array $parameters
		 */
		public function __construct($parameters) {
		}

		public function getWrapperStorage(): IStorage {
		}

		public function getId() {
		}

		public function mkdir($path) {
		}

		public function rmdir($path) {
		}

		public function opendir($path) {
		}

		public function is_dir($path) {
		}

		public function is_file($path) {
		}

		public function stat($path) {
		}

		public function filetype($path) {
		}

		public function filesize($path): int|float|false {
		}

		public function isCreatable($path) {
		}

		public function isReadable($path) {
		}

		public function isUpdatable($path) {
		}

		public function isDeletable($path) {
		}

		public function isSharable($path) {
		}

		public function getPermissions($path) {
		}

		public function file_exists($path) {
		}

		public function filemtime($path) {
		}

		public function file_get_contents($path) {
		}

		public function file_put_contents($path, $data) {
		}

		public function unlink($path) {
		}

		public function rename($source, $target) {
		}

		public function copy($source, $target) {
		}

		public function fopen($path, $mode) {
		}

		public function getMimeType($path) {
		}

		public function hash($type, $path, $raw = false) {
		}

		public function free_space($path) {
		}

		public function touch($path, $mtime = null) {
		}

		public function getLocalFile($path) {
		}

		public function hasUpdated($path, $time) {
		}

		public function getCache($path = '', $storage = null) {
		}

		public function getScanner($path = '', $storage = null) {
		}

		public function getOwner($path) {
		}

		public function getWatcher($path = '', $storage = null) {
		}

		public function getPropagator($storage = null) {
		}

		public function getUpdater($storage = null) {
		}

		public function getStorageCache() {
		}

		public function getETag($path) {
		}

		public function test() {
		}

		public function isLocal() {
		}

		public function instanceOfStorage($class) {
		}

		/**
		 * @psalm-template T of IStorage
		 * @psalm-param class-string<T> $class
		 * @psalm-return T|null
		 */
		public function getInstanceOfStorage(string $class) {
		}

		/**
		 * Pass any methods custom to specific storage implementations to the wrapped storage
		 *
		 * @param string $method
		 * @param array $args
		 * @return mixed
		 */
		public function __call($method, $args) {
		}

		public function getDirectDownload($path) {
		}

		public function getAvailability() {
		}

		public function setAvailability($isAvailable) {
		}

		public function verifyPath($path, $fileName) {
		}

		public function copyFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		}

		public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		}

		public function getMetaData($path) {
		}

		public function acquireLock($path, $type, ILockingProvider $provider) {
		}

		public function releaseLock($path, $type, ILockingProvider $provider) {
		}

		public function changeLock($path, $type, ILockingProvider $provider) {
		}

		public function needsPartFile() {
		}

		public function writeStream(string $path, $stream, ?int $size = null): int {
		}

		public function getDirectoryContent($directory): \Traversable {
		}

		public function isWrapperOf(IStorage $storage) {
		}

		public function setOwner(?string $user): void {
		}
	}
}
