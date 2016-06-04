<?php
/**
 * \Elabftw\Elabftw\Uploads
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use Exception;

/**
 * All about the file uploads
 */
class Uploads extends Entity
{
    /** pdo object */
    protected $pdo;

    /** experiments or items */
    public $type;

    /** our item */
    public $itemId;

    /** what algo for hashing */
    private $hashAlgorithm = 'sha256';

    /**
     * Constructor
     *
     * @param string $type experiment or items
     * @param int $itemId
     * @param int|null $id ID of a single file
     */
    public function __construct($type, $itemId, $id = null)
    {
        $this->pdo = Db::getConnection();
        $this->type = $type;
        $this->itemId = $itemId;

        if (!is_null($id)) {
            $this->setId($id);
        }
    }

    /**
     * Main method for normal file upload
     *
     * @param string|array $file Either pass it the $_FILES array or the string of the local file path
     * @return bool
     */
    public function create($file)
    {
        if (!is_array($file) || count($file) === 0) {
            throw new Exception('No files received');
        }
        // check we own the experiment we upload to
        $this->checkPermission();

        $realName = $this->getSanitizedName($file['file']['name']);
        $longName = $this->getCleanName() . "." . Tools::getExt($realName);
        $fullPath = ELAB_ROOT . 'uploads/' . $longName;

        // Try to move the file to its final place
        $this->moveFile($file['file']['tmp_name'], $fullPath);

        // final sql
        return $this->dbInsert($realName, $longName, $this->getHash($fullPath));
    }

    /**
     * Called from ImportZip class
     *
     * @param string $file The string of the local file path stored in .elabftw.json of the zip archive
     * @return bool
     */
    public function createFromLocalFile($file)
    {
        if (!is_readable($file)) {
            throw new Exception('No file here!');
        }

        $realName = basename($file);
        $longName = $this->getCleanName() . "." . Tools::getExt($realName);
        $fullPath = ELAB_ROOT . 'uploads/' . $longName;

        $this->moveFile($file, $fullPath);

        return $this->dbInsert($realName, $longName, $this->getHash($fullPath));
    }

    /**
     * Can we upload to that experiment?
     * Make sure we own it.
     *
     * @throws Exception if we cannot upload file to this experiment
     */
    private function checkPermission()
    {
        if ($this->type === 'experiments') {
            if (!is_owned_by_user($this->itemId, 'experiments', $_SESSION['userid'])) {
                throw new Exception('Not your experiment!');
            }
        }
    }

    /**
     * Create a clean filename
     * Remplace all non letters/numbers by '.' (this way we don't lose the file extension)
     *
     * @param string $rawName The name of the file as it was on the user's computer
     * @return string The cleaned filename
     */
    private function getSanitizedName($rawName)
    {
        return preg_replace('/[^A-Za-z0-9]/', '.', $rawName);
    }

    /**
     * Place a file somewhere
     *
     * @param $string orig from
     * @param string dest to
     * @throws Exception if cannot move the file
     */
    private function moveFile($orig, $dest)
    {
        if (!rename($orig, $dest)) {
            throw new Exception('Error while moving the file. Check folder permissons!');
        }
    }

    /**
     * Generate the hash based on selected algorithm
     *
     * @param string $file The full path to the file
     * @return string|null the hash or null if file is too big
     */
    private function getHash($file)
    {
        if (filesize($file) < 5000000) {
            return hash_file($this->hashAlgorithm, $file);
        }

        return null;
    }

    /**
     * Create a unique long filename
     *
     * @return string Return a random string
     */
    protected function getCleanName()
    {
        return hash("sha512", uniqid(rand(), true));
    }

    /**
     * Make the final SQL request to store the file
     *
     * @param string $realName The clean name of the file
     * @param string $longName The sha512 name
     * @param string $hash The hash string of our file
     * @throws Exception if request fail
     * @return bool
     */
    private function dbInsert($realName, $longName, $hash)
    {
        $sql = "INSERT INTO uploads(
            real_name,
            long_name,
            comment,
            item_id,
            userid,
            type,
            hash,
            hash_algorithm
        ) VALUES(
            :real_name,
            :long_name,
            :comment,
            :item_id,
            :userid,
            :type,
            :hash,
            :hash_algorithm
        )";

        $req = $this->pdo->prepare($sql);
        $req->bindParam(':real_name', $realName);
        $req->bindParam(':long_name', $longName);
        // comment can be edited after upload
        // not i18n friendly because it is used somewhere else (not a valid reason, but for the moment that will do)
        $req->bindValue(':comment', 'Click to add a comment');
        $req->bindParam(':item_id', $this->itemId);
        $req->bindParam(':userid', $_SESSION['userid']);
        $req->bindParam(':type', $this->type);
        $req->bindParam(':hash', $hash);
        $req->bindParam(':hash_algorithm', $this->hashAlgorithm);

        return $req->execute();
    }

    /**
     * Read infos from an upload ID
     *
     * @return array
     */
    private function read()
    {
        // Check that the item we view has attached files
        $sql = "SELECT * FROM uploads WHERE id = :id AND type = :type";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id);
        $req->bindParam(':type', $this->type);
        $req->execute();

        return $req->fetch();
    }

    /**
     * Read all uploads for an item
     *
     * @return array
     */
    public function readAll()
    {
        $sql = "SELECT * FROM uploads WHERE item_id = :id AND type = :type";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->itemId);
        $req->bindParam(':type', $this->type);
        $req->execute();

        return $req->fetchAll();
    }


    /**
     * Create a jpg thumbnail from images of type jpg, png or gif.
     *
     * @param string $src Path to the original file
     * @param string $ext Extension of the file
     * @param string $dest Path to the place to save the thumbnail
     * @param int $desiredWidth Width of the thumbnail (height is automatic depending on width)
     * @return null|false
     */
    public function makeThumb($src, $ext, $dest, $desiredWidth)
    {
        // we don't want to work on too big images
        // put the limit to 5 Mbytes
        if (filesize($src) > 5000000) {
            return false;
        }

        // the used fonction is different depending on extension
        if (preg_match('/(jpg|jpeg)$/i', $ext)) {
            $sourceImage = imagecreatefromjpeg($src);
        } elseif (preg_match('/(png)$/i', $ext)) {
            $sourceImage = imagecreatefrompng($src);
        } elseif (preg_match('/(gif)$/i', $ext)) {
            $sourceImage = imagecreatefromgif($src);
        } else {
            return false;
        }

        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);

        // find the "desired height" of this thumbnail, relative to the desired width
        $desiredHeight = floor($height * ($desiredWidth / $width));

        // create a new, "virtual" image
        $virtualImage = imagecreatetruecolor($desiredWidth, $desiredHeight);

        // copy source image at a resized size
        imagecopyresized($virtualImage, $sourceImage, 0, 0, 0, 0, $desiredWidth, $desiredHeight, $width, $height);

        // create the physical thumbnail image to its destination (85% quality)
        imagejpeg($virtualImage, $dest, 85);
    }

    /**
     * Destroy an upload
     *
     * @return bool
     */
    public function destroy()
    {
        $uploadArr = $this->read();

        if ($this->type === 'experiments') {
            // Check file id is owned by connected user
            if ($uploadArr['userid'] != $_SESSION['userid']) {
                throw new Exception(_('This section is out of your reach!'));
            }
        } else {
            $User = new Users();
            $userArr = $User->read($_SESSION['userid']);
            if ($userArr['team'] != $_SESSION['team_id']) {
                throw new Exception(_('This section is out of your reach!'));
            }
        }

        // remove thumbnail
        $thumbPath = ELAB_ROOT . 'uploads/' . $uploadArr['long_name'] . '_th.jpg';
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }
        // now delete file from filesystem
        $filePath = ELAB_ROOT . 'uploads/' . $uploadArr['long_name'];
        unlink($filePath);

        // Delete SQL entry (and verify the type)
        // to avoid someone deleting files saying it's DB whereas it's exp
        $sql = "DELETE FROM uploads WHERE id = :id AND type = :type";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $this->id);
        $req->bindParam(':type', $this->type);

        return $req->execute();
    }

    /**
     * Delete all uploaded files for an entity
     *
     * @return bool
     */
    public function destroyAll()
    {
        $uploadArr = $this->readAll();
        $resultsArr = array();

        foreach ($uploadArr as $upload) {
            $this->id = $upload['id'];
            $resultsArr[] = $this->destroy();
        }

        if (in_array(false, $resultsArr)) {
            throw new Exception('Error deleting uploads.');
        }

        return true;
    }
}
