<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAccessControl;

use Exception;
use OC\Files\FileInfo;
use OC\Files\Node\Folder;
use OC\Files\View;
use OCA\WorkflowEngine\Entity\File;
use OCP\EventDispatcher\Event;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\ForbiddenException;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\WorkflowEngine\IComplexOperation;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IRuleMatcher;
use OCP\WorkflowEngine\ISpecificOperation;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use UnexpectedValueException;

class Operation implements IComplexOperation, ISpecificOperation {
	protected int $nestingLevel = 0;

	public function __construct(
		protected readonly IManager $manager,
		protected readonly IL10N $l,
		protected readonly IURLGenerator $urlGenerator,
		protected readonly File $fileEntity,
		protected readonly IMountManager $mountManager,
		protected readonly IRootFolder $rootFolder,
		protected readonly LoggerInterface $logger
	) {
	}

	/**
	 * @param array|ICacheEntry|null $cacheEntry
	 * @throws ForbiddenException
	 */
	public function checkFileAccess(IStorage $storage, string $path, bool $isDir = false, $cacheEntry = null): void {
		if (!$this->isBlockablePath($storage, $path) || $this->isCreatingSkeletonFiles() || $this->nestingLevel !== 0) {
			// Allow creating skeletons and theming
			// https://github.com/nextcloud/files_accesscontrol/issues/5
			// https://github.com/nextcloud/files_accesscontrol/issues/12
			return;
		}

		$this->nestingLevel++;

		$filePath = $this->translatePath($storage, $path);
		$ruleMatcher = $this->manager->getRuleMatcher();
		$ruleMatcher->setFileInfo($storage, $filePath, $isDir);
		$node = $this->getNode($storage, $path, $cacheEntry);
		if ($node !== null) {
			$ruleMatcher->setEntitySubject($this->fileEntity, $node);
		}
		$ruleMatcher->setOperation($this);
		$match = $ruleMatcher->getFlows();

		$this->nestingLevel--;

		if (!empty($match)) {
			$e = new \RuntimeException('Access denied for path ' . $path . ' that is ' . ($isDir ? '' : 'not ') . 'a directory and matches rules: ' . json_encode($match));
			$this->logger->debug($e->getMessage(), ['exception' => $e]);
			// All Checks of one operation matched: prevent access
			throw new ForbiddenException('Access denied by access control', false);
		}
	}

	protected function isBlockablePath(IStorage $storage, string $path): bool {
		if (property_exists($storage, 'mountPoint')) {
			$hasMountPoint = $storage instanceof StorageWrapper;
			if (!$hasMountPoint) {
				$ref = new ReflectionClass($storage);
				$prop = $ref->getProperty('mountPoint');
				$hasMountPoint = $prop->isPublic();
			}

			if ($hasMountPoint) {
				/** @var StorageWrapper $storage */
				$fullPath = $storage->mountPoint . ltrim($path, '/');
			} else {
				$fullPath = $path;
			}
		} else {
			$fullPath = $path;
		}

		if (substr_count($fullPath, '/') < 3) {
			return false;
		}

		if (preg_match('/\.ocTransferId\d+\.part$/i', $path)) {
			return false;
		}

		// '', admin, 'files', 'path/to/file.txt'
		$segment = explode('/', $fullPath, 4);

		if (isset($segment[2]) && $segment[1] === '__groupfolders' && $segment[2] === 'trash') {
			// Special case, a file was deleted inside a groupfolder
			return true;
		}

		return isset($segment[2]) && in_array($segment[2], [
			'files',
			'thumbnails',
			'files_versions',
		]);
	}

	/**
	 * For thumbnails and versions we want to check the tags of the original file
	 */
	protected function translatePath(IStorage $storage, string $path): string {
		if (substr_count($path, '/') < 1) {
			return $path;
		}

		// 'files', 'path/to/file.txt'
		[$folder, $innerPath] = explode('/', $path, 2);

		if ($folder === 'files_versions') {
			if (preg_match('/.+\.v\d{10}$/', basename($innerPath))) {
				// Remove trailing ".v{timestamp}"
				$innerPath = substr($innerPath, 0, -12);
			}
			return 'files/' . $innerPath;
		} elseif ($folder === 'thumbnails') {
			[$fileId,] = explode('/', $innerPath, 2);
			$innerPath = $storage->getCache()->getPathById((int) $fileId);

			if ($innerPath !== null) {
				return 'files/' . $innerPath;
			}
		}

		return $path;
	}

	/**
	 * Check if we are in the LoginController and if so, ignore the firewall
	 */
	protected function isCreatingSkeletonFiles(): bool {
		$exception = new Exception();
		$trace = $exception->getTrace();

		foreach ($trace as $step) {
			if (isset($step['class']) && $step['class'] === \OC\Core\Controller\LoginController::class &&
				isset($step['function']) && $step['function'] === 'tryLogin') {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $name
	 * @param array $checks
	 * @param string $operation
	 * @throws UnexpectedValueException
	 */
	public function validateOperation(string $name, array $checks, string $operation): void {
		if (empty($checks)) {
			throw new UnexpectedValueException($this->l->t('No rule given'));
		}
	}

	/**
	 * returns a translated name to be presented in the web interface
	 *
	 * Example: "Automated tagging" (en), "Aŭtomata etikedado" (eo)
	 *
	 * @since 18.0.0
	 */
	public function getDisplayName(): string {
		return $this->l->t('Block access to a file');
	}

	/**
	 * returns a translated, descriptive text to be presented in the web interface.
	 *
	 * It should be short and precise.
	 *
	 * Example: "Tag based automatic deletion of files after a given time." (en)
	 *
	 * @since 18.0.0
	 */
	public function getDescription(): string {
		return '';
	}

	/**
	 * returns the URL to the icon of the operator for display in the web interface.
	 *
	 * Usually, the implementation would utilize the `imagePath()` method of the
	 * `\OCP\IURLGenerator` instance and simply return its result.
	 *
	 * Example implementation: return $this->urlGenerator->imagePath('myApp', 'cat.svg');
	 *
	 * @since 18.0.0
	 */
	public function getIcon(): string {
		return $this->urlGenerator->imagePath('files_accesscontrol', 'app.svg');
	}

	/**
	 * returns whether the operation can be used in the requested scope.
	 *
	 * Scope IDs are defined as constants in OCP\WorkflowEngine\IManager. At
	 * time of writing these are SCOPE_ADMIN and SCOPE_USER.
	 *
	 * For possibly unknown future scopes the recommended behaviour is: if
	 * user scope is permitted, the default behaviour should return `true`,
	 * otherwise `false`.
	 *
	 * @since 18.0.0
	 */
	public function isAvailableForScope(int $scope): bool {
		return $scope === IManager::SCOPE_ADMIN;
	}

	/**
	 * returns the id of the entity the operator is designed for
	 *
	 * Example: 'WorkflowEngine_Entity_File'
	 *
	 * @since 18.0.0
	 */
	public function getEntityId(): string {
		return File::class;
	}

	/**
	 * As IComplexOperation chooses the triggering events itself, a hint has
	 * to be shown to the user so make clear when this operation is becoming
	 * active. This method returns such a translated string.
	 *
	 * Example: "When a file is accessed" (en)
	 *
	 * @since 18.0.0
	 */
	public function getTriggerHint(): string {
		return $this->l->t('File is accessed');
	}

	public function onEvent(string $eventName, Event $event, IRuleMatcher $ruleMatcher): void {
		// Noop
	}

	/**
	 * @param array|ICacheEntry|null $cacheEntry
	 */
	private function getNode(IStorage $storage, string $path, $cacheEntry = null): ?Node {
		/** @var IMountPoint|false $mountPoint */
		$mountPoint = current($this->mountManager->findByStorageId($storage->getId()));
		if (!$mountPoint) {
			return null;
		}

		$fullPath = $mountPoint->getMountPoint() . $path;
		if ($cacheEntry) {
			// todo: LazyNode?
			$info = new FileInfo($fullPath, $mountPoint->getStorage(), $path, $cacheEntry, $mountPoint);
			$isDir = $info->getType() === \OCP\Files\FileInfo::TYPE_FOLDER;
			$view = new View('');
			if ($isDir) {
				return new Folder($this->rootFolder, $view, $path, $info);
			} else {
				return new \OC\Files\Node\File($this->rootFolder, $view, $path, $info);
			}
		} else {
			try {
				return $this->rootFolder->get($fullPath);
			} catch (NotFoundException $e) {
				return null;
			}
		}
	}
}
