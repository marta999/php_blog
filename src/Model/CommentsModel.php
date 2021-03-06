<?php
/**
 * Comments model.
 *
 * @author Marta Szafraniec <marta.szafraniec@uj.edu.pl>
 * @link http://wierzba.wzks.uj.edu.pl/~12_szafraniec
 * @copyright 2015 EPI
 */

namespace Model;

use Silex\Application;

/**
 * Class PostsModel.
 *
 * @category Epi
 * @package Model
 * @use Silex\Application
 */
class CommentsModel
{
    /**
     * Db object.
     *
     * @access protected
     * @var Silex\Provider\DoctrineServiceProvider $db
     */
    protected $db;

    /**
     * Object constructor.
     *
     * @access public
     * @param Silex\Application $app Silex application
     */
    public function __construct(Application $app)
    {
        try {
            $this->db = $app['db'];
        } catch (Exception $e) {
            echo 'Caught exception: ' .  $e->getMessage() . "\n";
        }
    }

    /**
     * Get all comments for one post
     *
     * @param $id post id
     *
     * @access public
     * @internal param int $idpost
     * @return Array Comment
     */
    public function getCommentsList($id)
    {
        try {
            $sql = 'SELECT * FROM comments WHERE posts_id = ?';
            return $this->db->fetchAll($sql, array($id));
        } catch (Exception $e) {
            echo 'Caught exception: ' .  $e->getMessage() . "\n";
        }
    }

    /**
     * Gets one comment.
     *
     * @param Integer $id
     *
     * @access public
     * @return array Associative array with comments
     */
    public function getComment($id)
    {
        try {
            if (($id != '') && ctype_digit((string)$id)) {
                $query = 'SELECT id, date, comment, posts_id, users_id FROM comments WHERE id = ? LIMIT 1';
                $result = $this->db->fetchAssoc($query, array((int)$id));
                if (!$result) {
                    return array();
                } else {
                    return $result;
                }
            } else {
                return array();
            }
        } catch (Exception $e) {
            echo 'Caught exception: ' .  $e->getMessage() . "\n";
        }
    }

    /** Add comment.
     *
     * @access public
     * @param array $post Post data
     * @retun mixed Result
     */
    public function addComment($comment)
    {
        try {
            if (isset($comment['id'])
                && ($comment['id'] != '')
                && ctype_digit((string)$comment['id'])) {
                // update record
                $id = $comment['id'];
                unset($comment['id']);
                $comment['date_edited'] = new \DateTime();
                $comment['date_edited'] = $comment['date_edited']->format('Y-m-d H:i:s');
                return $this->db->update('comments', $comment, array('id' => $id));
            } else {
                // add new record
                $comment['date'] = new \DateTime();
                $comment['date'] = $comment['date']->format('Y-m-d H:i:s');
                return $this->db->insert('comments', $comment);
            }
        } catch (Exception $e) {
            echo 'Caught exception: ' .  $e->getMessage() . "\n";
        }
    }

    /**
     * Save comment.
     *
     * @param Array $data date about addcomment.
     *
     * @access public
     * @return Void
     */
    public function saveComment($data)
    {
        $sql = 'INSERT INTO comments
          (comment, date, posts_id)
          VALUES (?,?,?)';
        $this->db
            ->executeQuery(
                $sql,
                array(
                    $data['comment'],
                    $data['date'],
                    $data['posts_id'],
                    //$data['user_id']
                )
            );
    }

    /**
     * Delete single comment.
     *
     * @access public
     * @param integer $id Record Id
     * @return array Result
     */

    public function deleteComment($data)
    {
        try {
            if (($data['id'] != '') && ctype_digit((string)$data['id'])) {
                $query = 'DELETE FROM comments WHERE id= ?';
                //return $this->db->delete('comments', array('id' => $id));
                $this->db->executeQuery($query, array($data['id']));
            } else {
                return array();
            }

        } catch (Exception $e) {
            echo 'Caught exception: ' .  $e->getMessage() . "\n";
        }
        //var_dump($id);
    }
}
