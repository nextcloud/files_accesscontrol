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

class AdminTest extends TestCase {
	/** @var  IL10N */
	protected $l;

	/** @var  Application */
	protected $app;

	/** @var  EventDispatcherInterface */
	protected $dispatcher;

	/** @var  Admin */
	protected $admin;

	/** @var  IAppContainer */
	protected $container;

	protected function setUp() {
		parent::setUp();

		$this->l = $this->getMockBuilder('OCP\IL10N')
			->getMock();

		$this->app = $this->getMockBuilder('OCA\FilesAccessControl\AppInfo\Application')
			->disableOriginalConstructor()
			->getMock();

		$this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
			->getMock();

		$this->container = $this->getMockBuilder('OCP\AppFramework\IAppContainer')->getMock();

		$this->admin = new Admin($this->l, $this->app, $this->dispatcher);
	}

	public function testGetForm() {
		$this->dispatcher->expects($this->once())
		   ->method('dispatch')
		   ->with('OCP\WorkflowEngine::loadAdditionalSettingScripts');

		$this->l->expects($this->exactly(2))
		   ->method('t')
		   ->willReturnArgument(1);

		$this->container->expects($this->once())
			->method('getAppName')
			->willReturn('files_accesscontrol');

		$this->app->expects($this->once())
			->method('getContainer')
			->willReturn($this->container);

		$result = $this->admin->getForm();
		$this->assertInstanceOf('OCP\AppFramework\Http\TemplateResponse', $result);
	}
}
