<?php

namespace Hyper\Adops\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Hyper\Adops\WebBundle\Domain\AdopsUser;
use Hyper\Adops\WebBundle\Domain\AdopsApplication;

/**
 * @author Carl Pham <vanca.vnn@gmail.com>
 */
class UserController extends Controller
{
    const UPLOAD_DIR_USER_IMG = '/../web/uploads/adops/users/';

    /**
     * @Route("/adops/users", name="adops_users")
     * @Method({"GET", "POST"})
     */
    public function indexAction(Request $request)
    {
        $encoderService = $this->container->get('security.password_encoder');
        $appRepo = $this->get('adops.web.application.repository');
        $userRepo = $this->get('adops.web.user.repository');
        $adopsUser = new AdopsUser();
        $applications = $appRepo->findAll();

        $form = $this->createFormBuilder($adopsUser)
                ->add('username', 'text', ['label' => 'Username'])
                ->add('password', 'password', ['label' => 'Password'])
                ->add('type', 'choice', [
                    'choice_list' => new ChoiceList(
                        ['', 'transparent', 'limited', 'admin'],
                        ['Choose Access Type', 'Transparent', 'Limited', 'Admin']
                        ),
                    'label' => 'Access Type'
                    ])
                ->add('app_id', 'hidden', ['label' => 'App Access'])
                ->add('fullname', 'text', ['label' => 'Full Name'])
                ->add('team', 'text', ['label' => 'Team Name'])
                ->add('email', 'email', ['label' => 'Email'])
                ->add('avatar', 'file', ['label' => 'Profile image'])
                ->add('add_client', 'submit', ['label' => 'Add Client'])
                ->getForm();
        $cloned = clone $form;
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $adopsUserId = $adopsUser->getId();
            $password = $encoderService->encodePassword($adopsUser, $adopsUser->getPassword());
            $adopsUser->setPassword($password);

            $dataExtra = $request->request->all();
            if (isset($dataExtra['applications'])) {
                $appAccessIds = json_encode($dataExtra['applications']);
                $adopsUser->setAppId($appAccessIds);
            }

            $avatarUser = $adopsUser->getAvatar();
            $destination = $this->container->getParameter('kernel.root_dir').self::UPLOAD_DIR_USER_IMG.$adopsUserId;
            $adopsUserImg = $this->uploadUserImage($avatarUser, $destination);
            if (!$adopsUserImg['status']) {
                $this->addFlash('notice', $adopsUserImg['errors']);
                return $this->render('adops/user.html.twig', [
                    'form'=>$cloned->createView(),
                    'users'=>$userRepo->findAll(),
                    'applications'=>$applications
                ]);
            }
            $adopsUser->setAvatar($adopsUserImg['file_name']);

            $userRepo->create($adopsUser);
            $this->addFlash('notice', 'Create User successfully!');
            $form = $cloned;
        }
        $users = $userRepo->findAll();
        foreach ($users as $k => $user) {
            $appAccess = $appRepo->findBy(['id'=>json_decode($user->getAppId())]);
            $appName = [];
            foreach ($appAccess as $appAcces) {
                array_push($appName, $appAcces->getAppName().' '.ucfirst($appAcces->getPlatform()));
            }
            $user->{"appAccess"} = $appName;
            $users[$k] = $user;
        }

        return $this->render('adops/user.html.twig', ['form'=>$form->createView(), 'users'=>$users, 'applications'=>$applications]);
    }

    /**
     * @Route("/adops/users/{id}/edit", name="adops_users_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction($id, Request $request)
    {
        $encoderService = $this->container->get('security.password_encoder');
        $userRepo = $this->get('adops.web.user.repository');
        $adopsUser = $userRepo->find($id);
        $appRepo = $this->get('adops.web.application.repository');
        $applications = $appRepo->findAll();

        $form = $this->createFormBuilder($adopsUser)
                ->add('username', 'text', ['label' => 'Username'])
                ->add('password', 'password', ['label' => 'Password'])
                ->add('type', 'choice', [
                    'choice_list' => new ChoiceList(
                        ['', 'transparent', 'limited', 'admin'],
                        ['Choose Access Type', 'Transparent', 'Limited', 'Admin']
                        ),
                    'label' => 'Access Type'
                    ])
                ->add('app_id', 'hidden', ['label' => 'App Access'])
                ->add('fullname', 'text', ['label' => 'Full Name'])
                ->add('team', 'text', ['label' => 'Team Name'])
                ->add('email', 'email', ['label' => 'Email'])
                ->add('avatar', 'hidden', ['label' => 'Profile image'])
                ->add('add_client', 'submit', ['label' => 'Update Client'])
                ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $adopsUserId = $adopsUser->getId();
                $password = $encoderService->encodePassword($adopsUser, $adopsUser->getPassword());
                $adopsUser->setPassword($password);

                $dataExtra = $request->request->all();
                if (isset($dataExtra['applications'])) {
                    $appAccessIds = json_encode($dataExtra['applications']);
                    $adopsUser->setAppId($appAccessIds);
                }
                $avatarUser = $request->files->get('file');
                if (null != $avatarUser) {
                    $destination = $this->container->getParameter('kernel.root_dir').self::UPLOAD_DIR_USER_IMG.$adopsUserId;
                    $adopsUserImg = $this->uploadUserImage($avatarUser, $destination);
                    if (!$adopsUserImg['status']) {
                        $this->addFlash('notice', $adopsUserImg['errors']);
                        return $this->render('adops/user.html.twig', [
                            'form'=>$cloned->createView(),
                            'users'=>$userRepo->findAll(),
                            'applications'=>$applications
                        ]);
                    }
                    @unlink($destination.'/'.$adopsUser->getAvatar());
                    $adopsUser->setAvatar($adopsUserImg['file_name']);
                }

                $userRepo->create($adopsUser);
                return $this->redirectToRoute('adops_users');
            } else {
                $fErrors = $form->getErrorsAsString();
                echo '<pre>';
                var_dump($fErrors);
                die;
            }

        }

        $users = $userRepo->findAll();
        return $this->render('adops/user.html.twig', ['form'=>$form->createView(), 'users'=>$users, 'applications'=>$applications]);
    }

    /**
     * @Route("/adops/users/{id}/delete", name="adops_users_delete")
     * @Method("POST")
     */
    public function deleteAction($id)
    {
        $userRepo = $this->get('adops.web.user.repository');
        $adopsUser = $userRepo->find($id);
        if (null == $adopsUser) {
            $this->addFlash('notice', "Can not delete user with ID: {$id}");
            return $this->redirectToRoute('adops_users');
        }
        $deleteUser = $userRepo->delete($adopsUser);
        if ($deleteUser) {
            $dir = $this->container->getParameter('kernel.root_dir').self::UPLOAD_DIR_USER_IMG.$id;
            array_map('unlink', glob("$dir/*.*"));
            rmdir($dir);
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        return $response->setContent(json_encode(array('status'=>$deleteUser)));
    }

    public function uploadUserImage(UploadedFile $file, $destination)
    {
        if (empty($destination)) {
            return ['status'=>false, 'errors'=>'Destination empty.'];
        }
        $tmpFilePath = $file->getPathName();
        if (!file_exists($destination)) {
            $dir = mkdir($destination);
            if (!$dir) {
                return ['status'=>false, 'errors'=>'Can not create directory.'];
            }
        }
        $originalName = $file->getClientOriginalName();
        $fileUploaded = $file->move($destination, $originalName);

        return ['status'=>true, 'file_name'=>$originalName];
    }
}