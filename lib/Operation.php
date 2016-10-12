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
use OCP\Files\Storage\IStorage;
use OCP\IL10N;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IOperation;

class Operation implements IOperation{
	/** @var IManager */
	protected $manager;

	/** @var IL10N */
	protected $l;

	/** @var int */
	protected $nestingLevel = 0;

	/**
	 * @param IManager $manager
	 * @param IL10N $l
	 */
	public function __construct(IManager $manager, IL10N $l) {
		$this->manager = $manager;
		$this->l = $l;
	}

	/**
	 * @param IStorage $storage
	 * @param string $path
	 * @throws ForbiddenException
	 */
	public function checkFileAccess(IStorage $storage, $path) {
		if (!$this->isBlockablePath($storage, $path) || $this->isCreatingSkeletonFiles() || $this->nestingLevel !== 0) {
			// Allow creating skeletons and theming
			// https://github.com/nextcloud/files_accesscontrol/issues/5
			// https://github.com/nextcloud/files_accesscontrol/issues/12
			return;
		}

		$this->nestingLevel++;

		$filePath = $this->translatePath($storage, $path);
		$this->manager->setFileInfo($storage, $filePath);
		$match = $this->manager->getMatchingOperations('OCA\FilesAccessControl\Operation');

		$this->nestingLevel--;

		if (!empty($match)) {
			// All Checks of one operation matched: prevent access
			throw new ForbiddenException('Access denied', true);
		}
	}

	/**
	 * @param IStorage $storage
	 * @param string $path
	 * @return bool
	 */
	protected function isBlockablePath(IStorage $storage, $path) {
		if (property_exists($storage, 'mountPoint')) {
			/** @var StorageWrapper $storage */
			$fullPath = $storage->mountPoint . $path;
		} else {
			$fullPath = $path;
		}

		if (substr_count($fullPath, '/') < 3) {
			return false;
		}

		// '', admin, 'files', 'path/to/file.txt'
		$segment = explode('/', $fullPath, 4);

		return isset($segment[2]) && in_array($segment[2], [
			'files',
			'thumbnails',
			'files_versions',
		]);
	}

	/**
	 * For thumbnails and versions we want to check the tags of the original file
	 *
	 * @param IStorage $storage
	 * @param string $path
	 * @return bool
	 */
	protected function translatePath(IStorage $storage, $path) {
		if (substr_count($path, '/') < 1) {
			return $path;
		}

		// 'files', 'path/to/file.txt'
		list($folder, $innerPath) = explode('/', $path, 2);

		if ($folder === 'files_versions') {
			$innerPath = substr($innerPath, 0, strrpos($innerPath, '.v'));
			return 'files/' . $innerPath;
		} else if ($folder === 'thumbnails') {
			list($fileId,) = explode('/', $innerPath, 2);
			$innerPath = $storage->getCache()->getPathById($fileId);

			if ($innerPath !== null) {
				return 'files/' . $innerPath;
			}
		}

		return $path;
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
