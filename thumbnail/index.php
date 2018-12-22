<?php

class ThumbnailImage
{

    private $options;
    private $imageName;
    private $mimeType;
    private $path_folder_of_thumb;
    private $thumb_name;
    private $relative_path_folder_of_thumb;
    private $relative_path_folder_of_thumb_to_create;

    public function doThumbnail($img_path)
    {
        if ($img_path) {
            $this->setFolderPathOfThumb($img_path);
            $this->getImageName($img_path);
            $this->createThumbNameByOptions();
            if ($this->isHaveFolderImageOrCreateIt()) {
                if ($this->isHaveThumb()) {
                    return $this->responseThumb();
                } else {
                    $this->createThumb($img_path);
                    return $this->responseThumb();
                }
            } else {
                $this->createThumb($img_path);
                return $this->responseThumb();
            }
        }
    }

    public function setOptions($options)
    {
        $this->options = $options;
    }

    private function responseThumb()
    {
        if ($this->options["format"] === "tag") {
            $image_tag = "<img ";
            $image_tag .= 'src ="' . $this->getRelativePathToThumb() . '"';
            if ($this->options["alt"]) {
                $image_tag .= ' alt="' . $this->options["alt"] . '"';
            }
            if ($this->options["title"]) {
                $image_tag .= ' title="' . $this->options["title"] . '"';
            }
            $image_tag .= '/>';
            return $image_tag;
        } else {
            return $this->getRelativePathToThumb();
        }
    }

    private function getRelativePathToThumb()
    {
        return $this->relative_path_folder_of_thumb . '/' . $this->thumb_name;
    }

    private function getRelativePathToThumbToCreate()
    {
        return $this->relative_path_folder_of_thumb_to_create . '/' . $this->thumb_name;

    }

    private function createThumb($img_path)
    {
        include_once CURRENT_WORKING_DIR . '/thumbnail/image_resizer.php';
        $img_path = CURRENT_WORKING_DIR . $img_path;

        $image = new ImageResize($img_path);

        if ($this->options["width"] && $this->options["height"] && $this->options["crop"]) {
            $cropPosition = 2;
            switch ($this->options["crop"]) {
                case "center":
                    $cropPosition = 2;
                    break;
                case "top":
                    $cropPosition = 1;
                    break;
                case "bottom":
                    $cropPosition = 3;
                    break;
                case "left":
                    $cropPosition = 4;
                    break;
                case "right":
                    $cropPosition = 5;
                    break;
                case "topCenter":
                    $cropPosition = 6;
                    break;
            }

            $image->crop($this->options["width"], $this->options["height"], $cropPosition);
        } else if ($this->options["width"] && $this->options["height"]) {
            $image->resize($this->options["width"], $this->options["height"], true);
        } else if ($this->options["width"]) {
            $image->resizeToWidth($this->options["width"], true);
        } else if ($this->options["height"]) {
            $image->resizeToHeight($this->options["height"], true);
        }

        $image->save($this->getRelativePathToThumbToCreate(), null, $this->options["quality"]);
    }

    private function isHaveThumb()
    {
        $pathToThumb = $this->path_folder_of_thumb . '/' . $this->thumb_name;
        if (file_exists($pathToThumb)) {
            return true;
        }
        return false;
    }

    private function setFolderPathOfThumb($img_path)
    {
        $image_path_hash = md5($img_path);
        $this->path_folder_of_thumb = CURRENT_WORKING_DIR . '/images/_thumbnails/' . $image_path_hash;
        $this->relative_path_folder_of_thumb = '/images/_thumbnails/' . $image_path_hash;
        $this->relative_path_folder_of_thumb_to_create = './images/_thumbnails/' . $image_path_hash;
    }

    private function getImageName($img_path)
    {
        $expldImagePath = explode("/", $img_path);
        $lastExpldElement = array_pop($expldImagePath);
        $expldImageNameWithMimeType = explode(".", $lastExpldElement);
        $this->imageName = $expldImageNameWithMimeType[0];
        $this->mimeType = $expldImageNameWithMimeType[1];
    }

    private function isHaveFolderImageOrCreateIt()
    {
        if (!file_exists($this->path_folder_of_thumb)) {
            mkdir($this->path_folder_of_thumb);
            return false;
        }

        return true;
    }

    private function createThumbNameByOptions()
    {
        $this->thumb_name = $this->imageName;
        if ($this->options["width"]) {
            $this->thumb_name .= '_' . $this->options["width"];
        }
        if ($this->options["height"]) {
            $this->thumb_name .= '_' . $this->options["height"];
        }
        if ($this->options["crop"]) {
            $this->thumb_name .= '_' . $this->options["crop"];
        }
        if ($this->options["rotate"]) {
            $this->thumb_name .= '_' . $this->options["rotate"];
        }
        $this->thumb_name .= '.' . $this->mimeType;
    }

}