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

namespace Skyline\ImageTool\Service;

use Skyline\ImageTool\Source\Local\LocalImageSetInterface;
use Skyline\ImageTool\Source\Remote\ImageRefCollection;
use Skyline\ImageTool\Source\Remote\ImageRefInterface;
use Skyline\ImageTool\Source\Remote\ImageTargetRefInterface;

interface ImageToolServiceInterface
{
	/** @var int Searches for any image in the database that is not referenced anymore. */
	const FIND_UNREFERENCED_IMAGES = 1<<0;
	/** @var int Searches for any local file on disk that is not linked by an image entry in the database. */
	const FIND_UNLINKED_FILES = 1<<1;
	/** @var int Returns only target objects (only the id) */
	const FIND_TARGET_IMAGES_ONLY = 1<<2;

	/**
	 * Creates a new scope to add references to it.
	 *
	 * Scopes are like subdirectories from the root and represent a part of images in your application.
	 *
	 * @param string $slug
	 * @param string|null $name
	 * @param string|null $description
	 * @param string $preview_appendix
	 * @return Scope|null
	 */
	public function makeScope(string $slug, string $name = NULL, string $description = NULL, string $preview_appendix = 'd'): ?Scope;

	/**
	 * @param int|string|Scope $scope
	 * @return Scope|null
	 */
	public function getScope($scope): ?Scope;

	/**
	 * Drops a scope from data base. All references in that scope become invalid, so by default they are dropped as well.
	 *
	 * Holded image sources are not unlinked by default. There is another mechanism to hold your server clean of unlinked files.
	 *
	 * @param int|string|Scope $scope
	 * @param bool $dropReferences
	 * @param bool $dropFiles
	 * @return mixed
	 */
	public function dropScope($scope, bool $dropReferences = true, bool $dropFiles = false);

	/**
	 * Tries to create a new reference.
	 *
	 * @param string $slug
	 * @param int|string|Scope $scope
	 * @return Reference|null
	 */
	public function makeReference(string $slug, $scope): ?Reference;

	/**
	 * Gets a reference
	 *
	 * @param int|string|Reference $ref
	 * @return Reference|null
	 */
	public function getReference($ref): ?Reference;

	/**
	 * @param int|string|Reference $ref
	 * @param bool $dropFiles
	 * @return mixed
	 */
	public function dropReference($ref, bool $dropFiles = false);

	/**
	 * Fetches the main image of a referencce if available
	 *
	 * @param int|string|Reference $reference
	 * @return ImageRefInterface|null
	 */
	public function getMainImageOfReference($reference): ?ImageRefInterface;

	/**
	 * Fetches all images assigned to a reference
	 *
	 * @param int|string|Reference $reference
	 * @return ImageRefCollection|null
	 */
	public function getImagesOfReference($reference): ?ImageRefCollection;

	/**
	 * @param iterable|int[]|string[]|Reference[] $references
	 * @return array|ImageRefCollection[]
	 */
	public function getImagesOfReferences(iterable $references): array;


	/**
	 * @param LocalImageSetInterface $image
	 * @param Reference $reference
	 * @return bool
	 */
	public function setMainImageOfReference(LocalImageSetInterface $image, Reference $reference): bool;

	/**
	 * @param LocalImageSetInterface $image
	 * @param Reference $reference
	 * @return bool
	 */
	public function addImageToReference(LocalImageSetInterface $image, Reference $reference): bool;

	/**
	 * @param ImageTargetRefInterface|int $image
	 * @param bool $dropFile
	 * @return bool
	 */
	public function dropImage($image, bool $dropFile = false): bool;

	/**
	 * @param int $options
	 * @param null $scope
	 * @return array|null
	 */
	public function findImages(int $options, $scope = NULL): ?array;
}