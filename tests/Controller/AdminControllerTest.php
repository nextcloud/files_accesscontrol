<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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

namespace OCA\FilesAccessControl\Controller\Test;

use OCA\FilesAccessControl\Controller\AdminController;
use OCP\AppFramework\Http\TemplateResponse;
use Test\TestCase;

class AdminControllerTest extends TestCase {

	/** @var AdminController|\PHPUnit_Framework_MockObject_MockObject */
	protected $controller;
	/** @var \OCP\IRequest|\PHPUnit_Framework_MockObject_MockObject */
	protected $request;
	/** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
	protected $dispatcher;
	/** @var \OCP\IL10N|\PHPUnit_Framework_MockObject_MockObject */
	protected $l;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->getMockBuilder('OCP\IRequest')
			->getMock();
		$this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
			->getMock();
		$this->l = $this->getMockBuilder('OCP\IL10N')
			->getMock();
		$this->controller = new AdminController(
			'files_automatedtagging',
			$this->request,
			$this->dispatcher,
			$this->l
		);
	}

	/**
	 * @return TemplateResponse
	 */
	public function testIndex() {
		$this->dispatcher->expects($this->once())
			->method('dispatch')
			->with('OCP\WorkflowEngine::loadAdditionalSettingScripts');

		$this->l->expects($this->once())
			->method('t')
			->willReturnArgument(1);

		$response = $this->controller->index();

		$this->assertInstanceOf('OCP\AppFramework\Http\TemplateResponse', $response);
	}
}
