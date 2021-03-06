<?php

namespace Application\Controller\MembersArea;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UsersController
{
    public function indexAction(Request $request, Application $app)
    {
        $data = [];

        if (
            !$app['security.authorization_checker']->isGranted('ROLE_USERS_EDITOR') &&
            !$app['security.authorization_checker']->isGranted('ROLE_ADMIN')
        ) {
            $app->abort(403);
        }

        $limitPerPage = $request->query->get('limit_per_page', 20);
        $currentPage = $request->query->get('page');

        $userResults = $app['orm.em']
            ->createQueryBuilder()
            ->select('u')
            ->from('Application\Entity\UserEntity', 'u')
            ->leftJoin('u.profile', 'p')
        ;

        $pagination = $app['paginator']->paginate(
            $userResults,
            $currentPage,
            $limitPerPage,
            [
                'route' => 'members-area.users',
                'defaultSortFieldName' => 'u.email',
                'defaultSortDirection' => 'asc',
            ]
        );

        $data['pagination'] = $pagination;

        return new Response(
            $app['twig']->render(
                'contents/members-area/users/index.html.twig',
                $data
            )
        );
    }

    public function newAction(Request $request, Application $app)
    {
        $data = [];

        if (
            !$app['security.authorization_checker']->isGranted('ROLE_USERS_EDITOR') &&
            !$app['security.authorization_checker']->isGranted('ROLE_ADMIN')
        ) {
            $app->abort(403);
        }

        $form = $app['form.factory']->create(
            \Application\Form\Type\UserType::class,
            new \Application\Entity\UserEntity(),
            [
                'app' => $app,
            ]
        );

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $userEntity = $form->getData();

                /*** Image ***/
                $userEntity
                    ->getProfile()
                    ->setImageUploadPath($app['baseUrl'].'/assets/uploads/')
                    ->setImageUploadDir(WEB_DIR.'/assets/uploads/')
                    ->imageUpload()
                ;

                /*** Password ***/
                $userEntity->setPlainPassword(
                    $userEntity->getPlainPassword(), // This getPassword() is here just the plain password. That's why we need to convert it
                    $app['security.encoder_factory']
                );

                $app['orm.em']->persist($userEntity);
                $app['orm.em']->flush();

                $app['flashbag']->add(
                    'success',
                    $app['translator']->trans(
                        'The user has been added!'
                    )
                );

                return $app->redirect(
                    $app['url_generator']->generate(
                        'members-area.users.edit',
                        [
                            'id' => $userEntity->getId(),
                        ]
                    )
                );
            }
        }

        $data['form'] = $form->createView();

        return new Response(
            $app['twig']->render(
                'contents/members-area/users/new.html.twig',
                $data
            )
        );
    }

    public function detailAction($id, Request $request, Application $app)
    {
        $data = [];

        if (
            !$app['security.authorization_checker']->isGranted('ROLE_USERS_EDITOR') &&
            !$app['security.authorization_checker']->isGranted('ROLE_ADMIN')
        ) {
            $app->abort(403);
        }

        $user = $app['orm.em']->find('Application\Entity\UserEntity', $id);

        if (!$user) {
            $app->abort(404);
        }

        $data['user'] = $user;

        return new Response(
            $app['twig']->render(
                'contents/members-area/users/detail.html.twig',
                $data
            )
        );
    }

    public function editAction($id, Request $request, Application $app)
    {
        $data = [];

        if (
            !$app['security.authorization_checker']->isGranted('ROLE_USERS_EDITOR') &&
            !$app['security.authorization_checker']->isGranted('ROLE_ADMIN')
        ) {
            $app->abort(403);
        }

        $user = $app['orm.em']->find(
            'Application\Entity\UserEntity',
            $id
        );

        if (!$user) {
            $app->abort(404);
        }

        $form = $app['form.factory']->create(
            \Application\Form\Type\UserType::class,
            $user,
            [
                'app' => $app,
            ]
        );

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $userEntity = $form->getData();

                if ($userEntity->hasRole('ROLE_SUPER_ADMIN') &&
                    $userEntity->isLocked()) {
                    $app['flashbag']->add(
                        'danger',
                        $app['translator']->trans(
                            'You can not lock an user with the super admin role!'
                        )
                    );

                    return $app->redirect(
                        $app['url_generator']->generate(
                            'members-area.users.edit',
                            [
                                'id' => $userEntity->getId(),
                            ]
                        )
                    );
                }

                /*** Image ***/
                $userEntity
                    ->getProfile()
                    ->setImageUploadPath($app['baseUrl'].'/assets/uploads/')
                    ->setImageUploadDir(WEB_DIR.'/assets/uploads/')
                    ->imageUpload()
                ;

                /*** Password ***/
                if ($userEntity->getPlainPassword()) {
                    $userEntity->setPlainPassword(
                        $userEntity->getPlainPassword(), // This getPassword() is here just the plain password. That's why we need to convert it
                        $app['security.encoder_factory']
                    );
                }

                $app['orm.em']->persist($userEntity);
                $app['orm.em']->flush();

                $app['flashbag']->add(
                    'success',
                    $app['translator']->trans(
                        'The user has been edited!'
                    )
                );

                return $app->redirect(
                    $app['url_generator']->generate(
                        'members-area.users.edit',
                        [
                            'id' => $userEntity->getId(),
                        ]
                    )
                );
            }
        }

        $data['form'] = $form->createView();

        $data['user'] = $user;

        return new Response(
            $app['twig']->render(
                'contents/members-area/users/edit.html.twig',
                $data
            )
        );
    }

    public function removeAction($id, Request $request, Application $app)
    {
        $data = [];

        if (
            !$app['security.authorization_checker']->isGranted('ROLE_USERS_EDITOR') &&
            !$app['security.authorization_checker']->isGranted('ROLE_ADMIN')
        ) {
            $app->abort(403);
        }

        $user = $app['orm.em']->find('Application\Entity\UserEntity', $id);

        if (!$user) {
            $app->abort(404);
        }

        $confirmAction = $request->query->has('action') &&
            $request->query->get('action') == 'confirm';

        if ($confirmAction) {
            try {
                $app['orm.em']->remove($user);
                $app['orm.em']->flush();

                $app['flashbag']->add(
                    'success',
                    $app['translator']->trans(
                        'The user has been removed!'
                    )
                );
            } catch (\Exception $e) {
                $app['flashbag']->add(
                    'danger',
                    $app['translator']->trans(
                        $e->getMessage()
                    )
                );
            }

            return $app->redirect(
                $app['url_generator']->generate('members-area.users')
            );
        }

        $data['user'] = $user;

        return new Response(
            $app['twig']->render('contents/members-area/users/remove.html.twig', $data)
        );
    }
}
