<?php

namespace Sulu\Bundle\MediaBundle\EventListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;

class MediaUploadListener
{
    protected $storage;

    public function __construct(StorageInterface $storage) {
        $this->storage = $storage;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        $this->resizeFile($entity);
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();

        $this->resizeFile($entity);
    }

    private function resizeFile($entity)
    {
        if (!$entity instanceof Media) {
            return;
        }

        $files = $entity->getFiles();
        $fileContainer = $files[0];
        $fileVersion = $fileContainer->getLatestFileVersion();
        $path = $this->storage->load($fileVersion->getName(), $fileVersion->getVersion(), $fileVersion->getStorageOptions());

        $dst = $this->resize_image($path, 1200, 800);
        imagejpeg($dst, $path);
    }

    function resize_image($file, $w, $h, $crop=FALSE) {
        list($width, $height) = getimagesize($file);
        $r = $width / $height;
        if ($crop) {
            if ($width > $height) {
                $width = ceil($width-($width*abs($r-$w/$h)));
            } else {
                $height = ceil($height-($height*abs($r-$w/$h)));
            }
            $newwidth = $w;
            $newheight = $h;
        } else {
            if ($w/$h > $r) {
                $newwidth = $h*$r;
                $newheight = $h;
            } else {
                $newheight = $w/$r;
                $newwidth = $w;
            }
        }
        $src = \imagecreatefromjpeg($file);
        $dst = \imagecreatetruecolor($newwidth, $newheight);
        \imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

        return $dst;
    }
}