<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAccessControl\Listener;

use OCA\FilesAccessControl\Operation;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;

/**
 * @template-implements IEventListener<RegisterOperationsEvent>
 */
class FlowRegisterOperationListener implements IEventListener {
	public function __construct(
		protected readonly Operation $operation) {
	}

	public function handle(Event $event): void {
		if (!$event instanceof RegisterOperationsEvent) {
			return;
		}
		$event->registerOperation($this->operation);
		Util::addScript('files_accesscontrol', 'admin');
	}
}
