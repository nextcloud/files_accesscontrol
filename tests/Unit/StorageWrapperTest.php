<?php

declare(strict_types=1);
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

namespace OCA\FilesAccessControl\Tests\Unit;

use OCA\FilesAccessControl\Operation;
use OCA\FilesAccessControl\StorageWrapper;
use OCP\Files\ForbiddenException;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class StorageWrapperTest extends TestCase {

	/** @var IStorage|MockObject */
	protected $storage;

	/** @var Operation|MockObject */
	protected $operation;

	/** @var IMountPoint|MockObject */
	protected $mountPoint;

	protected function setUp(): void {
		parent::setUp();

		$this->storage = $this->createMock(IStorage::class);

		$this->operation = $this->createMock(Operation::class);

		$this->mountPoint = $this->createMock(IMountPoint::class);
		$this->mountPoint->method('getMountPoint')->willReturn('mountPoint');
		$this->mountPoint->method('getStorage')->willReturn($this->storage);
	}

	protected function getInstance(array $methods = []) {
		return $this->getMockBuilder(StorageWrapper::class)
			->setConstructorArgs([
				[
					'storage' => $this->storage,
					'mountPoint' => 'mountPoint',
					'mount' => $this->mountPoint,
					'operation' => $this->operation,
				]
			])
			->setMethods($methods)
			->getMock();
	}

	public function dataCheckFileAccess(): array {
		return [
			['path1'],
			['path2'],
		];
	}

	/**
	 * @dataProvider dataCheckFileAccess
	 * @param string $path
	 */
	public function testCheckFileAccess(string $path): void {
		$storage = $this->getInstance();

		$this->operation->expects($this->once())
			->method('checkFileAccess')
			->with($path, $this->mountPoint, false);

		self::invokePrivate($storage, 'checkFileAccess', [$path]);
	}

	public function dataSinglePath(): array {
		$methods = ['mkdir', 'rmdir', 'file_get_contents', 'unlink', 'getDirectDownload'];

		$tests = [];
		foreach ($methods as $method) {
			$tests[] = [$method, 'path1', true, null];
			$tests[] = [$method, 'path2', false, null];
			$tests[] = [$method, 'path3', true, new ForbiddenException('Access denied', false)];
			$tests[] = [$method, 'path4', false, new ForbiddenException('Access denied', false)];
		}
		return $tests;
	}

	/**
	 * @dataProvider dataSinglePath
	 * @param string $method
	 * @param string $path
	 * @param bool $return
	 * @param null|\Exception $expected
	 */
	public function testSinglePath(string $method, string $path, bool $return, ?\Exception $expected): void {
		$storage = $this->getInstance(['checkFileAccess']);


		if (is_null($expected)) {
			$storage->expects($this->once())
				->method('checkFileAccess')
				->with($path);

			$this->storage->expects($this->once())
				->method($method)
				->with($path)
				->willReturn($return);

			$this->assertSame($return, self::invokePrivate($storage, $method, [$path]));
		} else {
			$storage->expects($this->once())
				->method('checkFileAccess')
				->with($path)
				->willThrowException($expected);

			$this->storage->expects($this->never())
				->method($method)
				->with($path)
				->willReturn($return);

			try {
				self::invokePrivate($storage, $method, [$path]);
				$this->fail('Should throw an exception before this');
			} catch (\Exception $e) {
				$this->assertSame($expected, $e);
			}
		}
	}

	public function dataSinglePathOverWritten(): array {
		$methods = ['isCreatable', 'isReadable', 'isUpdatable', 'isDeletable'];

		$tests = [];
		foreach ($methods as $method) {
			$tests[] = [$method, 'path1', true, null, true];
			$tests[] = [$method, 'path2', false, null, false];
			$tests[] = [$method, 'path3', true, new ForbiddenException('Access denied', false), false];
			$tests[] = [$method, 'path4', false, new ForbiddenException('Access denied', false), false];
		}
		return $tests;
	}

	/**
	 * @dataProvider dataSinglePathOverWritten
	 * @param string $method
	 * @param string $path
	 * @param bool $return
	 * @param null|\Exception $checkAccess
	 * @param bool $expected
	 */
	public function testSinglePathOverWritten(string $method, string $path, bool $return, ?\Exception $checkAccess, bool $expected): void {
		$storage = $this->getInstance(['checkFileAccess']);


		if ($checkAccess === null) {
			$storage->expects($this->once())
				->method('checkFileAccess')
				->with($path);

			$this->storage->expects($this->once())
				->method($method)
				->with($path)
				->willReturn($return);
		} else {
			$storage->expects($this->once())
				->method('checkFileAccess')
				->with($path)
				->willThrowException($checkAccess);

			$this->storage->expects($this->never())
				->method($method)
				->with($path)
				->willReturn($return);
		}

		$this->assertSame($expected, self::invokePrivate($storage, $method, [$path]));
	}
}
