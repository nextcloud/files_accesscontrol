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
use Psr\Container\ContainerInterface;

class FlowRegisterOperationListener implements IEventListener {
	private ContainerInterface $c;

	public function __construct(ContainerInterface $c) {
		$this->c = $c;
	}

	public function handle(Event $event): void {
		if (!$event instanceof RegisterOperationsEvent) {
			return;
		}
		$event->registerOperation($this->c->get(Operation::class));
		Util::addScript('files_accesscontrol', 'admin');
	}
}
