<?php
/**
 * Posts model.
 *
 * @author Marta Szafraniec
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
class PostsModel
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
     * Gets all posts.
     *
     * @access public
     * @return array Result
     */
    public function getAll()
    {
        try {
            $query = 'SELECT id, title, content, date_published, author, date_edited FROM posts';
            $result = $this->db->fetchAll($query);
            return !$result ? array() : $result;
        } catch (Exception $e) {
            echo 'Caught exception: ' .  $e->getMessage() . "\n";
        }
    }

    /**
     * Gets single post data.
     *
     * @access public
     * @param integer $id Record Id
     * @return array Result
     */
    public function getPost($id)
    {
        try {
            if (($id != '') && ctype_digit((string)$id)) {
                $query = 'SELECT id, title, content, date_published, author, date_edited FROM posts WHERE id= ?';
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

    /**
     * Counts post pages.
     *
     * @access public
     * @param integer $limit Number of records on single page
     * @return integer Result
     */
    public function countPostsPages($limit)
    {
        try {
            $pagesCount = 0;
            $sql = 'SELECT COUNT(*) as pages_count FROM posts';
            $result = $this->db->fetchAssoc($sql);
            if ($result) {
                $pagesCount =  ceil($result['pages_count']/$limit);
            }
            return $pagesCount;
        } catch (Exception $e) {
            echo 'Caught exception: ' .  $e->getMessage() . "\n";
        }
    }

    /**
     * Returns current page number.
     *
     * @access public
     * @param integer $page Page number
     * @param integer $pagesCount Number of all pages
     * @return integer Page number
     *
     */
    public function getCurrentPageNumber($page, $pagesCount)
    {
        try {
            return (($page <= 1) || ($page > $pagesCount)) ? 1 : $page;
        } catch (Exception $e) {
            echo 'Caught exception: ' .  $e->getMessage() . "\n";
        }
    }

    /**
     * Get all posts on page
     *
     * @access public
     * @param integer $page Page number
     * @param integer $limit Number of records on single page
     * @param integer $pagesCount Number of all pages
     * @retun array Result
     */
    public function getPostsPage($page, $limit)
    {
        try {
            $sql = 'SELECT id, title, content, date_published, author, date_edited
              FROM posts ORDER BY date_published
              DESC LIMIT :start, :limit';
            $statement = $this->db->prepare($sql);
            $statement->bindValue('start', ($page-1)*$limit, \PDO::PARAM_INT);
            $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
            $statement->execute();
            return $statement->fetchAll();
        } catch (Exception $e) {
            echo 'Caught exception: ' .  $e->getMessage() . "\n";
        }
    }

    /**
     * Gets posts for pagination.
     *
     * @access public
     * @param integer $page Page number
     * @param integer $limit Number of records on single page
     *
     * @return array Result
     */
    public function getPaginatedPosts($page, $limit)
    {
        try {
            $pagesCount = $this->countPostsPages($limit);
            $page = $this->getCurrentPageNumber($page, $pagesCount);
            $posts = $this->getPostsPage($page, $limit);
            return array(
                'posts' => $posts,
                'paginator' => array('page' => $page, 'pagesCount' => $pagesCount)
            );
        } catch (Exception $e) {
            echo 'Caught exception: ' .  $e->getMessage() . "\n";
        }
    }

    /** Save post.
     *
     * @access public
     * @param array $post Post data
     * @retun mixed Result
     */
    public function savePost($post)
    {
        try {
            if (isset($post['id'])
                && ($post['id'] != '')
                && ctype_digit((string)$post['id'])) {
                // update record
                $id = $post['id'];
                unset($post['id']);
                $post['date_edited'] = new \DateTime();
                $post['date_edited'] = $post['date_edited']->format('Y-m-d H:i:s');
                return $this->db->update('posts', $post, array('id' => $id));
            } else {
                // add new record
                $post['date_published'] = new \DateTime();
                $post['date_published'] = $post['date_published']->format('Y-m-d H:i:s');
                return $this->db->insert('posts', $post);
            }
        } catch (Exception $e) {
            echo 'Caught exception: ' .  $e->getMessage() . "\n";
        }
    }

    /**
     * Delete single post.
     *
     * @access public
     * @param array $data Form data
     *
     * @return array Result
     */

    public function deletePost($data)
    {
        try {
            if (($data['id'] != '') && ctype_digit((string)$data['id'])) {
                $query = 'DELETE FROM posts WHERE id= ?';
                return $this->db->delete('posts', array('id' => $data['id']));
            } else {
                return array();
            }
        } catch (Exception $e) {
            echo 'Caught exception: ' .  $e->getMessage() . "\n";
        }
    }
}
