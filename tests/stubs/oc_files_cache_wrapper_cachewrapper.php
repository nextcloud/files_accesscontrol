<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Cache\Wrapper {

	use OCP\Files\Cache\ICacheEntry;

	class CacheWrapper extends \OC\Files\Cache\Cache {
		/**
		 * @param ICacheEntry $entry
		 * @return ICacheEntry|false
		 */
		protected function formatCacheEntry($entry) {

		}
	}
}
