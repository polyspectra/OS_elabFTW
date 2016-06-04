<?php
/**
 * \Elabftw\Elabftw\Make
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
namespace Elabftw\Elabftw;

use \Exception;

/**
 * Mother class of MakeCsv, MakePdf and MakeZip
 */
abstract class Make
{
    /** pdo object */
    protected $pdo;
    /** type can be experiments or items */
    protected $type;

    /** child classes need to implement that
     *
     * @return string
     */
    abstract protected function getCleanName();

    /**
     * Generate a long and unique filename
     *
     * @return string a sha512 hash of uniqid()
     */
    protected function getFileName()
    {
        return hash("sha512", uniqid(rand(), true));
    }

    /**
     * Attach the absolute path to a filename in the temporary folder
     *
     * @param string $fileName
     * @return string Absolute path
     */
    protected function getTempFilePath($fileName)
    {
        return ELAB_ROOT . 'uploads/tmp/' . $fileName;
    }

    /**
     * Attach the absolute path to a filename
     *
     * @param string $fileName
     * @return string Absolute path
     */
    protected function getFilePath($fileName)
    {
        return ELAB_ROOT . 'uploads/' . $fileName;
    }
    /**
     * Validate the type we have.
     *
     * @param string $type The type (experiments or items)
     * @return string The valid type
     */
    protected function checkType($type)
    {
        $correctValuesArr = array('experiments', 'items');
        if (!in_array($type, $correctValuesArr)) {
            throw new Exception('Bad type!');
        }
        return $type;
    }

    /**
     * Verify we can see the id
     *
     * @param int $id
     * @return bool|null True if user has reading rights
     */
    protected function checkVisibility($id)
    {
        $sql = "SELECT userid FROM " . $this->type . " WHERE id = :id";
        $req = $this->pdo->prepare($sql);
        $req->bindParam(':id', $id, \PDO::PARAM_INT);
        $req->execute();
        $theUser = $req->fetchColumn();

        if ($this->type === 'experiments') {
            $comparator = $_SESSION['userid'];
        } else {
            // get the team of the userid of the item
            $sql = "SELECT team FROM users WHERE userid = :userid";
            $req = $this->pdo->prepare($sql);
            $req->bindParam(':userid', $theUser, \PDO::PARAM_INT);
            $req->execute();
            $theUser = $req->fetchColumn();
            // we will compare the teams for DB items
            $comparator = $_SESSION['team_id'];
        }

        if ($theUser != $comparator) {
            throw new Exception(_("You don't have sufficient rights to access this item."));
        }
    }
}
