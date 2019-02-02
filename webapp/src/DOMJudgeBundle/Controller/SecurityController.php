<?php declare(strict_types=1);
namespace DOMJudgeBundle\Controller;

use DOMJudgeBundle\Service\DOMJudgeService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

use DOMJudgeBundle\Utils\Utils;
use DOMJudgeBundle\Form\UserRegistrationType;
use DOMJudgeBundle\Entity\User;
use DOMJudgeBundle\Entity\Team;

class SecurityController extends Controller
{
    /**
     * @var DOMJudgeService
     */
    private $DOMJudgeService;

    public function __construct(DOMJudgeService $DOMJudgeService)
    {
        $this->DOMJudgeService = $DOMJudgeService;
    }

    /**
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request)
    {
        $allowIPAuth = false;
        $authmethods = [];
        if ($this->container->hasParameter('domjudge.authmethods')) {
            $authmethods = $this->container->getParameter('domjudge.authmethods');
        }
        if (in_array('ipaddress', $authmethods)) {
            $allowIPAuth = true;
        }

        $clientIP = $this->DOMJudgeService->getClientIp();
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $user->setLastLogin(Utils::now());
            $user->setLastIpAddress($clientIP);

            $this->getDoctrine()->getManager()->flush();
            return $this->redirect($this->generateUrl('root'));
        }

        $authUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authUtils->getLastUsername();

        $auth_ipaddress_users = [];
        if ($allowIPAuth) {
            $em = $this->getDoctrine()->getManager();
            $auth_ipaddress_users = $em->getRepository('DOMJudgeBundle:User')->findBy(['ipAddress' => $clientIP]);
        }

        // Add a header so we can detect that this is the login page
        $response = new Response();
        $response->headers->set('X-Login-Page', $this->generateUrl('login'));

        return $this->render('DOMJudgeBundle:security:login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error,
            'allow_registration'    => $this->DOMJudgeService->dbconfig_get('allow_registration', false),
            'allowed_authmethods'   => $authmethods,
            'auth_xheaders_present' => $request->headers->get('X-DOMjudge-Login'),
            'auth_ipaddress_users'  => $auth_ipaddress_users,
        ), $response);
    }

    /**
     * @Route("/register", name="register")
     */
    public function registerAction(Request $request)
    {
        // Redirect if already logged in
        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirect($this->generateUrl('root'));
        }
        if (!$this->DOMJudgeService->dbconfig_get('allow_registration', false)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400, "Registration not enabled");
        }

        $user = new User();
        $registration_form = $this->createForm(UserRegistrationType::class, $user);
        $registration_form->handleRequest($request);
        if ($registration_form->isSubmitted() && $registration_form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $self_registered_category = $em->getRepository('DOMJudgeBundle:TeamCategory')->findOneByName('Self-Registered');
            $team_role = $em->getRepository('DOMJudgeBundle:Role')->findOneBy(['dj_role' => 'team']);

            $plainPass = $registration_form->get('plainPassword')->getData();
            $password = $this->get('security.password_encoder')->encodePassword($user, $plainPass);
            $user->setPassword($password);
            $user->setName($user->getUsername());
            $user->addRole($team_role);


            // Create a team to go with the user, then set some team attributes
            $team = new Team();
            $user->setTeam($team);
            $team->addUser($user);
            $team->setName($user->getUsername());
            $team->setCategory($self_registered_category);
            $team->setComments('Registered by ' . $this->DOMJudgeService->getClientIp() . ' on ' . date('r'));

            $em->persist($user);
            $em->persist($team);
            $em->flush();

            $this->addFlash('notice', 'Account registered successfully. Please log in.');

            return $this->redirect($this->generateUrl('login'));
        }

        return $this->render('DOMJudgeBundle:security:register.html.twig', array(
                'registration_form' => $registration_form->createView(),
        ));
    }
}
