<?php
/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
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


use OCP\Files\ForbiddenException;
use OCP\WorkflowEngine\IManager;

class Operation {

	/**
	 * AccessControl constructor.
	 *
	 * @param IManager $manager
	 */
	public function __construct(IManager $manager) {
		$this->manager = $manager;
	}

	/**
	 * @param StorageWrapper $storage
	 * @param string $path
	 * @throws ForbiddenException
	 */
	public function checkFileAccess(StorageWrapper $storage, $path) {
		if ($this->isCreatingSkeletonFiles()) {
			// Allow creating skeletons, otherwise the first login fails, see
			// https://github.com/nextcloud/files_accesscontrol/issues/5
			return;
		}

		$this->manager->setFileInfo($storage, $path);
		$match = $this->manager->getMatchingOperations('OCA\FilesAccessControl\Operation');

		if (!empty($match)) {
			// All Checks of one operation matched: prevent access
			throw new ForbiddenException('Access denied', true);
		}
	}

	/**
	 * Check if we are in the LoginController and if so, ignore the firewall
	 * @return bool
	 */
	protected function isCreatingSkeletonFiles() {
		$exception = new \Exception();
		$trace = $exception->getTrace();

		foreach ($trace as $step) {
			if ($step['class'] === 'OC\Core\Controller\LoginController' &&
				$step['function'] === 'tryLogin') {
				return true;
			}
		}

		return false;
	}
}
