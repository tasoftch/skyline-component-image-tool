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

namespace Skyline\ImageTool\Source\Local;


use Skyline\ImageTool\Exception\UnsupportedImageTypeException;

class LocalImageRef implements LocalImageRefInterface
{
	const IMAGE_JPEG = 1;
	const IMAGE_PNG = 2;
	const IMAGE_GIF = 3;
	const IMAGE_BMP = 4;


	/** @var string */
	private $filename;
	/** @var int */
	private $type;
	/** @var int */
	protected $width;
	/** @var int */
	protected $height;

	/**
	 * LocalImage constructor.
	 * @param string $filename
	 */
	public function __construct(string $filename)
	{
		$this->filename = $filename;
		$info = getimagesize($filename);
		$this->width = $info[0];
		$this->height = $info[1];

		switch ($info["mime"]) {
			case "image/jpeg":
			case "image/jpg":
				$this->type = self::IMAGE_JPEG;
				break;
			case "image/gif":
				$this->type = self::IMAGE_GIF;
				break;
			case "image/png":
				$this->type = self::IMAGE_PNG;
				break;
			case "image/bmp":
			case "image/tiff":
				$this->type = self::IMAGE_BMP;
				break;
			default:
				throw (new UnsupportedImageTypeException("Unsupported image type {$info["name"]}", 401))->setType($info["name"]);
		}
	}

	/**
	 * @param $destination
	 * @return bool
	 */
	public function copyTo($destination): bool
	{
		return copy($this->getFilename(), $destination);
	}

	/**
	 * @return string
	 */
	public function getFilename(): string
	{
		return $this->filename;
	}

	/**
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
	}

	/**
	 * @return int
	 */
	public function getWidth(): int
	{
		return $this->width;
	}

	/**
	 * @return int
	 */
	public function getHeight(): int
	{
		return $this->height;
	}
}