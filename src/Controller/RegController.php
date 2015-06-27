<?php
/**
 * Registration controller.
 *
 * @author Marta Szafraniec <marta.szafraniec@uj.edu.pl>
 * @link http://wierzba.wzks.uj.edu.pl
 * @copyright 2015 EPI
 */

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form;
use Model\UsersModel;

/**
 * Class RegController.
 *
 * @package Controller
 * @implements ControllerProviderInterface
 * @author Marta Szafraniec
 * @link wierzba.wzks.uj.edu.pl/~12_szafraniec
 * @uses Silex\Application;
 * @uses Silex\ControllerProviderInterface;
 * @uses Symfony\Component\HttpFoundation\Request;
 * @uses Symfony\Component\Validator\Constraints as Assert;
 * @uses Symfony\Component\Form;
 * @uses Model\UsersModel;
 */
class RegController implements ControllerProviderInterface
{
    /**
     * Data for view.
     *
     * @access protected
     * @var array $view
     */
    protected $view = array();

    /**
     * Model object.
     *
     * @var $model
     * @access protected
     */
    protected $model;

    /**
     * Routing settings.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @return AlbumsController Result
     * */
    public function connect(Application $app)
    {
        $this->model = new UsersModel($app);
        $regController = $app['controllers_factory'];
        $regController->match('/', array($this, 'register'))
            ->bind('register');
        return $regController;
    }

    /**
     * Creates registration form
     *
     * @param Application $app     application object
     * @param Request     $request request
     *
     * @access public
     * @return registration form
     */
    public function register(Application $app, Request $request)
    {
        try {
            $data = array();

            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'login',
                    'text',
                    array(
                        'constraints' => array(
                            new Assert\NotBlank()
                        )
                    )
                )
                ->add(
                    'password',
                    'password',
                    array(
                        'label' => $app['translator']->trans('password'),
                        'constraints' => array(
                            new Assert\NotBlank()
                        )
                    )
                )
                ->add(
                    'confirm_password',
                    'password',
                    array(
                        'label' => $app['translator']->trans('Confirm password'),
                        'constraints' => array(
                            new Assert\NotBlank()
                        )
                    )
                )
                ->getForm();
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                $data['login'] = $app
                    ->escape($data['login']);
                $data['password'] = $app
                    ->escape($data['password']);
                $data['confirm_password'] = $app
                    ->escape($data['confirm_password']);

                if ($data['password'] === $data['confirm_password']) {
                    $password = $app['security.encoder.digest']
                        ->encodePassword($data['password'], '');

                    $checkLogin = $this->model->getUserByLogin(
                        $data['login']
                    );
                    if (!$checkLogin) {
                        $this->model->register(
                            $data,
                            $password
                        );

                        $app['session']->getFlashBag()->add(
                            'message',
                            array(
                                'type' => 'success',
                                'content' => $app['translator']
                                    ->trans('Registration successful')
                            )
                        );

                        return $app->redirect(
                            $app['url_generator']
                                ->generate(
                                    'posts_index'
                                ),
                            301
                        );
                    } else {
                        $app['session']->getFlashBag()->add(
                            'message',
                            array(
                                'type' => 'warning',
                                'content' => $app['translator']
                                    ->trans('Login is not available')
                            )
                        );
                        return $app['twig']->render(
                            'users/register.twig',
                            array(
                                'form' => $form->createView()
                            )
                        );
                    }
                } else {
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'warning',
                            'content' => $app['translator']
                                ->trans('Passwords are different')
                        )
                    );
                    return $app['twig']->render(
                        'users/register.twig',
                        array(
                            'form' => $form->createView()
                        )
                    );
                }

            }
            return $app['twig']->render(
                'users/register.twig',
                array(
                    'form' => $form->createView(),
                    'data' => $data
                )
            );
        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Error occured'));
        }
    }
}
