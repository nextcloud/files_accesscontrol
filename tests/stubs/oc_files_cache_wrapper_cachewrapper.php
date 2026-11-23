<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Cache\Wrapper {

	use OCP\Files\Cache\ICacheEntry;

	class CacheWrapper extends \OC\Files\Cache\Cache {
		public function __construct(?\OCP\Files\Cache\ICache $cache, ?\OC\Files\Cache\CacheDependencies $dependencies = null) {
		}

		/**
		 * @param ICacheEntry $entry
		 * @return ICacheEntry|false
		 */
		protected function formatCacheEntry($entry) {

		}
	}
}
