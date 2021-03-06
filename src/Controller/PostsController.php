<?php
/**
 * Blog posts controller.
 *
 * @author Marta Szafraniec <marta.szafraniec@uj.edu.pl>
 * @link http://wierzba.wzks.uj.edu.pl/~12_szafraniec
 * @copyright 2015 EPI
 */

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Model\PostsModel;
use Form\PostForm;
use Model\CommentsModel;

/**
 * Class PostsController
 *
 * @category Controller
 * @package Controller
 * @author Marta Szafraniec
 * @link wierzba.wzks.uj.edu.pl/~12_szafraniec
 * @uses Silex\Application;
 * @uses Silex\ControllerProviderInterface;
 * @uses Symfony\Component\HttpFoundation\Request;
 * @uses Model\PostsModel;
 * @uses Form\PostForm;
 * @uses Model\CommentsModel;

 */

class PostsController implements ControllerProviderInterface
{

    /**
     * Data for view.
     *
     * @access protected
     * @var array $view
     */
    protected $view = array();

    /**
     * Comments Model
     *
     * @access protected
     * @var array $comments_model
     */
    protected $comments_model;

    /**
     * Routing settings.
     *
     * @access public
     * @param Silex\Application $app Silex application
     */
    public function connect(Application $app)
    {
        $postsController = $app['controllers_factory'];

        $this->comments_model = new CommentsModel($app);

        $postsController->match('/add', array($this, 'addAction'))
            ->bind('post_add');
        $postsController->match('/add/', array($this, 'addAction'));

        $postsController->match('/edit/{id}', array($this, 'editAction'))
            ->bind('post_edit');
        $postsController->match('/edit/{id}/', array($this, 'editAction'));

        $postsController->match('/delete/{id}', array($this, 'deleteAction'))
            ->bind('post_delete');
        $postsController->match('/delete/{id}/', array($this, 'deleteAction'));

        $postsController->get('/view/{id}', array($this, 'viewAction'))
            ->bind('post_view');
        $postsController->get('/view/{id}/', array($this, 'viewAction'));

        $postsController->get('/{page}', array($this, 'indexAction'))
            ->value('page', 1)->bind('posts_index');
        $postsController->get('/', array($this, 'indexAction'));

        return $postsController;
    }

    /**
     * Index action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function indexAction(Application $app, Request $request)
    {
        try {
            $pageLimit = 3;
            $page = (int) $request->get('page', 1);
            $postsModel = new PostsModel($app);
            $this->view = array_merge(
                $this->view,
                $postsModel->getPaginatedPosts($page, $pageLimit)
            );

        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Article not found'));
        }
        return $app['twig']->render('posts/index.twig', $this->view);
    }

    /**
     * View all posts
     *
     * @param Application $app     application object
     * @param Request     $request request
     * @access public
     * @return mixed Generates page.
     */
    public function viewAction(Application $app, Request $request)
    {
        try {
            $id = (int)$request->get('id', null);
            $postsModel = new PostsModel($app);
            $this->view['post'] = $postsModel->getPost($id);
            $this->view['comments'] = $this->comments_model->getCommentsList($id);

        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Article not found'));
        }

        return $app['twig']->render('posts/view.twig', $this->view);

    }

    /**
     * Add action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function addAction(Application $app, Request $request)
    {
        try {
            // default values:
            $data = array(
                'title' => $app['translator']
                ->trans('title'),
                'content' => $app['translator']
                    ->trans('content'),
                'date_published' => new \DateTime(),
                'author' => 'Marta'
            );

            $form = $app['form.factory']
                ->createBuilder(new PostForm(), $data)->getForm();
            $form->remove('id');

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $postsModel = new PostsModel($app);
                $postsModel->savePost($data);
                return $app->redirect(
                    $app['url_generator']->generate('posts_index'),
                    301
                );
            }

            $this->view['form'] = $form->createView();

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'success', 'content' => $app['translator']->trans('New post added.')
                )
            );

        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Error occured'));
        }

        return $app['twig']->render('posts/add.twig', $this->view);
    }

    /**
     * Edit action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function editAction(Application $app, Request $request)
    {
        try {
            $id = (int) $request->get('id', 0);

            $postsModel = new PostsModel($app);
            $post = $postsModel->getPost($id);

            $this->view['post'] = $post;

            if (count($post)) {
                $form = $app['form.factory']
                    ->createBuilder(new PostForm(), $post)->getForm();

                $form->remove('date_published');
                $form->remove('date_edited');
                $form->remove('id');

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();
                    $postsModel = new PostsModel($app);
                    $postsModel->savePost($data);
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'success', 'content' => $app['translator']->trans('Post edited.')
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate('post_view', array('id'=>$post['id'])),
                        301
                    );
                }

                $this->view['form'] = $form->createView();

            } else {
                return $app->redirect(
                    $app['url_generator']->generate('post_add'),
                    301
                );
            }
        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Error occured'));
        }

        return $app['twig']->render('posts/edit.twig', $this->view);
    }

    /**
     * Delete action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function deleteAction(Application $app, Request $request)
    {
        try {
            $id = (int)$request->get('id', 0);

            $postsModel = new PostsModel($app);
            $post = $postsModel->getPost($id);

            $this->view['post'] = $post;

            $data = array();

            if (count($post)) {
                $form = $app['form.factory']->createBuilder('form', $data)
                    ->add(
                        'id',
                        'hidden',
                        array(
                            'data' => $id,
                        )
                    )
                    ->add('Tak', 'submit')
                    ->add('Nie', 'submit')
                    ->getForm();

                $form->handleRequest($request);

                if ($form->isValid()) {
                    if ($form->get('Tak')->isClicked()) {
                        $data = $form->getData();
                        try {
                            $postsModel = new PostsModel($app);
                            $postsModel->deletePost($data);

                            $app['session']->getFlashBag()->add(
                                'message',
                                array(
                                    'type' => 'success',
                                    'content' => $app['translator']
                                        ->trans('Post deleted.')
                                )
                            );

                            return $app->redirect(
                                $app['url_generator']->generate(
                                    'posts_index'
                                ),
                                301
                            );
                        } catch (\Exception $e) {
                            $errors[] = $app['translator']
                                ->trans("Post couldn't deleted.");
                        }
                    } else {
                        $app['session']->getFlashBag()->add(
                            'message',
                            array(
                                'type' => 'danger',
                                'content' => $app['translator']
                                    ->trans('Post not deleted.')
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                'post_view',
                                array('id' => $post['id'])
                            ),
                            301
                        );
                    }
                }
                $this->view['form'] = $form->createView();

                return $app['twig']->render(
                    'posts/delete.twig',
                    $this->view
                );

            } else {
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => $app['translator']
                            ->trans('Post not found.')
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        'posts_index'
                    ),
                    301
                );
            }
        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Error occured'));
        }
    }
}
