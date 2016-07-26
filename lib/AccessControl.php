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
use Symfony\Component\EventDispatcher\GenericEvent;

class AccessControl {

	/**
	 * AccessControl constructor.
	 *
	 * @param IManager $manager
	 */
	public function __construct(IManager $manager) {
		$this->manager = $manager;
	}

	/**
	 * @param GenericEvent $event
	 */
	public function listen(GenericEvent $event) {
		if ($event->getArgument('slot') !== StorageWrapper::SLOT_BEFORE) {
			return;
		}

		/** @var StorageWrapper $storage */
		$storage = $event->getSubject();

		if ($event->hasArgument('path')) {
			$this->checkFileAccess($storage, $event->getArgument('path'));
		}
		if ($event->hasArgument('path1')) {
			$this->checkFileAccess($storage, $event->getArgument('path1'));
		}
		if ($event->hasArgument('path2')) {
			$this->checkFileAccess($storage, $event->getArgument('path2'));
		}
	}

	/**
	 * @param StorageWrapper $storage
	 * @param string $path
	 * @throws ForbiddenException
	 */
	protected function checkFileAccess(StorageWrapper $storage, $path) {
		$this->manager->setFileInfo($storage, $path);
		$match = $this->manager->getMatchingOperations('OCA\FilesAccessControl\Operation');

		if (!empty($match)) {
			// All Checks of one operation matched: prevent access
			throw new ForbiddenException('Access denied', true);
		}
	}
}
