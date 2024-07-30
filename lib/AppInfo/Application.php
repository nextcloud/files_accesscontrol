<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAccessControl\AppInfo;

use OC;
use OC\Files\Filesystem;
use OCA\FilesAccessControl\Listener\FlowRegisterOperationListener;
use OCA\FilesAccessControl\Operation;
use OCA\FilesAccessControl\StorageWrapper;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Storage\IStorage;
use OCP\Util;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;

class Application extends App implements IBootstrap {
	public function __construct() {
		parent::__construct('files_accesscontrol');
	}

	/**
	 * @internal
	 */
	public function addStorageWrapper(): void {
		// Needs to be added as the first layer
		Filesystem::addStorageWrapper('files_accesscontrol', [$this, 'addStorageWrapperCallback'], -10);
	}

	/**
	 * @internal
	 * @param $mountPoint
	 * @param IStorage $storage
	 * @return StorageWrapper|IStorage
	 */
	public function addStorageWrapperCallback($mountPoint, IStorage $storage) {
		if (!OC::$CLI && $mountPoint !== '/') {
			/** @var Operation $operation */
			$operation = $this->getContainer()->get(Operation::class);
			return new StorageWrapper([
				'storage' => $storage,
				'mountPoint' => $mountPoint,
				'operation' => $operation,
			]);
		}

		return $storage;
	}

	public function register(IRegistrationContext $context): void {
		Util::connectHook('OC_Filesystem', 'preSetup', $this, 'addStorageWrapper');
		$context->registerEventListener(RegisterOperationsEvent::class, FlowRegisterOperationListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
