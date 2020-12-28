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

namespace Skyline\ImageTool\Controller;


use Skyline\API\Controller\AbstractAPIActionController;
use Skyline\API\Render\JSONRender;
use Skyline\ImageTool\Render\ImageTranslationRender;
use Skyline\ImageTool\Render\LocalImageRef;
use Skyline\ImageTool\Service\ImageToolService;
use Skyline\ImageTool\Service\Reference;

use Skyline\ImageTool\Render\ImagePreviewRender;
use Skyline\ImageTool\Service\Scope;

abstract class AbstractImageToolAPIController extends AbstractAPIActionController
{
	const UPLOAD_OPTION_SCALE_TO_BEST = 1;
	const UPLOAD_OPTION_RENDER_PREVIEW = 2;
	const UPLOAD_OPTION_MAKE_MAIN = 4;
	const UPLOAD_OPTION_RENDER_WATERMARK = 8;
	const UPLOAD_OPTION_APPLY_TRANSFORMATION = 16;


	/**
	 * @param $request
	 * @return bool
	 */
	protected function isScopeRequest($request): bool {
		return isset($request["s"]);
	}

	/**
	 * @param $request
	 * @return bool
	 */
	protected function isReferenceRequest($request): bool {
		return isset($request["r"]);
	}

	/**
	 * @param $request
	 * @return bool
	 */
	protected function isImageRequest($request): bool {
		return isset($request["i"]);
	}

	/**
	 * @param $request
	 * @return bool
	 */
	protected function isQueryRequest($request): bool {
		return isset($request["l"]);
	}

	/**
	 * @param $request
	 * @return bool
	 */
	protected function isChangeRequest($request): bool {
		return isset($request["n"]) || isset($request["d"]) || isset($request["o"]) || isset($request["m"]);
	}

	/**
	 * @param $request
	 * @param Scope|null $scope
	 * @return bool
	 */
	protected function readScopeRequet($request, Scope &$scope = NULL): bool {
		if($this->isScopeRequest($request)) {
			/** @var ImageToolService $iTool */
			$iTool = $this->get(ImageToolService::SERVICE_NAME);

			if($sc = $iTool->getScope( $request['s'] )) {
				$scope = $sc;
				return true;
			}
		}
		return false;
	}

	/**
	 * @param $request
	 * @param Reference|null $reference
	 * @return bool
	 */
	protected function readReferenceRequest($request , Reference &$reference = NULL): bool {
		if($this->isReferenceRequest($request)) {
			/** @var ImageToolService $iTool */
			$iTool = $this->get(ImageToolService::SERVICE_NAME);

			if($ref = $iTool->getReference($request["r"])) {
				$reference = $ref;
				return true;
			}
		}
		return false;
	}

	/**
	 * @param $request
	 * @param array|null $image
	 * @return bool
	 */
	protected function readImageRequest($request, array &$image = NULL): bool {
		if($this->isImageRequest($request)) {
			/** @var ImageToolService $iTool */
			$iTool = $this->get(ImageToolService::SERVICE_NAME);

			if(strpos($request['i'], '/') !== false ) {
				$image = $iTool->getPDO()->selectOne("SELECT
SKY_IT_IMAGE.id, reference, src_slug, thumb_slug, caption, alternative
FROM SKY_IT_IMAGE
JOIN SKY_IT_REFERENCE ON reference = SKY_IT_REFERENCE.id
JOIN SKY_IT_SCOPE ON scope = SKY_IT_SCOPE.id
WHERE SKY_IT_SCOPE.slug = :s AND src_slug = :i", ['s' => dirname($request["i"]), 'i' => basename($request['i'])]);
			} else {
				$image = $iTool->getPDO()->selectOne("SELECT id, reference, src_slug, thumb_slug, caption, alternative FROM SKY_IT_IMAGE WHERE id = :i OR src_slug = :i", ['i' => $request['i']]);
			}
			return $image ? true : false;
		}
		return false;
	}

	protected function readFileRequest($files, &$name=NULL, &$type=NULL, &$temp=NULL, &$error=NULL, &$size=NULL): bool {
		if(isset($files["f"])) {
			$name = $files["f"]["name"];
			$type = $files["f"]["type"];
			$temp = $files["f"]["tmp_name"];
			$error = $files["f"]["error"]*1;
			$size = $files["f"]["size"]*1;
			return true;
		}
		return false;
	}

	/**
	 * This method must be overridden and called from the main action.
	 */
	protected function fetchQueryAction(array $request) {
		$this->preferRender(JSONRender::RENDER_NAME);

		/** @var ImageToolService $iTool */
		$iTool = $this->get(ImageToolService::SERVICE_NAME);

		$model = $this->getModel();

		$select = ($request["l"] ?? 511) * 1;
		$images = [];
		$references = [];

		$registerImage = function($record) use (&$images, &$scope, $iTool, $select) {
			$img = [];
			$root = ($select & 256) ? $iTool->getURI($scope) : "";
			$img["src"] = sprintf("%s%s", $root, $record["src_slug"]);
			if($select & 4)
				$img["id"] = $record["id"] * 1;
			if($select & 2)
				$img["thumb"] = $record["thumb_slug"] ? sprintf("%s%s/%s", $root, $scope->getPreviewAppendix(), $record["thumb_slug"]) : false;
			if($select & 8)
				$img["caption"] = $record["caption"];
			if($select & 16)
				$img["alternative"] = $record["alternative"];
			if($select & 1) {
				$root = $iTool->getlocalPath($scope);

				$img["linked"]["src"] = is_file( $root.DIRECTORY_SEPARATOR.$record["src_slug"] );
				if($record["thumb_slug"])
					$img["linked"]["thumb"] = is_file( $root.DIRECTORY_SEPARATOR.$record["thumb_slug"] );
			}
			$images[ $record["id"] ] = $img;
		};

		$registerReference = function($record) use (&$references) {
			$ref = $record["ref"];
			$references[ $ref ]["slug"] = $record["slug"];
			if(!isset($references[$ref]["main"]))
				$references[$ref]["main"] = false;
			if($record["main"] && !$references[$ref]["main"])
				$references[$ref]["main"] = $record["image"];
			$references[$ref]['images'][] = $record["image"];
		};

		$registerScope = function($scope) use ($model, $select, $iTool) {
			$sc = [
				'id' => $scope->getId(),
				'root' => $iTool->getURI($scope)
			];
			if($select & 32)
				$sc["name"] = $scope->getName();
			if($select & 64)
				$sc["description"] = $scope->getDescription();

			$model["scope"] = $sc;
		};

		if($this->isScopeRequest($request)) {
			if($this->readScopeRequet($request, $scope)) {
				if($select & 512) {
					$registerScope($scope);
				}

				if($select & 1024) {
					foreach($iTool->getPDO()->select("SELECT
    SKY_IT_REFERENCE.id AS ref,
       slug,
       SKY_IT_IMAGE.id AS image,
       is_main AS main
FROM SKY_IT_REFERENCE
LEFT JOIN SKY_IT_IMAGE ON reference = SKY_IT_REFERENCE.id
WHERE scope = ?
ORDER BY reference, priority", [$scope->getId()]) as $record) {
						$registerReference($record);
					}
				}

				if($select & 128) {
					foreach($iTool->getPDO()->select("SELECT SKY_IT_IMAGE.id, type, src_slug, thumb_slug, caption, alternative, is_main FROM SKY_IT_IMAGE JOIN SKY_IT_REFERENCE on reference = SKY_IT_REFERENCE.id WHERE scope = ?", [$scope->getId()]) as $record) {
						$registerImage($record);
					}
				}
			} else
				throw new \Exception("Scope not found", 404);
		}

		$image = NULL;
		if($this->isImageRequest($request) && $this->readImageRequest($request, $image)) {
			$request["r"] = $image["reference"];
		}

		if($this->isReferenceRequest($request)) {
			if($this->readReferenceRequest($request, $reference)) {
				$scope = $reference->getScope();
				if($select & 512) {
					$registerScope($scope);
				}

				if($select & 1024) {
					foreach($iTool->getPDO()->select("SELECT
    SKY_IT_REFERENCE.id AS ref,
       slug,
       SKY_IT_IMAGE.id AS image,
       is_main AS main
FROM SKY_IT_REFERENCE
LEFT JOIN SKY_IT_IMAGE ON reference = SKY_IT_REFERENCE.id
WHERE SKY_IT_REFERENCE.id = ?
ORDER BY reference, priority", [$reference->getId()]) as $record) {
						$registerReference($record);
					}
				}

				if($select & 128) {
					foreach((isset($image) ? [$image] : $iTool->getPDO()->select("SELECT SKY_IT_IMAGE.id, type, src_slug, thumb_slug, caption, alternative, is_main FROM SKY_IT_IMAGE JOIN SKY_IT_REFERENCE on reference = SKY_IT_REFERENCE.id WHERE SKY_IT_REFERENCE.id = ?", [$reference->getId()])) as $record) {
						$registerImage($record);
					}
				}
			}
		}

		$model["references"] = array_values($references);
		$model["images"] = $images;
	}

	protected function changeQueryAction( array $request ) {
		$this->preferRender(JSONRender::RENDER_NAME);

		/** @var ImageToolService $iTool */
		$iTool = $this->get(ImageToolService::SERVICE_NAME);
		$model = $this->getModel();

		if($this->isScopeRequest($request)) {
			// Change scope
			if($this->readScopeRequet($request, $scope)) {
				$name = $request["n"] ?? NULL;
				$desc = $request["d"] ?? NULL;

				if(NULL !== $name && NULL !== $desc)
					$iTool->getPDO()->inject("UPDATE SKY_IT_SCOPE SET name=?, description=? WHERE id=?")->send([
						$name,
						$desc,
						$scope->getId()
					]);
				elseif(NULL !== $name) {
					$iTool->getPDO()->inject("UPDATE SKY_IT_SCOPE SET name=? WHERE id=?")->send([
						$name,
						$scope->getId()
					]);
				}
				elseif(NULL !== $desc) {
					$iTool->getPDO()->inject("UPDATE SKY_IT_SCOPE SET description=? WHERE id=?")->send([
						$desc,
						$scope->getId()
					]);
				}

				$model['scope'] = [
					'id' => $scope->getId(),
					'root' => $iTool->getURI($scope),
					'name' => $name ?? $scope->getName(),
					'description' => $desc ?? $scope->getDescription()
				];
			} else
				throw new \Exception("Scope not found", 404);
		}

		if($this->isImageRequest($request)) {
			if($this->readImageRequest($request, $image)) {
				$name = $request["n"] ?? NULL;
				$desc = $request["d"] ?? NULL;

				if(NULL !== $name && NULL !== $desc) {
					$iTool->getPDO()->inject("UPDATE SKY_IT_IMAGE SET caption=?, alternative=? WHERE id=?")->send([
						$name,
						$desc,
						$image["id"]
					]);
					$image["caption"] = $name;
					$image["alternative"] = $desc;
				}
				elseif(NULL !== $name) {
					$iTool->getPDO()->inject("UPDATE SKY_IT_IMAGE SET caption=? WHERE id=?")->send([
						$name,
						$image["id"]
					]);
					$image["caption"] = $name;
				}
				elseif(NULL !== $desc) {
					$iTool->getPDO()->inject("UPDATE SKY_IT_IMAGE SET alternative=? WHERE id=?")->send([
						$desc,
						$image["id"]
					]);
					$image["alternative"] = $desc;
				}

				$model["image"] = $image;
			}
		}

		if($this->isReferenceRequest($request)) {
			if($this->readReferenceRequest($request, $reference)) {
				$ref = $reference->getID();

				if(isset($request['o'])) {
					// Reorder
					$order = array_values( json_decode( $request['o'], true ) );
					$inj = $iTool->getPDO()->inject("UPDATE SKY_IT_IMAGE SET priority = :p WHERE (id = :i OR src_slug = :i) AND reference = $ref");

					for($e=0;$e<count($order);$e++) {
						$img = $order[$e];
						$inj->send([
							'p' => $e+1,
							'i' => $img
						]);
					}
				}

				if(isset($request["m"])) {
					$iTool->getPDO()->exec("UPDATE SKY_IT_IMAGE SET is_main = 0 WHERE reference = $ref");
					$iTool->getPDO()->inject("UPDATE SKY_IT_IMAGE SET is_main = 1 WHERE reference = $ref AND (id = :i OR src_slug = :i)")->send([
						'i' => $request['m']
					]);
				}

				if(isset($request["rm"])) {
					if($image = $iTool->getPDO()->selectOne("SELECT id AS i, src_slug AS s, thumb_slug AS t FROM SKY_IT_IMAGE WHERE reference = $ref AND id = ?", [$request["rm"]])) {
						if($image["t"]) {
							$root = $iTool->getLocalPath($reference->getScope(), true);
							if(is_file($f = "$root".DIRECTORY_SEPARATOR.$image["t"]))
								unlink($f);
						}

						$root = $iTool->getLocalPath($reference->getScope(), false);
						if(is_file($f = "$root".DIRECTORY_SEPARATOR.$image["s"]))
							unlink($f);

						$iTool->getPDO()->exec("DELETE FROM SKY_IT_IMAGE WHERE id = {$image['i']}");
					}
				}

				$model["order"] = (function($PDO) use ($ref) {
					$list = [];
					foreach($PDO->select("SELECT id, src_slug AS src FROM SKY_IT_IMAGE WHERE reference = $ref ORDER BY priority") as $record)
						$list[ $record['id'] ] = $record['src'];
					return $list;
				})($iTool->getPDO());
				$model["main"] = $iTool->getPDO()->selectFieldValue("SELECT id FROM SKY_IT_IMAGE WHERE reference = $ref AND is_main = 1", 'id') ?: false;
			}
		}
	}


	protected function getDesiredImageRenderInfo(&$image, &$preview, &$fixOrientation, &$watermark): bool {
		$image = 1920;
		$preview = 400;
		$fixOrientation = false;
		return true;
	}


	protected function putQueryAction(array $request, array $files, int $requiredOptions = 0, int $defaultOptions = 1) {
		$this->preferRender(JSONRender::RENDER_NAME);

		/** @var ImageToolService $iTool */
		$iTool = $this->get(ImageToolService::SERVICE_NAME);
		$model = $this->getModel();

		if($this->isReferenceRequest($request)) {
			if($this->readReferenceRequest($request, $reference) && $this->readFileRequest($files, $name, $type, $temp, $error)) {
				$model["slug"] = $slug = basename( preg_replace("/[^a-z0-9\-_.]+/i", '-',  @ $request["s"] ?: $name) );
				$options = ($request['o'] ?? $defaultOptions) | $requiredOptions;

				if($error)
					throw new \RuntimeException($error);

				$root = $iTool->getLocalPath($reference->getScope());
				if(is_file($src = $root.DIRECTORY_SEPARATOR.$slug))
					throw new \RuntimeException("File already exists.", 407);

				if($options & (static::UPLOAD_OPTION_SCALE_TO_BEST|static::UPLOAD_OPTION_RENDER_PREVIEW|static::UPLOAD_OPTION_APPLY_TRANSFORMATION)) {
					if(!class_exists(ImagePreviewRender::class))
						throw new \RuntimeException("Can not manipulate images because of missing the skyline/image-render-tool package");
					if(!$this->getDesiredImageRenderInfo($maxImageSize, $maxPreviewSize, $fixOrientation, $watermark))
						throw new \RuntimeException("Can not manipulate images because of missing desired image sizes");

					if($options & static::UPLOAD_OPTION_RENDER_PREVIEW) {
						$pRoot = $iTool->getLocalPath($reference->getScope(), true);
						if(is_file($preview = $pRoot.DIRECTORY_SEPARATOR.$slug))
							throw new \RuntimeException("File already exists.", 408);
					}
				}

				if(!move_uploaded_file($temp, $src))
					throw new \RuntimeException("Could not copy image to destination.", 400);

				$imageType = -1;
				$hasPreview = false;

				try {
					if($options & static::UPLOAD_OPTION_APPLY_TRANSFORMATION) {
						$transformation = json_decode( $request["add"], true );
						list($tx, $ty) = $transformation["translation"];
						$scale = $transformation["scale"] * 1;
						list($fw, $fh) = $transformation["frame"];

						if($fw && $fh) {
							$image = new LocalImageRef($src);
							$imageType = $image->getType();

							$gen = new ImageTranslationRender($fw, $fh);
							if(!$gen->renderTranslation($image, $tx, $ty, $scale))
								throw new \RuntimeException("Could not apply transformation");
							$q = $image->getType() == LocalImageRef::IMAGE_PNG ? 9 : ($image->getType() == LocalImageRef::IMAGE_JPEG ? 75 : 0);
							@unlink($src);
							if(!$image->save($src, $q))
								throw new \RuntimeException("Could not save the preview image");
							unset($image);
						}
					} else {
						if($options & static::UPLOAD_OPTION_RENDER_PREVIEW) {
							$image = new LocalImageRef($src);
							$imageType = $image->getType();

							if($image->getWidth() > $maxPreviewSize || $image->getHeight() > $maxPreviewSize) {
								$gen = new ImagePreviewRender($maxPreviewSize);
								if(!$gen->generatePreview($image, $fixOrientation)) {
									throw new \RuntimeException("Could not create preview from image");
								}
							}
							$q = $image->getType() == LocalImageRef::IMAGE_PNG ? 9 : ($image->getType() == LocalImageRef::IMAGE_JPEG ? 75 : 0);
							if(!$image->save($preview, $q))
								throw new \RuntimeException("Could not save the preview image");
							$hasPreview = true;
							unset($image);
						}

						if($options & static::UPLOAD_OPTION_SCALE_TO_BEST) {
							$image = new LocalImageRef($src);
							$imageType = $image->getType();

							if($image->getWidth() > $maxImageSize || $image->getHeight() > $maxImageSize) {
								$gen = new ImagePreviewRender($maxImageSize);
								if(!$gen->generatePreview($image, $fixOrientation)) {
									throw new \RuntimeException("Could not scale image to desired size");
								}
								$q = $image->getType() == LocalImageRef::IMAGE_PNG ? 9 : ($image->getType() == LocalImageRef::IMAGE_JPEG ? 75 : 0);
								@unlink($src);
								if(!$image->save($src, $q))
									throw new \RuntimeException("Could not save the preview image");
							}
							unset($image);
						}
					}

					$main = $options & static::UPLOAD_OPTION_MAKE_MAIN ? 1 : 0;
					$ref = $reference->getID();

					if($main)
						$iTool->getPDO()->exec("UPDATE SKY_IT_IMAGE SET is_main = 0 WHERE reference = $ref");

					$iTool->getPDO()->inject("INSERT INTO SKY_IT_IMAGE (type, reference, src_slug, thumb_slug, caption, alternative, is_main) VALUES ($imageType, $ref, ?, ?, ?, ?, $main)")->send([
						$slug,
						$hasPreview ? $slug : NULL,
						$request["n"] ?? NULL,
						$request["d"] ?? NULL
					]);
				} catch (\Throwable $throwable) {
					if($src && is_file($src))
						unlink($src);
					if($preview && is_file($preview))
						unlink($preview);
					throw $throwable;
				}
			} else
				throw new \RuntimeException("Reference not found", 404);
		}
	}
}