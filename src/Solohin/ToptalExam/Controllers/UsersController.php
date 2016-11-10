<?php
/**
 * Created by solohin.i@gmail.com.
 * http://data5.pro
 * https://www.upwork.com/freelancers/~0110e79b44736be7ab
 * Date: 10/11/16
 * Time: 17:23
 */

namespace Solohin\ToptalExam\Controllers;

use Solohin\ToptalExam\ErrorTypes;
use Solohin\ToptalExam\Security\UserRoles;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UsersController extends BasicController
{
    const MIN_NORMAL = 1;
    const DEFAULT_NORMAL = 2000;
    const MAX_NORMAL = 32000;

    private function ICanEditUser($user)
    {
        $me = $this->app['user'];
        $myRole = $this->app['user']->getRoles()[0];

        //I am ok
        if ($me->getId() == $user['id']) {
            return true;
        }

        if ($myRole == UserRoles::ROLE_ADMIN) {
            return true;
        } elseif ($myRole == UserRoles::ROLE_MANAGER) {
            //Manager
            return ($user['roles'][0] == UserRoles::ROLE_USER);
        } else {
            //User can not edit other users
            return false;
        }
    }

    public function removeMe()
    {
        return $this->remove($this->app['user']->getId());
    }

    public function getAll(Request $request)
    {
        $role = $this->app['user']->getRoles()[0];
        if ($role == UserRoles::ROLE_USER) {
            return new JsonResponse([
                'success' => false,
                'error_message' => 'You can not list users',
                'error_type' => ErrorTypes::PERMISSION_DENIED,
            ], 403);
        }
        try {
            $users = $this->service->getAll($request->get('page', 1));
            $users = array_map([$this, 'transformUser'], $users);

            $response = ['success' => true];
            $response['users'] = $users;
            return new JsonResponse($response);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function remove($id)
    {
        $toDelete = $this->service->getOne($id);

        if (!$toDelete) {
            return new JsonResponse([
                'success' => false,
                'error_message' => 'User ' . $id . ' not found',
                'error_type' => ErrorTypes::USER_NOT_FOUND,
            ], 404);
        }

        if (!$this->ICanEditUser($toDelete)) {
            return new JsonResponse([
                'success' => false,
                'error_message' => 'You can not delete this user',
                'error_type' => ErrorTypes::PERMISSION_DENIED,
            ], 403);
        }

        try {
            $deleted = $this->service->delete($id);
            $response = ['success' => $deleted];
            if ($deleted) {
                return new JsonResponse($response);
            } else {
                $response['error_message'] = 'User not found';
                $response['error_type'] = ErrorTypes::USER_NOT_FOUND;
                return new JsonResponse($response, 500);
            }
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function getMe()
    {
        return $this->getOne($this->app['user']->getId());
    }

    public function getOne($id)
    {
        $role = $this->app['user']->getRoles()[0];
        if ($role == UserRoles::ROLE_USER && $id != $this->app['user']->getId()) {
            return new JsonResponse([
                'success' => false,
                'error_message' => 'You can not delete this user',
                'error_type' => ErrorTypes::PERMISSION_DENIED,
            ], 403);
        }

        try {
            $user = $this->service->getOne($id);
            $response = ['success' => !!$user];
            if ($user) {
                $response['user'] = $this->transformUser($user);
                return new JsonResponse($response);
            } else {
                $response['error_message'] = 'User not found';
                $response['error_type'] = ErrorTypes::USER_NOT_FOUND;
                return new JsonResponse($response, 404);
            }
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    private function transformUser($user)
    {
        $user['role'] = isset($user['roles'][0]) ? $user['roles'][0] : '';
        $user['can_edit'] = $this->ICanEditUser($user);

        unset($user['roles']);
        unset($user['token']);
        unset($user['password']);
        return $user;
    }
}