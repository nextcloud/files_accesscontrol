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

namespace OCA\FilesAccessControl\AppInfo;

use OC;
use OC\Files\Filesystem;
use OCA\Files_Sharing\SharedStorage;
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
	public function addStorageWrapper() {
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
		if (!OC::$CLI && !$storage->instanceOfStorage(SharedStorage::class)) {
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
