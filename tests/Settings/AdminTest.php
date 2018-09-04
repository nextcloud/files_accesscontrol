<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>g
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCA\FilesAccessControl\Settings\Test;

use OCA\FilesAccessControl\AppInfo\Application;
use OCA\FilesAccessControl\Settings\Admin;
use OCP\AppFramework\IAppContainer;
use OCP\IL10N;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Test\TestCase;
use OCP\AppFramework\Http\TemplateResponse;

class AdminTest extends TestCase {
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	protected $l;

	/** @var Application|\PHPUnit_Framework_MockObject_MockObject */
	protected $app;

	/** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
	protected $dispatcher;

	/** @var Admin */
	protected $admin;

	/** @var IAppContainer|\PHPUnit_Framework_MockObject_MockObject */
	protected $container;

	protected function setUp() {
		parent::setUp();

		$this->l = $this->createMock(IL10N::class);
		$this->app = $this->createMock(Application::class);
		$this->dispatcher = $this->createMock(EventDispatcherInterface::class);
		$this->container = $this->createMock(IAppContainer::class);

		$this->admin = new Admin($this->l, $this->app, $this->dispatcher);
	}

	public function testGetForm() {
		$this->dispatcher->expects($this->once())
			->method('dispatch')
			->with('OCP\WorkflowEngine::loadAdditionalSettingScripts');

		$this->l->expects($this->exactly(3))
			->method('t')
			->will($this->returnCallback(function($text, $parameters = []) {
				return vsprintf($text, $parameters);
			}));

		$this->container->expects($this->once())
			->method('getAppName')
			->willReturn('files_accesscontrol');

		$this->app->expects($this->once())
			->method('getContainer')
			->willReturn($this->container);

		$result = $this->admin->getForm();
		$this->assertInstanceOf(TemplateResponse::class, $result);
	}
}
