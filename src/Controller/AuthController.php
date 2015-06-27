<?php
/**
 * Auth controller.
 *
 * @author Marta Szafraniec <marta.szafraniec@uj.edu.pl>
 * @link http://wierzba.wzks.uj.edu.pl/~12_szafraniec
 * @copyright 2015 EPI
 */
namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Form\LoginForm;

/**
 * Class AuthController
 *
 * @category Controller
 * @package Controller
 * @author Marta Szafraniec
 * @link wierzba.wzks.uj.edu.pl/~12_szafraniec
 * @uses Silex\Application;
 * @uses Silex\ControllerProviderInterface;
 * @uses Symfony\Component\HttpFoundation\Request;
 * @uses Form\LoginForm;
 */
class AuthController implements ControllerProviderInterface
{
    /**
     * Data for view.
     *
     * @access protected
     * @var array $view
     */
    protected $view = array();

    /**
     * Routing settings.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @return AlbumsController Result
     */
    public function connect(Application $app)
    {
        $authController = $app['controllers_factory'];
        $authController->match('login', array($this, 'loginAction'))
            ->bind('auth_login');
        $authController->get('logout', array($this, 'logoutAction'))
            ->bind('auth_logout');
        return $authController;
    }

    /**
     * Login action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function loginAction(Application $app, Request $request)
    {
        try {
            $user = array(
                'login' => $app['session']->get('_security.last_username')
            );

            $form = $app['form.factory']->createBuilder(new LoginForm(), $user)
                ->getForm();

            $this->view = array(
                'form' => $form->createView(),
                'error' => $app['security.last_error']($request)
            );
        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Error occured'));
        }

        return $app['twig']->render('auth/login.twig', $this->view);
    }

    /**
     * Logout action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function logoutAction(Application $app, Request $request)
    {
        try {
            $app['session']->clear();
        } catch (\PDOException $e) {
            $app->abort(404, $app['translator']->trans('Error occured'));
        }
        return $app['twig']->render('auth/logout.twig', $this->view);
    }
}
