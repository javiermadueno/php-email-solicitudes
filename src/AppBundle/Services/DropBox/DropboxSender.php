<?php
/**
 * Created by PhpStorm.
 * User: jmadueno
 * Date: 31/05/2016
 * Time: 15:55
 */

namespace AppBundle\Services\DropBox;

use Dropbox\Client as Dropbox;
use League\Flysystem\Config;
use League\Flysystem\Dropbox\DropboxAdapter;

class DropboxSender
{

    protected $dropbox;

    public function __construct(Dropbox $client )
    {
        $this->dropbox = new DropboxAdapter($client);
    }


    public function send(\SplFileObject $file)
    {
        $name = $file->getFileInfo()->getFilename();

        $content = $file->fread($file->getSize());

        $this->dropbox->write("Informes TTP/{$name}", $content , new Config());
    }
} 