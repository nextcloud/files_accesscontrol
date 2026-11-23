<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Cache {
	use OCP\Files\Cache\ICache;
	use OCP\Files\Cache\ICacheEntry;
	use OCP\Files\IMimeTypeLoader;
	use OCP\Files\Search\ISearchOperator;
	use OCP\Files\Search\ISearchQuery;

	class Cache implements ICache {
		public function __construct(\OCP\Files\Storage\IStorage $storage, ?CacheDependencies $dependencies = null) {
		}
		public function getNumericStorageId() {
		}
		public function getIncomplete() {
		}
		public function getPathById($id) {
		}
		public function getAll() {
		}
		public function get($file) {
		}
		public function getFolderContents(string $folder, ?string $mimeTypeFilter = null) {
		}
		public function getFolderContentsById(int $fileId, ?string $mimeTypeFilter = null) {
		}
		public function put($file, array $data) {
		}
		public function insert($file, array $data) {
		}
		public function update($id, array $data) {
		}
		public function getId($file) {
		}
		public function getParentId($file) {
		}
		public function inCache($file) {
		}
		public function remove($file) {
		}
		public function move($source, $target) {
		}
		public function moveFromCache(ICache $sourceCache, $sourcePath, $targetPath) {
		}
		public function clear() {
		}
		public function getStatus($file) {
		}
		public function search($pattern) {
		}
		public function searchByMime($mimetype) {
		}
		public function searchQuery(ISearchQuery $query) {
		}
		public function correctFolderSize(string $path, $data = null, bool $isBackgroundScan = false): void {
		}
		public function copyFromCache(ICache $sourceCache, ICacheEntry $sourceEntry, string $targetPath): int {
		}
		public function normalize($path) {
		}
		public function getQueryFilterForStorage(): ISearchOperator {
		}
		public function getCacheEntryFromSearchResult(ICacheEntry $rawEntry): ?ICacheEntry {
		}
		public static function cacheEntryFromData(array $data, IMimeTypeLoader $mimetypeLoader): CacheEntry {
		}
	}
}
