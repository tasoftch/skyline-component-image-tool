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

use Skyline\ImageTool\Exception\DeleteImageException;
use Skyline\ImageTool\Exception\ImageRootNotFoundException;
use Skyline\ImageTool\Source\Local\LocalImageSetInterface;
use Skyline\ImageTool\Source\Remote\ImageRef;
use Skyline\ImageTool\Source\Remote\ImageRefCollection;
use Skyline\ImageTool\Source\Remote\ImageRefInterface;
use Skyline\ImageTool\Source\Remote\ImageTargetRefInterface;
use TASoft\Service\AbstractService;
use TASoft\Util\PDO;
use TASoft\Util\ValueInjector;

class ImageToolService extends AbstractService implements ImageToolServiceInterface {
	const SERVICE_NAME = 'imageTool';

	/** @var PDO */
	private $PDO;
	private $imageRoot;
	private $uriRoot;

	private $scopes = [];
	private $references = [];

	/**
	 * ImageToolService constructor.
	 * @param PDO $PDO
	 * @param string $imageRoot
	 * @param string $uriRoot
	 */
	public function __construct(PDO $PDO, string $imageRoot, string $uriRoot)
	{
		$this->PDO = $PDO;
		if(!is_dir($imageRoot))
			throw (new ImageRootNotFoundException("Could not find image root", 404))->setImageRoot($imageRoot);
		$this->imageRoot = $imageRoot;
		$this->uriRoot = $uriRoot;
	}

	/**
	 * @return PDO
	 */
	public function getPDO(): PDO
	{
		return $this->PDO;
	}

	/**
	 * @inheritDoc
	 */
	public function getImageRoot(): string
	{
		return realpath($this->imageRoot);
	}

	/**
	 * @return string
	 */
	public function getUriRoot(): string
	{
		return $this->uriRoot;
	}

	/**
	 * Gets the local path of a scope
	 *
	 * @param $scope
	 * @param bool $preview
	 * @param bool $createIfNeeded
	 * @return false|string
	 */
	public function getLocalPath($scope, bool $preview = false, bool $createIfNeeded = true) {
		if($scope = $this->getScope($scope)) {
			$path = sprintf("%s%s%s", $this->getImageRoot(), DIRECTORY_SEPARATOR, $scope->getSlug());
			if($preview)
				$path .= DIRECTORY_SEPARATOR . $scope->getPreviewAppendix();

			if(!is_dir($path) && $createIfNeeded)
				mkdir($path, 0777, true);

			return is_dir($path) ? $path : false;
		}
		return false;
	}

	/**
	 * Gets the URI located as root to access any image inside the scope
	 *
	 * @param $scope
	 * @param bool $preview
	 * @return false|string
	 */
	public function getURI($scope, bool $preview = false) {
		if($scope = $this->getScope($scope)) {
			$uri = sprintf("%s/%s/", $this->getUriRoot(), $scope->getSlug());
			if($preview)
				$uri .= $scope->getPreviewAppendix() . "/";
			return preg_replace("%//+%", "/", $uri);
		}
		return false;
	}

	private function _assignScope(Scope $s, $id, $slug = "", $name = NULL, $desc = NULL, $appendix = 'd') {
		$vi = new ValueInjector($s);
		if(is_array($id)) {
			$vi->id = $id['id'];
			$vi->slug = $id['slug'];
			$vi->name = $id['name'];
			$vi->description = $id['description'];
			$vi->preview_appendix = $id['preview_appendix'];
		} else {
			$vi->id = $id;
			$vi->slug = $slug;
			$vi->name = $name;
			$vi->description = $desc;
			$vi->preview_appendix = $appendix;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function makeScope(string $slug, string $name = NULL, string $description = NULL, string $preview_appendix = 'd'): ?Scope {
		try {
			$this->getPDO()->inject("INSERT INTO SKY_IT_SCOPE (slug, name, description, preview_appendix) VALUES (?,?,?,?)")->send([
				$slug,
				$name,
				$description,
				$preview_appendix
			]);
			$this->_assignScope($s = new Scope(), $this->getPDO()->lastInsertId("SKY_IT_SCOPE"), $slug, $name, $description, $preview_appendix);
			$this->getLocalPath($s, true, true);
			return $s;
		} catch (\PDOException $e) {
			return NULL;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getScope($slugOrId): ?Scope
	{
		if(!is_object($slugOrId)) {
			if($s = $this->getPDO()->selectOne("SELECT * FROM SKY_IT_SCOPE WHERE id = ? OR slug = ?", [$slugOrId, $slugOrId])) {
				$this->_assignScope($scope = new Scope(), $s);
				return $scope;
			}
		}
		return $slugOrId instanceof Scope ? $slugOrId : NULL;
	}


	private function _unlinkWithSQL($generator, bool $dropReferences, bool $dropFiles) {
		$ds = DIRECTORY_SEPARATOR;
		$root = $this->getImageRoot();

		$images = [];
		$references = [];

		$dropFiles = $dropFiles && $dropReferences;

		foreach($generator as $record) {
			if($dropFiles && is_file( $f = "$root$ds{$record["slug"]}$ds/{$record['src_slug']}" ))
				unlink($f);
			if($dropFiles && is_file( $f = "$root$ds{$record["slug"]}$ds/{$record["preview_appendix"]}$ds{$record['thumb_slug']}" ))
				unlink($f);
			$images[] = $record["img"];
			$references[] = $record["ref"];
		}

		if($dropReferences && $references)
			$this->getPDO()->exec(sprintf("DELETE FROM SKY_IT_REFERENCE WHERE id IN (%s)", implode(",", $references)));
		if($dropReferences && $images)
			$this->getPDO()->exec(sprintf("DELETE FROM SKY_IT_IMAGE WHERE id IN (%s)", implode(",", $images)));
	}


	public function dropScope($scope, bool $dropReferences = true, bool $dropFiles = false)
	{
		if($scope = $this->getScope($scope)) {
			$this->_unlinkWithSQL($this->getPDO()->select("SELECT
SKY_IT_REFERENCE.id AS ref,
       SKY_IT_SCOPE.slug,
       preview_appendix,
       SKY_IT_IMAGE.id AS img,
       src_slug,
       thumb_slug
FROM SKY_IT_SCOPE
LEFT JOIN SKY_IT_SCOPE ON SKY_IT_SCOPE.id = scope
LEFT JOIN SKY_IT_IMAGE ON reference = SKY_IT_REFERENCE.id
WHERE SKY_IT_SCOPE.id = ?", [$scope->getId()]), $dropReferences, $dropFiles);

			if($image = $this->getPDO()->selectFieldValue("SELECT default_image FROM SKY_IT_SCOPE WHERE id = ?", 'default_image', [$scope->getId()])) {
				$this->dropImage($image * 1, $dropFiles);
			}

			$this->getPDO()->inject("DELETE FROM SKY_IT_SCOPE WHERE id = ?")->send([
				$scope->getId()
			]);
		}
	}

	private function _assignReference(Reference $s, $id, $slug = "", $scope = NULL) {
		$vi = new ValueInjector($s);
		if(is_array($id)) {
			$vi->id = $id['id'];
			$vi->slug = $id['slug'];
			$vi->scope = $this->getScope($id['scope']);
		} else {
			$vi->id = $id;
			$vi->slug = $slug;
			$vi->scope = $scope;
		}
		return $s;
	}

	public function makeReference(string $slug, $scope): ?Reference
	{
		try {
			if($scope = $this->getScope($scope)) {
				$this->getPDO()->inject("INSERT INTO SKY_IT_REFERENCE (slug, scope) VALUES (?,?)")->send([
					$slug,
					$scope->getId()
				]);
				return $this->_assignReference(new Reference(), $this->getPDO()->lastInsertId("SKY_IT_REFERENCE"), $slug, $scope);
			}
		} catch (\PDOException $exception) {
		}
		return NULL;
	}

	public function getReference($ref): ?Reference
	{
		if($ref instanceof Reference)
			return $ref;

		if($s = $this->getPDO()->selectOne("SELECT * FROM SKY_IT_REFERENCE WHERE id = ? OR slug = ?", [$ref, $ref])) {
			return $this->_assignReference(new Reference(), $s);
		}
		return NULL;
	}

	public function dropReference($ref, bool $dropFiles = false)
	{
		if($ref = $this->getReference($ref)) {
			$this->_unlinkWithSQL($this->getPDO()->select("SELECT
    SKY_IT_REFERENCE.id AS ref,
    SKY_IT_SCOPE.slug,
    preview_appendix,
    SKY_IT_IMAGE.id AS img,
    src_slug,
    thumb_slug
FROM SKY_IT_REFERENCE
LEFT JOIN SKY_IT_IMAGE ON reference = SKY_IT_REFERENCE.id
LEFT JOIN SKY_IT_SCOPE ON scope = SKY_IT_SCOPE.id
WHERE SKY_IT_REFERENCE.id = ?", [$ref->getId()]), true, $dropFiles);
		}

	}

	private function _assignImage(ImageRef $ref, $record, $id = 'id', $src = 'src_slug', $thumb = 'thumb_slug', $caption = 'caption', $alt = 'alternative', $slug = 'slug', $preview = 'preview_appendix') {
		$vi = new ValueInjector($ref);
		$vi->id = $record[$id] * 1;
		$vi->source = sprintf("%s/%s/%s", $this->getUriRoot(), $record[$slug], $record[$src]);
		$vi->preview = $record[$thumb] ? sprintf("%s/%s/%s/%s", $this->getUriRoot(), $record[$slug], $record[$preview], $record[$thumb]) : NULL;
		$vi->caption = $record[$caption];
		$vi->alternative = $record[$alt];
		return $ref;
	}

	private function _getMainImageOfReference($reference): ?array {
		if($i = $this->getPDO()->selectOne("SELECT
SKY_IT_IMAGE.id,
       SKY_IT_SCOPE.slug,
        preview_appendix,
       src_slug,
       thumb_slug,
       caption,
       alternative
FROM SKY_IT_REFERENCE
JOIN SKY_IT_IMAGE ON main_image = SKY_IT_IMAGE.id
JOIN SKY_IT_SCOPE ON scope = SKY_IT_SCOPE.id
WHERE SKY_IT_REFERENCE.id = :tag OR SKY_IT_REFERENCE.slug = :tag", ['tag' => $reference instanceof Reference ? $reference->getId() : $reference])) {
			return $i;
		}
		return NULL;
	}

	public function getMainImageOfReference($reference): ?ImageRefInterface
	{
		if($i = $this->_getMainImageOfReference($reference)) {
			return $this->_assignImage(new ImageRef(), $i);
		}
		return NULL;
	}

	public function getImagesOfReference($reference): ?ImageRefCollection
	{
		$col = [];
		$reference = $this->getReference($reference);
		$main = NULL;

		foreach($this->getPDO()->select("SELECT
        MI.id AS mid,
       T1.id AS iid,
    SKY_IT_SCOPE.slug,
    preview_appendix,
    MI.src_slug AS main_src,
       MI.thumb_slug AS main_thumb,
       MI.caption AS main_caption,
       MI.alternative AS main_alternative,
       T1.src_slug AS img_src,
       T1.thumb_slug AS img_thumb,
       T1.caption AS img_caption,
       T1.alternative AS img_alternative
FROM SKY_IT_REFERENCE
    JOIN SKY_IT_SCOPE ON scope = SKY_IT_SCOPE.id
    LEFT JOIN SKY_IT_IMAGE MI ON MI.id = main_image
    LEFT JOIN SKY_IT_IMAGE T1 ON T1.reference = SKY_IT_REFERENCE.id
WHERE MI.id != T1.id AND SKY_IT_REFERENCE.id = ?
ORDER BY priority", [$reference->getId()]) as $record) {
			if(!$main && $record["mid"]) {
				$main = $this->_assignImage(new ImageRef(), $record, 'mid', 'main_src', 'main_thumb', 'main_caption', 'main_alternative');
			}
			if($record["iid"]) {
				$col[] = $this->_assignImage(new ImageRef(), $record, 'iid', 'img_src', 'img_thumb', 'img_caption', 'img_alternative');
			}
		}
		if($col) {
			$vi = new ValueInjector($cc = new ImageRefCollection());
			$vi->collection = $col;
			$vi->mainImage = $main;
			$vi->reference = $reference;
			return $cc;
		}
		return NULL;
	}

	public function getImagesOfReferences(iterable $references): array
	{
		$refs = [];

		foreach($references as $ref) {
			if($r = $this->getReference($ref))
				$refs[ $r->getId() ] = ['r' => $r, 's' => $ref, 'm' => NULL, 'i' => []];
		}

		if($refs) {
			$refFilter = implode(",", array_keys($refs));

			foreach($this->getPDO()->select("SELECT
       SKY_IT_REFERENCE.id,
        MI.id AS mid,
       T1.id AS iid,
    SKY_IT_SCOPE.slug,
    preview_appendix,
    MI.src_slug AS main_src,
       MI.thumb_slug AS main_thumb,
       MI.caption AS main_caption,
       MI.alternative AS main_alternative,
       T1.src_slug AS img_src,
       T1.thumb_slug AS img_thumb,
       T1.caption AS img_caption,
       T1.alternative AS img_alternative
FROM SKY_IT_REFERENCE
    JOIN SKY_IT_SCOPE ON scope = SKY_IT_SCOPE.id
    LEFT JOIN SKY_IT_IMAGE MI ON MI.id = main_image
    LEFT JOIN SKY_IT_IMAGE T1 ON T1.reference = SKY_IT_REFERENCE.id
WHERE MI.id != T1.id AND SKY_IT_REFERENCE.id IN ($refFilter)
ORDER BY SKY_IT_REFERENCE.id, priority") as $record) {
				$rid = $record["id"];
				if(!$refs[$rid]['m'] && $record["mid"])
					$refs[$rid]['m'] = $this->_assignImage(new ImageRef(), $record, 'mid', 'main_src', 'main_thumb', 'main_caption', 'main_alternative');

				if($record["iid"]) {
					$refs[$rid]['i'][] = $this->_assignImage(new ImageRef(), $record, 'iid', 'img_src', 'img_thumb', 'img_caption', 'img_alternative');
				}
			}

			$final = [];
			foreach($refs as $ref) {
				$vi = new ValueInjector($cc = new ImageRefCollection());
				$vi->collection = $ref["i"];
				$vi->mainImage = $ref["m"];
				$vi->reference = $ref["r"];
				$final[ $ref["s"] ] = $cc;
			}
			return $final;
		}

		return [];
	}

	public function setMainImageOfReference(LocalImageSetInterface $image, Reference $reference): bool
	{
		try {
			$this->getPDO()->beginTransaction();
			if($main = $this->getPDO()->selectFieldValue("SELECT main_image FROM SKY_IT_REFERENCE WHERE id = ?", 'main_image', [$reference->getId()])) {
				if(!$this->dropImage($main["main_image"] * 1, true))
					throw new DeleteImageException("Can not delete main image", DeleteImageException::CAN_NOT_DELETE_MAIN_IMAGE_REF_CODE);
			}



			$this->getPDO()->commit();
		} catch (\Throwable $exception) {
			$this->getPDO()->rollBack();
			throw $exception;
		}
	}

	public function addImageToReference(LocalImageSetInterface $image, Reference $reference): bool
	{
		// TODO: Implement addImageToReference() method.
	}

	public function dropImage($image, bool $dropFile = false): bool
	{
		// TODO: Implement dropImage() method.
	}

	public function findImages(int $options, $scope = NULL): ?array
	{
		// TODO: Implement findImages() method.
	}
}