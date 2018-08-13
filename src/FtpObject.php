<?php
namespace Rumd3x\Ftp;

use Carbon\Carbon;
use Rumd3x\BaseObject\BaseObject;

abstract class FtpObject extends BaseObject {

    protected $ftp;

    public $name;
    public $full_name;
    public $timestamp;
    public $permission;

    public function __construct(Ftp $ftp, $name = NULL) {
        $this->setFtp($ftp);
        $this->timestamp = Carbon::now();
        if (!empty($name)) $this->name = $name;
        if (!empty($name)) $this->full_name = $this->ftp->currentFolder()."/".$name;
    }

    public function getFtp() {
        return $this->ftp;
    }

    public function setFtp(Ftp $ftp) {
        $this->ftp = $ftp;
        return $this;
    }

    public function __toString() {
        return $this->full_name;
    }
}