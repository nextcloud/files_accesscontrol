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
use OCP\IL10N;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IOperation;

class Operation implements IOperation{
	/** @var IManager */
	protected $manager;

	/** @var IL10N */
	protected $l;

	/**
	 * @param IManager $manager
	 * @param IL10N $l
	 */
	public function __construct(IManager $manager, IL10N $l) {
		$this->manager = $manager;
		$this->l = $l;
	}

	/**
	 * @param StorageWrapper $storage
	 * @param string $path
	 * @throws ForbiddenException
	 */
	public function checkFileAccess(StorageWrapper $storage, $path) {
		if (!$this->isUserFileOrThumbnail($storage, $path) || $this->isCreatingSkeletonFiles()) {
			// Allow creating skeletons and theming
			// https://github.com/nextcloud/files_accesscontrol/issues/5
			// https://github.com/nextcloud/files_accesscontrol/issues/12
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
	 * @param StorageWrapper $storage
	 * @param string $path
	 * @return bool
	 */
	protected function isUserFileOrThumbnail(StorageWrapper $storage, $path) {
		$fullPath = $storage->mountPoint . $path;

		if (substr_count($fullPath, '/') < 3) {
			return false;
		}

		// '', admin, 'files', 'path/to/file.txt'
		$segment = explode('/', $fullPath, 4);

		return isset($segment[2]) && in_array($segment[2], ['files', 'thumbnails']);
	}

	/**
	 * Check if we are in the LoginController and if so, ignore the firewall
	 * @return bool
	 */
	protected function isCreatingSkeletonFiles() {
		$exception = new \Exception();
		$trace = $exception->getTrace();

		foreach ($trace as $step) {
			if (isset($step['class']) && $step['class'] === 'OC\Core\Controller\LoginController' &&
				isset($step['function']) && $step['function'] === 'tryLogin') {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $name
	 * @param array[] $checks
	 * @param string $operation
	 * @throws \UnexpectedValueException
	 */
	public function validateOperation($name, array $checks, $operation) {
		if (empty($checks)) {
			throw new \UnexpectedValueException($this->l->t('No rule given'));
		}
	}
}
