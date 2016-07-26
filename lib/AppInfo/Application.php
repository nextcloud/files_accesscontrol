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

use OC\Files\Filesystem;
use OCA\FilesAccessControl\StorageWrapper;
use OCP\Files\Storage\IStorage;
use OCP\Util;
use Symfony\Component\EventDispatcher\GenericEvent;

class Application extends \OCP\AppFramework\App {

	public function __construct() {
		parent::__construct('files_accesscontrol');
	}

	/**
	 * Register all hooks and listeners
	 */
	public function registerHooksAndListeners() {
		Util::connectHook('OC_Filesystem', 'preSetup', $this, 'addStorageWrapper');

		\OCP\App::registerAdmin('files_accesscontrol', 'settings/admin');

		$container = $this->getContainer();
		$container->getServer()->getEventDispatcher()->addListener(
			'OCA\FilesAccessControl\StorageWrapper\Event',
			function(GenericEvent $event) use ($container) {
				/** @var \OCA\FilesAccessControl\AccessControl $accessControl */
				$accessControl = $container->query('OCA\FilesAccessControl\AccessControl');
				$accessControl->listen($event);
			},
			-10
		);
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
		if (!\OC::$CLI && !$storage->instanceOfStorage('OC\Files\Storage\Shared')) {
			$dispatcher = $this->getContainer()->getServer()->getEventDispatcher();
			return new StorageWrapper([
				'storage' => $storage,
				'mountPoint' => $mountPoint,
				'dispatcher' => $dispatcher,
			]);
		}

		return $storage;
	}
}
