<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Storage\Wrapper {

	use OCP\Files\Cache\ICache;
	use OCP\Files\Cache\IPropagator;
	use OCP\Files\Cache\IScanner;
	use OCP\Files\Cache\IUpdater;
	use OCP\Files\Cache\IWatcher;
	use OCP\Files\Storage\IStorage;
	use OCP\Lock\ILockingProvider;

	class Wrapper implements IStorage {
		/** @var IStorage */
		protected $storage;

		public function __construct(array $parameters) {
		}

		public function getWrapperStorage(): IStorage {
		}

		public function getId(): string {
		}

		public function mkdir(string $path): bool {
		}

		public function rmdir(string $path): bool {
		}

		public function opendir(string $path) {
		}

		public function is_dir(string $path): bool {
		}

		public function is_file(string $path): bool {
		}

		public function stat(string $path): array|false {
		}

		public function filetype(string $path): string|false {
		}

		public function filesize(string $path): int|float|false {
		}

		public function isCreatable(string $path): bool {
		}

		public function isReadable(string $path): bool {
		}

		public function isUpdatable(string $path): bool {
		}

		public function isDeletable(string $path): bool {
		}

		public function isSharable(string $path): bool {
		}

		public function getPermissions(string $path): int {
		}

		public function file_exists(string $path): bool {
		}

		public function filemtime(string $path): int|false {
		}

		public function file_get_contents(string $path): string|false {
		}

		public function file_put_contents(string $path, mixed $data): int|float|false {
		}

		public function unlink(string $path): bool {
		}

		public function rename(string $source, string $target): bool {
		}

		public function copy(string $source, string $target): bool {
		}

		public function fopen(string $path, string $mode) {
		}

		public function getMimeType(string $path): string|false {
		}

		public function hash(string $type, string $path, bool $raw = false): string|false {
		}

		public function free_space(string $path): int|float|false {
		}

		public function touch(string $path, ?int $mtime = null): bool {
		}

		public function getLocalFile(string $path): string|false {
		}

		public function hasUpdated(string $path, int $time): bool {
		}

		public function getCache(string $path = '', ?IStorage $storage = null): ICache {
		}

		public function getScanner(string $path = '', ?IStorage $storage = null): IScanner {
		}

		public function getOwner(string $path): string|false {
		}

		public function getWatcher(string $path = '', ?IStorage $storage = null): IWatcher {
		}

		public function getPropagator(?IStorage $storage = null): IPropagator {
		}

		public function getUpdater(?IStorage $storage = null): IUpdater {
		}

		public function getStorageCache(): \OC\Files\Cache\Storage {
		}

		public function getETag(string $path): string|false {
		}

		public function test(): bool {
		}

		public function isLocal(): bool {
		}

		public function instanceOfStorage(string $class): bool {
		}

		/**
		 * @psalm-template T of IStorage
		 * @psalm-param class-string<T> $class
		 * @psalm-return T|null
		 */
		public function getInstanceOfStorage(string $class): ?IStorage {
		}

		/**
		 * Pass any methods custom to specific storage implementations to the wrapped storage
		 *
		 * @param string $method
		 * @param array $args
		 * @return mixed
		 */
		public function __call(string $method, array $args) {
		}

		public function getDirectDownload(string $path): array|false {
		}

		public function getAvailability(): array {
		}

		public function setAvailability(bool $isAvailable): void {
		}

		public function verifyPath(string $path, string $fileName): void {
		}

		public function copyFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		}

		public function moveFromStorage(IStorage $sourceStorage, string $sourceInternalPath, string $targetInternalPath): bool {
		}

		public function getMetaData(string $path): ?array {
		}

		public function acquireLock(string $path, int $type, ILockingProvider $provider): void {
		}

		public function releaseLock(string $path, int $type, ILockingProvider $provider): void {
		}

		public function changeLock(string $path, int $type, ILockingProvider $provider): void {
		}

		public function needsPartFile(): bool {
		}

		public function writeStream(string $path, $stream, ?int $size = null): int {
		}

		public function getDirectoryContent(string $directory): \Traversable {
		}

		public function isWrapperOf(IStorage $storage): bool {
		}

		public function setOwner(?string $user): void {
		}
	}
}
