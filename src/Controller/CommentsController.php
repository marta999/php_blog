<?php
/**
 * Blog comments controller.
 *
 * @author Marta Szafraniec <marta.szafraniec@uj.edu.pl>
 * @link http://wierzba.wzks.uj.edu.pl/~12_szafraniec
 * @copyright 2015 EPI
 */

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Model\CommentsModel;
use Model\PostsModel;
use Form\CommentForm;
use Model\UsersModel;

/**
 * Class CommentsController
 *
 * @category Controller
 * @package Controller
 * @author Marta Szafraniec
 * @link wierzba.wzks.uj.edu.pl/~12_szafraniec
 * @uses Silex\Application;
 * @uses Silex\ControllerProviderInterface;
 * @uses Symfony\Component\HttpFoundation\Request;
 * @uses Model\CommentsModel;
 * @uses Model\PostsModel;
 * @uses Form\CommentForm;
 */
class CommentsController implements ControllerProviderInterface
{

    /**
     * Data for view.
     *
     * @access protected
     * @var array $view
     */
    protected $view = array();

    /**
     * Comment Model object.
     *
     * @var $model
     * @access protected
     */
    protected $model;

    /**
     * User Model object.
     *
     * @var $model
     * @access protected
     */
    protected $user;

    /**
     * Routing settings.
     *
     * @access public
     * @param Silex\Application $app Silex application
     */
    public function connect(Application $app)
    {
        $commentsController = $app['controllers_factory'];

        $commentsController->match('/add', array($this, 'addAction'))
            ->bind('comments_add');
        $commentsController->match('/add/', array($this, 'addAction'));

        $commentsController->match('/edit/{id}', array($this, 'editAction'))
            ->bind('comments_edit');
        $commentsController->match('/edit/{id}/', array($this, 'editAction'));

        $commentsController->match('/delete/{id}', array($this, 'deleteAction'))
            ->bind('comments_delete');
        $commentsController->match('/delete/{id}/', array($this, 'deleteAction'));

        $commentsController->get('/view/{id}', array($this, 'viewAction'))
            ->bind('comments_view');
        $commentsController->get('/view/{id}/', array($this, 'viewAction'));

        $commentsController->get('/{page}', array($this, 'indexAction'))
            ->value('page', 1)->bind('comments_index');

        return $commentsController;
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
            $id = (int)$request->get('id', 0);
            $commentsModel = new CommentsModel($app);
            $comment = $this->model->getCommentsList($id);
        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Error occured'));
        }
        return $app['twig']->render('comments/index.twig', $this->view);
    }

    /**
     * View action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function viewAction(Application $app, Request $request)
    {
        try {
            $view = array();
            $id = (int)$request->get('id', null);
            $commentsModel = new CommentsModel($app);
            $this->view['comment'] = $commentsModel->getPost($id);
        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Error occured'));
        }

        return $app['twig']->render('comments/view.twig', $this->view);
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

            $posts_id = (int)$request->get('id');

            $data = array(
                'date' => date('Y-m-d'),
                'posts_id' => (int)$posts_id,
            );

            $form = $app['form.factory']
                ->createBuilder(new CommentForm(), $data)
                ->getForm();

            $form->remove('id');

            $form->handleRequest($request);

            if ($form->isValid()) {

                $data = $form->getData();

                $commentsModel = new CommentsModel($app);
                $commentsModel->saveComment($data);

                $app['session']->getFlashBag()
                    ->add(
                        'message',
                        array(
                            'type' => 'success',
                            'content' =>
                                $app['translator']->trans(
                                    'Comment Added'
                                )
                        )
                    );

                return $app->redirect(
                    $app['url_generator']
                        ->generate(
                            'posts_index'
                        ),
                    301
                );
            }

            return $app['twig']->render(
                'comments/add.twig',
                array(
                    'form' => $form
                        ->createView(),
                    'posts_id' => $posts_id
                )
            );

        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Error occured'));
        }

        return $app['twig']->render('comments/add.twig', $this->view);
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
            $id = (int)$request->get('id', 0);

            $commentsModel = new CommentsModel($app);
            $comment = $commentsModel->getComment($id);

            $this->view['comment'] = $comment;

            if (count($comment)) {
                $form = $app['form.factory']
                    ->createBuilder(new CommentForm(), $comment)->getForm();

                //$form->remove('date');

                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();
                    $commentsModel = new CommentsModel($app);
                    $commentsModel->addComment($data);
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'success',
                            'content' => $app['translator']
                                ->trans('Comment edited.')
                        )
                    );

                    return $app->redirect(
                        $app['url_generator']->generate(
                            'post_view',
                            array('id' => $comment['posts_id'])
                        ),
                        301
                    );
                }

                $this->view['form'] = $form->createView();

            } else {
                return $app->redirect(
                    $app['url_generator']->generate('comments_add'),
                    301
                );
            }
        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Error occured'));
        }

        return $app['twig']->render('comments/edit.twig', $this->view);
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

            $commentsModel = new CommentsModel($app);
            $comment = $commentsModel->getComment($id);

            $this->view['comment'] = $comment;

            $data = array();

            if (count($comment)) {
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
                            $commentsModel = new CommentsModel($app);
                            $commentsModel->deleteComment($data);

                            $app['session']->getFlashBag()->add(
                                'message',
                                array(
                                    'type' => 'success',
                                    'content' => $app['translator']
                                        ->trans('Comment deleted.')
                                )
                            );
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    'post_view',
                                    array('id' => $comment['posts_id'])
                                ),
                                301
                            );
                        } catch (\Exception $e) {
                            $errors[] = ':(';
                        }
                    } else {
                        $app['session']->getFlashBag()->add(
                            'message',
                            array(
                                'type' => 'danger',
                            'content' => $app['translator']
                                ->trans('Comment not deleted.')
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                'post_view',
                                array('id' => $comment['posts_id'])
                            ),
                            301
                        );
                    }
                }
                return $app['twig']->render(
                    'comments/delete.twig',
                    array(
                        'form' => $form->createView()
                    )
                );
            } else {
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => $app['translator']
                            ->trans('Comment not found.')
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        'post_view',
                        array('id' => $comment['posts_id'])
                    ),
                    301
                );
            }
        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Error occured'));
        }
    }
}
