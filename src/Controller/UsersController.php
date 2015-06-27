<?php
/**
 * Users controller.
 *
 * @author Marta Szafraniec <marta.szafraniec@uj.edu.pl>
 * @link http://wierzba.wzks.uj.edu.pl/~12_szafraniec
 * @copyright 2015 EPI
 */

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Model\UsersModel;
use Form\RegForm;

/**
 * Class UsersController
 *
 * @category Controller
 * @package Controller
 * @author Marta Szafraniec
 * @link wierzba.wzks.uj.edu.pl/~12_szafraniec
 * @use Silex\Application;
 * @use Silex\ControllerProviderInterface;
 * @use Symfony\Component\HttpFoundation\Request;
 * @use Symfony\Component\Validator\Constraints as Assert;
 * @use Model\UsersModel;
 * @use Form\RegForm;
 */
class UsersController implements ControllerProviderInterface
{
    /**
     * User Model object.
     *
     * @var $model
     * @access protected
     */
    protected $model;

    /**
     * View
     *
     * @var $view
     * @access protected
     */
    protected $view = array();

    /**
     * Routing settings.
     *
     * @access public
     * @param Silex\Application $app Silex application
     */
    public function connect(Application $app)
    {
        $this->model = new UsersModel($app);
        $usersController = $app['controllers_factory'];

        $usersController->match('/delete/{id}/', array($this, 'deleteAction'))
            ->bind('users_delete');

        $usersController->get('/view/{id}', array($this, 'viewAction'))
            ->assert('id', '\.*|\d')
            ->value('id', '')
            ->bind('users_view');

        $usersController->get('/{page}', array($this, 'indexAction'))
            ->value('page', 1)->bind('users_index');
        $usersController->get('/', array($this, 'indexAction'));

        $usersController->get('/view/{id}/{activation}', array($this, 'activationAction'))
            ->bind('users_activation');


        return $usersController;
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
            $pageLimit = 10;
            $page = (int) $request->get('page', 1);
            $usersModel = new UsersModel($app);

            $this->view = array_merge(
                $this->view,
                $usersModel
                ->getPaginatedUsers($page, $pageLimit)
            );


        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']
                ->trans('Users not found'));
        }
        return $app['twig']->render('users/index.twig', $this->view);
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
        $id = (int)$request->get('id', null);

        if ($app['security']->isGranted('ROLE_ADMIN')) {
            $user = $this->model->getUserById($id);
        } else {
            $user = $this->model->getCurrentUserInfo($app);
        }

        if (count($user)) {
            return $app['twig']->render(
                'users/view.twig',
                array(
                    'user' => $user,
                )
            );
        } else {
            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => $app['translator']
                        ->trans('Users not found')
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    'users_index'
                ),
                301
            );
        }
    }

    /**
     * Users activation action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function activationAction(Application $app, Request $request)
    {
        $id = (int)$request->get('id', null);
        $activation = (int)$request->get('activation', null);
        $user = $this->model->getUserById($id);
        $usersModel = new UsersModel($app);
        $usersModel->updateActivation($id, $activation);

        $app['session']->getFlashBag()->add(
            'message',
            array(
                'type' => 'success', 'content' => $app['translator']->trans('Activation change')
            )
        );
        return $app->redirect(
            $app['url_generator']->generate('users_index', array('id'=>$user['id'])),
            301
        );
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

            $usersModel = new UsersModel($app);
            $user = $usersModel->getUserById($id);

            $this->view['user'] = $user;

            $data = array();

            if (count($user)) {
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
                            $usersModel = new UsersModel($app);
                            $usersModel->removeUser($data);

                            $app['session']->getFlashBag()->add(
                                'message',
                                array(
                                    'type' => 'success',
                                    'content' => $app['translator']
                                        ->trans('User removed.')
                                )
                            );

                            return $app->redirect(
                                $app['url_generator']->generate(
                                    'users_index'
                                ),
                                301
                            );
                        } catch (\Exception $e) {
                            $errors[] = $app['translator']
                                ->trans("User couldn't be removed.");
                        }

                    } else {
                        $app['session']->getFlashBag()->add(
                            'message',
                            array(
                                'type' => 'danger',
                                'content' => $app['translator']
                                    ->trans('User not removed.')
                            )
                        );
                        return $app->redirect(
                            $app['url_generator']->generate(
                                'users_index'
                            ),
                            301
                        );
                    }
                }
                $this->view['form'] = $form->createView();

                return $app['twig']->render(
                    'users/delete.twig',
                    $this->view
                );

            } else {
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => $app['translator']
                            ->trans('User not found.')
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        'users_index'
                    ),
                    301
                );
            }
        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Error occured'));
        }
    }
}
