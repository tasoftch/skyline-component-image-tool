<?php
/*
 * Copyright (c) 2019 TASoft Applications, Th. Abplanalp <info@tasoft.ch>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

use PHPUnit\Framework\TestCase;
use Skyline\ImageTool\Service\ImageToolService;
use Skyline\ImageTool\Service\Scope;
use Skyline\PDO\SQLite;

class ImageToolTest extends TestCase
{
	public function getPDO() {
		static $PDO;
		if(!$PDO) {
			$PDO = new SQLite("Tests/db.sqlite");
			$PDO->exec("DELETE FROM SKY_IT_SCOPE");
		}
		return $PDO;
	}

	public function testImageTool() {
		$pdo = $this->getPDO();

		$it = new ImageToolService($pdo, "Tests/Root", "/Public/img");
		$this->assertEquals(getcwd() . "/Tests/Root", $it->getImageRoot());
	}

	public function testMakeScope() {
		$pdo = $this->getPDO();

		$it = new ImageToolService($pdo, "Tests/Root", "/Public/img");

		$this->assertInstanceOf(Scope::class, $s = $it->makeScope("tester", "Test Scope", "Example", 'dd'));
		$this->assertEquals('tester', $s->getSlug());
		$this->assertEquals('Test Scope', $s->getName());
		$this->assertEquals('Example', $s->getDescription());
		$this->assertEquals('dd', $s->getPreviewAppendix());
	}

	/**
	 * @depends testMakeScope
	 */
	public function testGetScope() {
		$pdo = $this->getPDO();

		$it = new ImageToolService($pdo, "Tests/Root", "/Public/img");

		$s = $it->getScope("tester");

		$this->assertEquals('tester', $s->getSlug());
		$this->assertEquals('Test Scope', $s->getName());
		$this->assertEquals('Example', $s->getDescription());
		$this->assertEquals('dd', $s->getPreviewAppendix());

		$this->assertNull($it->getScope("hi there"));

		$this->assertCount(1, $it->getAllScopes());
	}
}
